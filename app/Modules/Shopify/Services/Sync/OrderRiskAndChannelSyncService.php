<?php

namespace App\Modules\Shopify\Services\Sync;

use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\OrderChannel;
use App\Modules\Orders\Models\OrderRisk;
use App\Modules\Shopify\DTOs\OrderRiskData;
use App\Modules\Shopify\Services\ShopifyClient;
use App\Modules\Stores\Models\Store;

class OrderRiskAndChannelSyncService
{
    private const PAGE_SIZE = 50;

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

            $connection = $response['data']['orders'] ?? null;
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

    private function persist(Store $store, OrderRiskData $data): void
    {
        $order = Order::query()
            ->where('store_id', $store->id)
            ->where('shopify_order_id', $data->shopifyOrderId)
            ->first();

        if (!$order) {
            return;
        }

        OrderChannel::query()->updateOrCreate(
            [
                'store_id' => $store->id,
                'shopify_order_id' => $data->shopifyOrderId,
            ],
            [
                'order_id' => $order->id,
                'source_name' => $data->channel['source_name'] ?? null,
                'source_identifier' => $data->channel['source_identifier'] ?? null,
                'channel_id' => $data->channel['channel_id'] ?? null,
                'channel_name' => $data->channel['channel_name'] ?? null,
                'app_id' => $data->channel['app_id'] ?? null,
                'app_title' => $data->channel['app_title'] ?? null,
                'raw_payload' => $data->channel['raw_payload'] ?? null,
            ]
        );

        foreach ($data->assessments as $assessment) {
            OrderRisk::query()->updateOrCreate(
                [
                    'store_id' => $store->id,
                    'shopify_order_id' => $data->shopifyOrderId,
                    'assessment_id' => $assessment['assessment_id'] ?? null,
                ],
                [
                    'order_id' => $order->id,
                    'recommendation' => $data->recommendation,
                    'risk_level' => $assessment['risk_level'] ?? $data->riskLevel,
                    'provider' => $assessment['provider'] ?? null,
                    'assessed_at' => $assessment['assessed_at'] ?? null,
                    'facts' => $assessment['facts'] ?? [],
                    'raw_payload' => $assessment['raw_payload'] ?? [],
                ]
            );
        }
    }

    private function map(array $node): OrderRiskData
    {
        $assessments = array_map(function (array $assessment): array {
            return [
                'assessment_id' => $assessment['id'] ?? md5(json_encode($assessment)),
                'risk_level' => $assessment['riskLevel'] ?? null,
                'provider' => $assessment['provider']['title'] ?? null,
                'assessed_at' => null,
                'facts' => $assessment['facts'] ?? [],
                'raw_payload' => $assessment,
            ];
        }, $node['risk']['assessments'] ?? []);

        return new OrderRiskData(
            shopifyOrderId: (string) $node['id'],
            recommendation: $node['risk']['recommendation'] ?? null,
            riskLevel: $assessments[0]['risk_level'] ?? null,
            assessments: $assessments,
            channel: [
                'source_name' => $node['sourceName'] ?? null,
                'source_identifier' => $node['sourceIdentifier'] ?? null,
                'channel_id' => $node['channelInformation']['channelId'] ?? null,
                'channel_name' => $node['channelInformation']['displayName'] ?? null,
                'app_id' => $node['channelInformation']['app']['id'] ?? null,
                'app_title' => $node['channelInformation']['app']['title'] ?? null,
                'raw_payload' => $node['channelInformation'] ?? [],
            ],
            rawPayload: $node,
        );
    }

    private function query(): string
    {
        return <<<'GRAPHQL'
query SyncOrderRisk($first: Int!, $after: String) {
  orders(first: $first, after: $after, sortKey: UPDATED_AT, reverse: true) {
    edges {
      node {
        id
        sourceName
        sourceIdentifier
        channelInformation {
          channelId
          displayName
          app {
            id
            title
          }
        }
        risk {
          recommendation
          assessments {
            riskLevel
            provider {
              ... on App {
                title
              }
            }
            facts {
              description
              sentiment
            }
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
