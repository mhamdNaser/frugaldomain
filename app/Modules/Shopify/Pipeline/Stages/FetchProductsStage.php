<?php

namespace App\Modules\Shopify\Pipeline\Stages;

use App\Modules\Shopify\Services\ShopifyClient;
use App\Modules\Stores\Models\Store;
use App\Modules\Shopify\Exceptions\ShopifySyncException;

class FetchProductsStage
{
    /**
     * جلب المنتجات من Shopify فقط (Raw GraphQL Response)
     *
     * @return array<string, mixed>
     *
     * @throws ShopifySyncException
     */
    public function handle(Store $store, int $first = 20, ?string $after = null): array
    {
        $client = new ShopifyClient($store);

        $response = $client->query(
            query: $this->buildProductsQuery(),
            variables: $this->buildVariables($first, $after),
        );

        $productsData = $response['data']['products'] ?? null;

        if (!is_array($productsData)) {
            throw new ShopifySyncException(
                message: 'Invalid Shopify products response structure.',
                context: [
                    'store_id' => $store->id,
                    'response' => $response,
                ]
            );
        }

        return [
            'edges' => $productsData['edges'] ?? [],
            'pageInfo' => $productsData['pageInfo'] ?? [
                'hasNextPage' => false,
                'hasPreviousPage' => false,
                'endCursor' => null,
                'startCursor' => null,
            ],
            'raw' => $productsData,
        ];
    }

    /**
     * Build GraphQL variables
     */
    private function buildVariables(int $first, ?string $after = null): array
    {
        $variables = [
            'first' => $first,
        ];

        if (!empty($after)) {
            $variables['after'] = $after;
        }

        return $variables;
    }

    /**
     * Shopify Products GraphQL Query
     */
    private function buildProductsQuery(): string
    {
        return <<<'GRAPHQL'
query GetProducts($first: Int!, $after: String) {
  products(first: $first, after: $after) {
    edges {
      cursor
      node {
        id
        title
        description
        handle
        vendor
        productType
        status
        tags
        isGiftCard
        hasOnlyDefaultVariant
        publishedAt
        createdAt
        updatedAt
        onlineStoreUrl

        featuredImage {
          id
          url
          altText
        }

        seo {
          title
          description
        }

        category {
          id
          name
        }

        options {
          id
          name
          position
          optionValues {
            id
            name
            swatch {
              color
              image {
                image {
                  url
                }
              }
            }
          }
        }
      }
    }

    pageInfo {
      hasNextPage
      hasPreviousPage
      startCursor
      endCursor
    }
  }
}
GRAPHQL;
    }
}
