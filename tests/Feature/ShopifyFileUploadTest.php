<?php

use App\Modules\Catalog\Models\Product;
use App\Modules\CMS\Models\File;
use App\Modules\Stores\Models\Store;
use App\Modules\User\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

// The full migration set is MySQL-only, so load just the tables this flow touches.
beforeEach(function () {
    $m = fn ($module, $file) => base_path("app/Modules/{$module}/database/migrations/{$file}.php");
    Artisan::call('migrate', ['--realpath' => true, '--path' => [
        $m('Locale', '0001_01_01_000003_create_countries_table'),
        $m('Locale', '0001_01_01_000004_create_states_table'),
        $m('Locale', '0001_01_01_000005_create_cities_table'),
        $m('User', '0001_01_01_000006_create_users_table'),
        $m('User', '2026_09_29_120000_allow_self_registration_on_users_table'),
        $m('User', '2025_10_04_075133_create_personal_access_tokens_table'),
        $m('User', '2025_10_11_123012_create_permission_tables'),
        $m('Billing', '2026_02_26_091148_create_plans_table'),
        $m('Stores', '2026_02_26_091149_create_stores_table'),
        $m('Catalog', '2026_02_26_091505_create_vendors_table'),
        $m('Catalog', '2026_02_26_091506_create_product_types_table'),
        $m('Catalog', '2026_02_26_091522_create_categories_table'),
        $m('Catalog', '2026_02_26_091530_create_products_table'),
        $m('CMS', '2026_03_31_091738_create_files_table'),
        $m('CMS', '2026_04_18_100000_widen_files_paths_for_shopify_files_sync'),
    ]]);
    Role::findOrCreate('partner', 'web');
    Storage::fake('public');

    $this->owner = User::create([
        'first_name' => 'Store', 'last_name' => 'Owner', 'name' => 'owner',
        'email' => 'owner@test.com', 'password' => 'x', 'status' => 1,
    ]);
    $this->owner->assignRole('partner');

    $this->store = Store::query()->create([
        'id' => (string) Str::uuid(),
        'owner_id' => $this->owner->id,
        'shopify_domain' => 'demo.myshopify.com',
        'shopify_access_token' => Crypt::encryptString('shpat_test'),
        'name' => 'Demo',
    ]);
});

/** A real 1x1 PNG, so the upload passes the mimes rule without the GD extension. */
function fakePng(string $name): UploadedFile
{
    return UploadedFile::fake()->createWithContent($name, base64_decode(
        'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='
    ));
}

/**
 * @param array<string, mixed> $node the file as Shopify returns it once processed
 */
function fakeShopifyFileUpload(array $node): void
{
    Http::fake([
        'staged.example.com/*' => Http::response('', 201),
        'cdn.shopify.com/*' => Http::response('fake-image-bytes', 200, ['Content-Type' => 'image/png']),
        '*/admin/api/*/graphql.json' => function (Request $request) use ($node) {
            $query = (string) $request['query'];

            return Http::response(match (true) {
                str_contains($query, 'stagedUploadsCreate') => ['data' => ['stagedUploadsCreate' => [
                    'stagedTargets' => [[
                        'url' => 'https://staged.example.com/upload',
                        'resourceUrl' => 'https://staged.example.com/upload/abc',
                        'parameters' => [['name' => 'key', 'value' => 'abc']],
                    ]],
                    'userErrors' => [],
                ]]],
                // Shopify answers before the image is processed: no URL yet.
                str_contains($query, 'fileCreate') => ['data' => ['fileCreate' => [
                    'files' => [['__typename' => 'MediaImage', 'id' => $node['id'], 'alt' => 'shirt', 'image' => null]],
                    'userErrors' => [],
                ]]],
                str_contains($query, 'FileStatus') => ['data' => ['node' => $node]],
                str_contains($query, 'fileUpdate') => ['data' => ['fileUpdate' => ['files' => [['id' => $node['id']]], 'userErrors' => []]]],
                default => ['data' => []],
            });
        },
    ]);
}

it('uploads an image to Shopify and waits until it has a URL', function () {
    fakeShopifyFileUpload([
        '__typename' => 'MediaImage',
        'id' => 'gid://shopify/MediaImage/555',
        'alt' => 'shirt',
        'fileStatus' => 'READY',
        'mimeType' => 'image/png',
        'image' => ['url' => 'https://cdn.shopify.com/s/files/shirt.png', 'width' => 800, 'height' => 600],
    ]);

    $this->actingAs($this->owner)
        ->post('/api/admin/files/upload-to-shopify', [
            'file' => fakePng('shirt.png'),
            'title' => 'shirt',
        ], ['Accept' => 'application/json'])
        ->assertCreated()
        ->assertJsonPath('data.width', 800);

    $file = File::query()->sole();
    expect((string) $file->shopify_id)->toBe('555')
        ->and($file->type)->toBe('image')
        ->and($file->size)->toBeGreaterThan(0)
        ->and($file->meta['source_url'])->toBe('https://cdn.shopify.com/s/files/shirt.png')
        ->and($file->url)->not->toBeEmpty();

    Storage::disk('public')->assertExists($file->path);
});

it('links an uploaded image to the product in Shopify', function () {
    fakeShopifyFileUpload([
        '__typename' => 'MediaImage',
        'id' => 'gid://shopify/MediaImage/556',
        'fileStatus' => 'READY',
        'image' => ['url' => 'https://cdn.shopify.com/s/files/hat.png', 'width' => 10, 'height' => 10],
    ]);

    $product = Product::query()->create([
        'store_id' => $this->store->id, 'title' => 'Hat', 'slug' => 'hat', 'handle' => 'hat',
        'shopify_product_id' => 'gid://shopify/Product/77',
    ]);

    $this->actingAs($this->owner)
        ->post('/api/admin/files/upload-to-shopify', [
            'file' => fakePng('hat.png'),
            'owner_type' => 'product',
            'owner_id' => $product->id,
            'role' => 'product_image',
        ], ['Accept' => 'application/json'])
        ->assertCreated();

    $file = File::query()->sole();
    expect($file->fileable_id)->toBe($product->id)
        ->and($file->role)->toBe('product_image');

    Http::assertSent(fn (Request $request) => str_contains((string) ($request->data()['query'] ?? ''), 'fileUpdate')
        && $request['variables']['files'][0] === [
            'id' => 'gid://shopify/MediaImage/556',
            'referencesToAdd' => ['gid://shopify/Product/77'],
        ]);
});

it('returns a readable error when Shopify cannot process the file', function () {
    fakeShopifyFileUpload([
        '__typename' => 'MediaImage',
        'id' => 'gid://shopify/MediaImage/557',
        'fileStatus' => 'FAILED',
        'fileErrors' => [['code' => 'UNSUPPORTED_IMAGE_FILE_TYPE', 'message' => 'Image format is not supported']],
    ]);

    $this->actingAs($this->owner)
        ->post('/api/admin/files/upload-to-shopify', [
            'file' => fakePng('broken.png'),
        ], ['Accept' => 'application/json'])
        ->assertStatus(422)
        ->assertJsonPath('errors.file.0', 'Shopify upload failed: Image format is not supported');

    expect(File::query()->count())->toBe(0);
});

it('rejects file types Shopify does not accept', function () {
    Http::fake();

    $this->actingAs($this->owner)
        ->post('/api/admin/files/upload-to-shopify', [
            'file' => UploadedFile::fake()->create('script.exe', 10, 'application/octet-stream'),
        ], ['Accept' => 'application/json'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('file');

    Http::assertNothingSent();
});
