<?php

namespace App\Modules\Shopify\Services\Sync;

use App\Modules\Shopify\DTOs\CustomerAddressData;
use App\Modules\Shopify\DTOs\CustomerData;
use App\Modules\Shopify\Services\ShopifyClient;
use App\Modules\Stores\Models\Store;
use App\Modules\User\Models\Customer;
use App\Modules\User\Models\CustomerAddress;

class CustomersSyncService
{
    private const PAGE_SIZE = 50;
    private const ADDRESS_PAGE_SIZE = 100;

    public function sync(Store $store): int
    {
        $client = new ShopifyClient($store);
        $count = 0;
        $after = null;

        do {
            $response = $client->query(
                query: $this->query(),
                variables: array_filter([
                    'first' => self::PAGE_SIZE,
                    'after' => $after,
                    'addressFirst' => self::ADDRESS_PAGE_SIZE,
                ]),
            );

            $connection = $response['data']['customers'] ?? null;

            if (!is_array($connection)) {
                break;
            }

            foreach ($connection['edges'] ?? [] as $edge) {
                $node = $edge['node'] ?? null;

                if (is_array($node)) {
                    $this->syncCustomer($store, $this->customerData($client, $node));
                    $count++;
                }
            }

            $pageInfo = $connection['pageInfo'] ?? [];
            $after = $pageInfo['endCursor'] ?? null;
        } while (!empty($pageInfo['hasNextPage']) && !empty($after));

        return $count;
    }

    private function syncCustomer(Store $store, CustomerData $data): Customer
    {
        $customer = $this->findCustomer($store, $data);
        $customer->fill($this->customerPayload($store, $data));
        $customer->save();

        $this->syncAddresses($store, $customer, $data->addresses);

        return $customer;
    }

    private function findCustomer(Store $store, CustomerData $data): Customer
    {
        $query = Customer::query()->where('store_id', $store->id);

        $customer = (clone $query)
            ->where('shopify_customer_id', $data->shopifyCustomerId)
            ->first();

        if (!$customer && !empty($data->email)) {
            $customer = (clone $query)
                ->where('email', $data->email)
                ->first();
        }

        return $customer ?? new Customer();
    }

    private function customerPayload(Store $store, CustomerData $data): array
    {
        return [
            'store_id' => $store->id,
            'shopify_customer_id' => $data->shopifyCustomerId,
            'first_name' => $data->firstName,
            'last_name' => $data->lastName,
            'display_name' => $data->displayName,
            'email' => $data->email,
            'phone' => $data->phone,
            'status' => $data->status,
            'state' => $data->state,
            'tags' => $data->tags,
            'note' => $data->note,
            'verified_email' => $data->verifiedEmail,
            'tax_exempt' => $data->taxExempt,
            'orders_count' => $data->ordersCount,
            'total_spent' => $data->totalSpent,
            'currency' => $data->currency,
            'raw_payload' => $data->rawPayload,
            'shopify_created_at' => $data->shopifyCreatedAt,
            'shopify_updated_at' => $data->shopifyUpdatedAt,
        ];
    }

    private function addresses(ShopifyClient $client, array $customer): array
    {
        $addresses = $customer['addressesV2']['edges'] ?? [];
        $pageInfo = $customer['addressesV2']['pageInfo'] ?? [];
        $after = $pageInfo['endCursor'] ?? null;

        while (!empty($pageInfo['hasNextPage']) && !empty($after) && !empty($customer['id'])) {
            $response = $client->query(
                query: $this->addressesQuery(),
                variables: [
                    'id' => $customer['id'],
                    'first' => self::ADDRESS_PAGE_SIZE,
                    'after' => $after,
                ],
            );

            $connection = $response['data']['node']['addressesV2'] ?? null;

            if (!is_array($connection)) {
                break;
            }

            $addresses = array_merge($addresses, $connection['edges'] ?? []);
            $pageInfo = $connection['pageInfo'] ?? [];
            $after = $pageInfo['endCursor'] ?? null;
        }

        return $addresses;
    }

    private function syncAddresses(Store $store, Customer $customer, array $addresses): void
    {
        CustomerAddress::query()
            ->where('customer_id', $customer->id)
            ->update(['is_default' => false]);

        $localDefaultAddressId = null;

        foreach ($addresses as $addressData) {
            if (!$addressData instanceof CustomerAddressData) {
                continue;
            }

            $address = CustomerAddress::query()->updateOrCreate(
                [
                    'customer_id' => $customer->id,
                    'shopify_customer_address_id' => $addressData->shopifyAddressId,
                ],
                [
                    'store_id' => $store->id,
                    'first_name' => $addressData->firstName,
                    'last_name' => $addressData->lastName,
                    'name' => $addressData->name,
                    'company' => $addressData->company,
                    'address1' => $addressData->address1,
                    'address2' => $addressData->address2,
                    'city' => $addressData->city,
                    'province' => $addressData->province,
                    'province_code' => $addressData->provinceCode,
                    'country' => $addressData->country,
                    'country_code' => $addressData->countryCode,
                    'zip' => $addressData->zip,
                    'phone' => $addressData->phone,
                    'is_default' => $addressData->isDefault,
                    'raw_payload' => $addressData->rawPayload,
                ],
            );

            if ($addressData->isDefault) {
                $localDefaultAddressId = $address->id;
            }
        }

        if ($localDefaultAddressId !== null) {
            $customer->forceFill(['default_address_id' => $localDefaultAddressId])->save();
        }
    }

