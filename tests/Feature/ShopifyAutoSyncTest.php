<?php

use App\Modules\Catalog\Models\Collection;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\Vendor;
use App\Modules\Shopify\AutoSync\ShopifyAutoSync;
use App\Modules\Shopify\OutboundSync\Jobs\ProcessOutboundSyncJob;
use App\Modules\Stores\Models\Store;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

// The full migration set is MySQL-only, so load just the tables this flow touches.
beforeEach(function () {
    $m = fn ($module, $file) => base_path("app/Modules/{$module}/database/migrations/{$file}.php");
    Artisan::call('migrate', ['--realpath' => true, '--path' => [
        $m('User', '0001_01_01_000006_create_users_table'),
        $m('Billing', '2026_02_26_091148_create_plans_table'),
        $m('Stores', '2026_02_26_091149_create_stores_table'),
        $m('Catalog', '2026_02_26_091505_create_vendors_table'),
        $m('Catalog', '2026_02_26_091506_create_product_types_table'),
        $m('Catalog', '2026_02_26_091522_create_categories_table'),
        $m('Catalog', '2026_02_26_091530_create_products_table'),
        $m('Catalog', '2026_02_26_091703_create_collections_table'),
        $m('Catalog', '2026_02_26_091740_create_collection_products_table'),
        $m('Catalog', '2026_02_26_091520_create_options_table'),
        $m('Catalog', '2026_03_31_091008_create_product_options_table'),
        $m('Shopify', '2026_04_25_170000_create_shopify_outbound_syncs_table'),
        $m('Shopify', '2026_04_25_170100_create_shopify_outbound_sync_attempts_table'),
    ]]);

    $this->store = Store::query()->create([
        'id' => (string) Str::uuid(),
        'shopify_domain' => 'demo.myshopify.com',
        'shopify_access_token' => Crypt::encryptString('shpat_test'),
        'name' => 'Demo',
    ]);
});

/**
 * Fakes the Shopify GraphQL endpoint: picks the response by the operation name it finds
 * in the query, and records every call.
 *
 * @param array<string, array|callable> $responses
 */
function fakeShopifyGraphql(array $responses): void
{
    Http::fake(['*/admin/api/*/graphql.json' => function (Request $request) use ($responses) {
        $query = (string) $request['query'];

        foreach ($responses as $needle => $response) {
            if (str_contains($query, $needle)) {
                return is_callable($response) ? $response($request) : Http::response($response);
            }
        }

        return Http::response(['data' => []]);
    }]);
}

function sentShopifyQueries(string $needle): array
{
    return collect(Http::recorded())
        ->filter(fn ($pair) => str_contains((string) $pair[0]['query'], $needle))
        ->map(fn ($pair) => $pair[0]->data())
        ->values()
        ->all();
}

it('creates a new collection in Shopify, stores its id and publishes it', function () {
    fakeShopifyGraphql([
        'collectionCreate' => ['data' => ['collectionCreate' => [
            'collection' => ['id' => 'gid://shopify/Collection/101', 'handle' => 'summer'],
            'userErrors' => [],
        ]]],
        'publications(' => ['data' => ['publications' => ['nodes' => [
            ['id' => 'gid://shopify/Publication/9', 'catalog' => ['title' => 'Online Store']],
        ]]]],
        'publishablePublish' => ['data' => ['publishablePublish' => ['userErrors' => []]]],
    ]);

    $sync = app(ShopifyAutoSync::class);
    $collection = $sync->create(fn () => Collection::query()->create([
        'store_id' => $this->store->id,
        'title' => 'Summer',
        'description' => '<p>Hot</p>',
    ]));

    expect($collection->fresh()->shopify_collection_id)->toBe('gid://shopify/Collection/101')
        ->and($collection->fresh()->handle)->toBe('summer')
        ->and($sync->report()['status'])->toBe('synced');

    $create = sentShopifyQueries('collectionCreate')[0];
    expect($create['variables']['input'])->toMatchArray(['title' => 'Summer', 'descriptionHtml' => '<p>Hot</p>']);

    $publish = sentShopifyQueries('publishablePublish')[0];
    expect($publish['variables'])->toBe([
        'id' => 'gid://shopify/Collection/101',
        'input' => [['publicationId' => 'gid://shopify/Publication/9']],
    ]);
});

it('rolls the local record back when Shopify rejects the data', function () {
    fakeShopifyGraphql([
        'collectionCreate' => ['data' => ['collectionCreate' => [
            'collection' => null,
            'userErrors' => [['field' => ['input', 'handle'], 'message' => 'Handle has already been taken']],
        ]]],
    ]);

    expect(fn () => app(ShopifyAutoSync::class)->create(fn () => Collection::query()->create([
        'store_id' => $this->store->id,
        'title' => 'Duplicate',
        'handle' => 'taken',
    ])))->toThrow(ValidationException::class, 'Handle has already been taken');

    expect(Collection::query()->count())->toBe(0);
});

