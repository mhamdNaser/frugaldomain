<?php

namespace App\Modules\Shopify\Services\Sync;

use App\Modules\Shopify\DTOs\CustomerMarketingConsentData;
use App\Modules\Shopify\Services\ShopifyClient;
use App\Modules\Stores\Models\Store;
use App\Modules\User\Models\Customer;
use App\Modules\User\Models\CustomerMarketingConsent;

class CustomerMarketingConsentSyncService
{
    private const PAGE_SIZE = 100;

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
                ])
            );

            $connection = $response['data']['customers'] ?? null;
            if (!is_array($connection)) {
                break;
            }

            foreach ($connection['edges'] ?? [] as $edge) {
                $node = $edge['node'] ?? null;
                if (!is_array($node) || empty($node['id'])) {
                    continue;
                }

                $data = $this->map($node);
                $this->persist($store, $data);
                $count++;
            }

            $pageInfo = $connection['pageInfo'] ?? [];
            $after = $pageInfo['endCursor'] ?? null;
        } while (!empty($pageInfo['hasNextPage']) && !empty($after));

        return $count;
    }

    private function persist(Store $store, CustomerMarketingConsentData $data): void
    {
        $customerId = Customer::query()
            ->where('store_id', $store->id)
            ->where('shopify_customer_id', $data->shopifyCustomerId)
            ->value('id');

        CustomerMarketingConsent::query()->updateOrCreate(
            [
                'store_id' => $store->id,
                'shopify_customer_id' => $data->shopifyCustomerId,
            ],
            [
                'customer_id' => $customerId,
                'email_marketing_state' => $data->emailMarketingState,
                'email_marketing_opt_in_level' => $data->emailMarketingOptInLevel,
                'email_consent_updated_at' => $data->emailConsentUpdatedAt,
                'sms_marketing_state' => $data->smsMarketingState,
                'sms_marketing_opt_in_level' => $data->smsMarketingOptInLevel,
                'sms_consent_updated_at' => $data->smsConsentUpdatedAt,
                'source_location_id' => $data->sourceLocationId,
                'raw_payload' => $data->rawPayload,
            ]
        );
    }

    private function map(array $node): CustomerMarketingConsentData
    {
        return new CustomerMarketingConsentData(
            shopifyCustomerId: (string) $node['id'],
            emailMarketingState: $node['emailMarketingConsent']['marketingState'] ?? null,
            emailMarketingOptInLevel: $node['emailMarketingConsent']['marketingOptInLevel'] ?? null,
            emailConsentUpdatedAt: $node['emailMarketingConsent']['consentUpdatedAt'] ?? null,
            smsMarketingState: $node['smsMarketingConsent']['marketingState'] ?? null,
            smsMarketingOptInLevel: $node['smsMarketingConsent']['marketingOptInLevel'] ?? null,
            smsConsentUpdatedAt: $node['smsMarketingConsent']['consentUpdatedAt'] ?? null,
            sourceLocationId: $node['smsMarketingConsent']['sourceLocation']['id'] ?? ($node['emailMarketingConsent']['sourceLocation']['id'] ?? null),
            rawPayload: $node,
        );
    }

    private function query(): string
    {
        return <<<'GRAPHQL'
query SyncCustomerMarketingConsent($first: Int!, $after: String) {
  customers(first: $first, after: $after) {
    edges {
      node {
        id
        emailMarketingConsent {
          marketingState
          marketingOptInLevel
          consentUpdatedAt
          sourceLocation {
            id
          }
        }
        smsMarketingConsent {
          marketingState
          marketingOptInLevel
          consentUpdatedAt
          sourceLocation {
            id
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
GRAPHQL;
    }
}