    private function customerData(ShopifyClient $client, array $node): CustomerData
    {
        $defaultAddressId = $node['defaultAddress']['id'] ?? null;

        return new CustomerData(
            shopifyCustomerId: $node['id'],
            firstName: $node['firstName'] ?? null,
            lastName: $node['lastName'] ?? null,
            displayName: $node['displayName'] ?? null,
            email: $node['email'] ?? null,
            phone: $node['phone'] ?? null,
            status: strtolower((string) ($node['state'] ?? 'active')),
            state: $node['state'] ?? null,
            tags: $node['tags'] ?? [],
            note: $node['note'] ?? null,
            verifiedEmail: (bool) ($node['verifiedEmail'] ?? false),
            taxExempt: (bool) ($node['taxExempt'] ?? false),
            ordersCount: (int) ($node['numberOfOrders'] ?? 0),
            totalSpent: (float) ($node['amountSpent']['amount'] ?? 0),
            currency: $node['amountSpent']['currencyCode'] ?? null,
            defaultAddressId: $defaultAddressId,
            shopifyCreatedAt: $node['createdAt'] ?? null,
            shopifyUpdatedAt: $node['updatedAt'] ?? null,
            rawPayload: $node,
            addresses: array_values(array_filter(array_map(
                fn (array $edge): ?CustomerAddressData => $this->addressData($edge['node'] ?? null, $defaultAddressId),
                $this->addresses($client, $node),
            ))),
        );
    }

    private function addressData(mixed $node, ?string $defaultAddressId): ?CustomerAddressData
    {
        if (!is_array($node) || empty($node['id'])) {
            return null;
        }

        return new CustomerAddressData(
            shopifyAddressId: $node['id'],
            firstName: $node['firstName'] ?? null,
            lastName: $node['lastName'] ?? null,
            name: $node['name'] ?? null,
            company: $node['company'] ?? null,
            address1: $node['address1'] ?? null,
            address2: $node['address2'] ?? null,
            city: $node['city'] ?? null,
            province: $node['province'] ?? null,
            provinceCode: $node['provinceCode'] ?? null,
            country: $node['country'] ?? null,
            countryCode: $node['countryCodeV2'] ?? null,
            zip: $node['zip'] ?? null,
            phone: $node['phone'] ?? null,
            isDefault: $defaultAddressId === $node['id'],
            rawPayload: $node,
        );
    }

    private function query(): string
    {
        return <<<'GRAPHQL'
query GetCustomers($first: Int!, $after: String, $addressFirst: Int!) {
  customers(first: $first, after: $after, sortKey: UPDATED_AT, reverse: true) {
    edges {
      node {
        ...CustomerFields
        addressesV2(first: $addressFirst) {
          edges {
            node {
              ...CustomerAddressFields
            }
          }
          pageInfo {
            hasNextPage
            endCursor
          }
        }
      }
    }
    pageInfo {
      hasNextPage
      endCursor
    }
  }
}

fragment CustomerFields on Customer {
  id
  firstName
  lastName
  displayName
  email
  phone
  state
  tags
  note
  verifiedEmail
  taxExempt
  numberOfOrders
  amountSpent {
    amount
    currencyCode
  }
  defaultAddress {
    id
  }
  createdAt
  updatedAt
}

fragment CustomerAddressFields on MailingAddress {
  id
  firstName
  lastName
  name
  company
  address1
  address2
  city
  province
  provinceCode
  country
  countryCodeV2
  zip
  phone
}
GRAPHQL;
    }

    private function addressesQuery(): string
    {
        return <<<'GRAPHQL'
query GetCustomerAddresses($id: ID!, $first: Int!, $after: String) {
  node(id: $id) {
    ... on Customer {
      addressesV2(first: $first, after: $after) {
        edges {
          node {
            ...CustomerAddressFields
          }
        }
        pageInfo {
          hasNextPage
          endCursor
        }
      }
    }
  }
}

fragment CustomerAddressFields on MailingAddress {
  id
  firstName
  lastName
  name
  company
  address1
  address2
  city
  province
  provinceCode
  country
  countryCodeV2
  zip
  phone
}
GRAPHQL;
    }
}