it('keeps the change and queues a retry when Shopify is unreachable', function () {
    Queue::fake();
    Http::fake(['*' => Http::response('Service unavailable', 503)]);

    $sync = app(ShopifyAutoSync::class);
    $collection = $sync->create(fn () => Collection::query()->create([
        'store_id' => $this->store->id,
        'title' => 'Later',
    ]));

    expect(Collection::query()->find($collection->id))->not->toBeNull()
        ->and($sync->report()['status'])->toBe('queued');

    $row = DB::table('shopify_outbound_syncs')->first();
    expect($row->entity_type)->toBe('Collection')
        ->and($row->action)->toBe('create')
        ->and(json_decode($row->payload, true)['auto_sync']['id'])->toBe($collection->id);

    Queue::assertPushed(ProcessOutboundSyncJob::class);
});

it('re-pushes linked products when a vendor is renamed', function () {
    fakeShopifyGraphql([
        'productUpdate' => ['data' => ['productUpdate' => ['product' => ['id' => 'x'], 'userErrors' => []]]],
    ]);

    $vendor = Vendor::query()->create(['store_id' => $this->store->id, 'name' => 'Old Co', 'slug' => 'old-co']);
    Product::query()->create([
        'store_id' => $this->store->id, 'title' => 'Shirt', 'slug' => 'shirt', 'handle' => 'shirt',
        'vendor_id' => $vendor->id, 'shopify_product_id' => 'gid://shopify/Product/55',
    ]);
    Product::query()->create([
        'store_id' => $this->store->id, 'title' => 'Local only', 'slug' => 'local', 'handle' => 'local',
        'vendor_id' => $vendor->id,
    ]);

    app(ShopifyAutoSync::class)->update($vendor, ['name'], function () use ($vendor) {
        $vendor->update(['name' => 'New Co']);

        return $vendor;
    });

    $updates = sentShopifyQueries('productUpdate');
    expect($updates)->toHaveCount(1)
        ->and($updates[0]['variables']['product'])->toBe(['id' => 'gid://shopify/Product/55', 'vendor' => 'New Co']);
});

it('sends collection membership changes with the product update', function () {
    fakeShopifyGraphql([
        'productUpdate' => ['data' => ['productUpdate' => ['product' => ['id' => 'x'], 'userErrors' => []]]],
    ]);

    $old = Collection::query()->create(['store_id' => $this->store->id, 'title' => 'Old', 'shopify_collection_id' => 'gid://shopify/Collection/1']);
    $new = Collection::query()->create(['store_id' => $this->store->id, 'title' => 'New', 'shopify_collection_id' => 'gid://shopify/Collection/2']);
    $product = Product::query()->create([
        'store_id' => $this->store->id, 'title' => 'Hat', 'slug' => 'hat', 'handle' => 'hat',
        'shopify_product_id' => 'gid://shopify/Product/77',
    ]);
    $product->collections()->attach($old->id, ['store_id' => $this->store->id]);

    app(ShopifyAutoSync::class)->update($product, ['collection_ids'], function () use ($product, $new) {
        $product->collections()->sync([$new->id => ['store_id' => $product->store_id]]);

        return $product;
    });

    expect(sentShopifyQueries('productUpdate')[0]['variables']['product'])->toBe([
        'id' => 'gid://shopify/Product/77',
        'collectionsToJoin' => ['gid://shopify/Collection/2'],
        'collectionsToLeave' => ['gid://shopify/Collection/1'],
    ]);
});

it('deletes the Shopify product together with the local one', function () {
    fakeShopifyGraphql([
        'productDelete' => ['data' => ['productDelete' => ['deletedProductId' => 'gid://shopify/Product/9', 'userErrors' => []]]],
    ]);

    $product = Product::query()->create([
        'store_id' => $this->store->id, 'title' => 'Bag', 'slug' => 'bag', 'handle' => 'bag',
        'shopify_product_id' => '9',
    ]);

    app(ShopifyAutoSync::class)->delete($product, fn () => $product->delete());

    expect(sentShopifyQueries('productDelete')[0]['variables'])->toBe(['input' => ['id' => 'gid://shopify/Product/9']]);
});

it('only saves locally when the store is not connected to Shopify', function () {
    Http::fake();
    $this->store->update(['shopify_access_token' => null]);

    $sync = app(ShopifyAutoSync::class);
    $sync->create(fn () => Collection::query()->create(['store_id' => $this->store->id, 'title' => 'Offline']));

    expect($sync->report()['status'])->toBe('skipped');
    Http::assertNothingSent();
});

it('finishes a queued push once Shopify is reachable again', function () {
    Queue::fake();
    Http::fake(['*' => Http::response('Service unavailable', 503)]);

    $collection = app(ShopifyAutoSync::class)->create(fn () => Collection::query()->create([
        'store_id' => $this->store->id,
        'title' => 'Retry me',
        'is_active' => false,
    ]));
    $outboundId = (int) DB::table('shopify_outbound_syncs')->value('id');

    Http::swap(new \Illuminate\Http\Client\Factory());
    fakeShopifyGraphql([
        'collectionCreate' => ['data' => ['collectionCreate' => [
            'collection' => ['id' => 'gid://shopify/Collection/202', 'handle' => 'retry-me'],
            'userErrors' => [],
        ]]],
    ]);

    app(\App\Modules\Shopify\OutboundSync\Services\OutboundSyncProcessor::class)->process($outboundId);

    expect($collection->fresh()->shopify_collection_id)->toBe('gid://shopify/Collection/202')
        ->and(DB::table('shopify_outbound_syncs')->value('status'))->toBe('synced');
});
