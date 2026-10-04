<?php

namespace App\Modules\Shopify\AutoSync\Syncers;

use App\Modules\Catalog\Models\Collection;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\CMS\Models\Article;
use App\Modules\CMS\Models\Blog;
use App\Modules\CMS\Models\Metafield;
use App\Modules\CMS\Models\Page;
use App\Modules\Orders\Models\Order;
use App\Modules\Shopify\AutoSync\Support\Gid;
use App\Modules\Shopify\AutoSync\SyncContext;
use App\Modules\User\Models\Customer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Validation\ValidationException;

class MetafieldSyncer extends BaseSyncer
{
    /** @var array<class-string<Model>, array{0: string, 1: string}> owner class => [gid column, Shopify type] */
    private const OWNERS = [
        Product::class => ['shopify_product_id', 'Product'],
        ProductVariant::class => ['shopify_variant_id', 'ProductVariant'],
        Collection::class => ['shopify_collection_id', 'Collection'],
        Customer::class => ['shopify_customer_id', 'Customer'],
        Order::class => ['shopify_order_id', 'Order'],
        Blog::class => ['shopify_blog_id', 'Blog'],
        Article::class => ['shopify_article_id', 'Article'],
        Page::class => ['shopify_page_id', 'Page'],
    ];

    /**
     * @param Metafield $model
     */
    public function snapshot(Model $model): array
    {
        return [
            'namespace' => $model->namespace,
            'key' => $model->key,
            'owner_type' => $model->metafieldable_type,
            'owner_id' => $model->metafieldable_id,
        ];
    }

    /**
     * @param Metafield $model
     */
    public function create(Model $model, SyncContext $ctx): void
    {
        $ownerId = $this->ownerGid($model->metafieldable_type, $model->metafieldable_id, $ctx, createMissing: true);

        if (blank($model->namespace) || blank($model->key) || blank($model->type)) {
            throw ValidationException::withMessages(['shopify' => 'Metafield namespace, key and type are required by Shopify.']);
        }

        $result = $ctx->gw->mutate(<<<'GQL'
mutation MetafieldsSet($metafields: [MetafieldsSetInput!]!) {
  metafieldsSet(metafields: $metafields) {
    metafields { id }
    userErrors { field message code }
  }
}
GQL, ['metafields' => [[
            'ownerId' => $ownerId,
            'namespace' => (string) $model->namespace,
            'key' => (string) $model->key,
            'type' => (string) $model->type,
            'value' => $this->stringValue($model->value),
        ]]], 'metafieldsSet');

        $this->persist($model, ['shopify_metafield_id' => $result['metafields'][0]['id'] ?? null]);
    }

    /**
     * @param Metafield $model
     */
    public function update(Model $model, array $before, array $changed, SyncContext $ctx): void
    {
        $identityChanged = ($before['namespace'] ?? $model->namespace) !== $model->namespace
            || ($before['key'] ?? $model->key) !== $model->key
            || ($before['owner_type'] ?? $model->metafieldable_type) !== $model->metafieldable_type
            || (string) ($before['owner_id'] ?? $model->metafieldable_id) !== (string) $model->metafieldable_id;

        if ($identityChanged) {
            $this->delete($before, $ctx);
        }

        $this->create($model, $ctx);
    }

    public function delete(array $snapshot, SyncContext $ctx): void
    {
        if (blank($snapshot['namespace'] ?? null) || blank($snapshot['key'] ?? null)) {
            return;
        }

        $ownerId = $this->ownerGid($snapshot['owner_type'] ?? null, $snapshot['owner_id'] ?? null, $ctx, createMissing: false);

        if (!$ownerId) {
            return;
        }

        $this->ignoreNotFound(fn () => $ctx->gw->mutate(<<<'GQL'
mutation MetafieldsDelete($metafields: [MetafieldIdentifierInput!]!) {
  metafieldsDelete(metafields: $metafields) {
    deletedMetafields { key namespace ownerId }
    userErrors { field message }
  }
}
GQL, ['metafields' => [[
            'ownerId' => $ownerId,
            'namespace' => (string) $snapshot['namespace'],
            'key' => (string) $snapshot['key'],
        ]]], 'metafieldsDelete'));
    }

    /**
     * Resolves the Shopify owner of the metafield. No owner means a shop-level metafield.
     */
    private function ownerGid(?string $type, mixed $id, SyncContext $ctx, bool $createMissing): ?string
    {
        if (blank($type) || blank($id)) {
            return $ctx->gw->query('query { shop { id } }')['shop']['id'] ?? null;
        }

        $class = Relation::getMorphedModel($type) ?? $type;
        [$column, $shopifyType] = self::OWNERS[$class] ?? [null, null];
        $owner = $column && class_exists($class) ? $class::query()->find($id) : null;

        if (!$owner) {
            if ($createMissing) {
                throw ValidationException::withMessages(['shopify' => "Metafield owner [{$type}] cannot be synced to Shopify."]);
            }

            return null;
        }

        if ($createMissing) {
            $ctx->ensureCreated($owner, $column);
        }

        return Gid::make($owner->{$column}, $shopifyType);
    }

    private function stringValue(mixed $value): string
    {
        return match (true) {
            is_array($value) => (string) json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            is_bool($value) => $value ? 'true' : 'false',
            default => (string) $value,
        };
    }
}
