<?php

namespace App\Modules\Shopify\AutoSync\Syncers;

use App\Modules\Catalog\Models\Collection;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\CMS\Models\File;
use App\Modules\Shopify\AutoSync\Support\Gid;
use App\Modules\Shopify\AutoSync\SyncContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

/**
 * Links Shopify files (media library) to products, variants and collections.
 */
class FileMediaSyncer
{
    public function attach(File $file, Model $owner, SyncContext $ctx): void
    {
        match (true) {
            $owner instanceof Collection => $this->attachToCollection($file, $owner, $ctx),
            $owner instanceof ProductVariant => $this->attachToVariant($file, $owner, $ctx),
            $owner instanceof Product => $this->attachToProduct($file, $owner, $ctx),
            default => null,
        };
    }

    private function attachToProduct(File $file, Product $product, SyncContext $ctx): string
    {
        $ctx->ensureCreated($product, 'shopify_product_id');
        $productGid = Gid::make($product->shopify_product_id, 'Product');
        $fileGid = $this->fileGid($file, $ctx);

        $ctx->gw->mutate(<<<'GQL'
mutation FileLink($files: [FileUpdateInput!]!) {
  fileUpdate(files: $files) {
    files { id }
    userErrors { field message code }
  }
}
GQL, ['files' => [['id' => $fileGid, 'referencesToAdd' => [$productGid]]]], 'fileUpdate');

        return $fileGid;
    }

    private function attachToVariant(File $file, ProductVariant $variant, SyncContext $ctx): void
    {
        $product = $variant->product;
        $fileGid = $this->attachToProduct($file, $product, $ctx);
        $ctx->ensureCreated($variant, 'shopify_variant_id');

        $ctx->gw->mutate(<<<'GQL'
mutation VariantMedia($productId: ID!, $variantMedia: [ProductVariantAppendMediaInput!]!) {
  productVariantAppendMedia(productId: $productId, variantMedia: $variantMedia) {
    product { id }
    userErrors { field message code }
  }
}
GQL, [
            'productId' => Gid::make($product->fresh()->shopify_product_id, 'Product'),
            'variantMedia' => [[
                'variantId' => Gid::make($variant->shopify_variant_id, 'ProductVariant'),
                'mediaIds' => [$fileGid],
            ]],
        ], 'productVariantAppendMedia');
    }

    private function attachToCollection(File $file, Collection $collection, SyncContext $ctx): void
    {
        $ctx->ensureCreated($collection, 'shopify_collection_id');
        $source = $this->shopifyUrl($file);

        $ctx->gw->mutate(<<<'GQL'
mutation CollectionImage($input: CollectionInput!) {
  collectionUpdate(input: $input) {
    collection { id }
    userErrors { field message }
  }
}
GQL, ['input' => [
            'id' => Gid::make($collection->shopify_collection_id, 'Collection'),
            'image' => ['src' => $source, 'altText' => $file->altText ?: $collection->title],
        ]], 'collectionUpdate');
    }

    /**
     * Files uploaded through "upload-to-shopify" already have a Shopify id; others are
     * created in the Shopify media library from their public URL first.
     */
    private function fileGid(File $file, SyncContext $ctx): string
    {
        $meta = is_array($file->meta) ? $file->meta : [];

        if (filled($file->shopify_id)) {
            return Gid::make((string) $file->shopify_id, (string) ($meta['__typename'] ?? 'MediaImage'));
        }

        $source = $meta['source_url'] ?? $file->url;

        if (!is_string($source) || !preg_match('#^https?://#', $source) || preg_match('#//(localhost|127\.0\.0\.1)#', $source)) {
            throw ValidationException::withMessages(['shopify' => 'This file is not in Shopify. Upload it with "upload to Shopify" first.']);
        }

        $result = $ctx->gw->mutate(<<<'GQL'
mutation FileCreate($files: [FileCreateInput!]!) {
  fileCreate(files: $files) {
    files { id __typename }
    userErrors { field message code }
  }
}
GQL, ['files' => [[
            'originalSource' => $source,
            'contentType' => str_starts_with((string) $file->mime_type, 'video/') ? 'VIDEO' : 'IMAGE',
            'alt' => $file->altText,
        ]]], 'fileCreate');

        $created = $result['files'][0] ?? [];
        $file->forceFill([
            'shopify_id' => Gid::numeric($created['id'] ?? null),
            'meta' => array_merge($meta, ['__typename' => $created['__typename'] ?? 'MediaImage', 'source_url' => $source]),
        ])->saveQuietly();

        return (string) $created['id'];
    }

    private function shopifyUrl(File $file): string
    {
        $meta = is_array($file->meta) ? $file->meta : [];
        $url = data_get($meta, 'source_url') ?? data_get($meta, 'image.url') ?? $file->url;

        if (!is_string($url) || preg_match('#//(localhost|127\.0\.0\.1)#', $url)) {
            throw ValidationException::withMessages(['shopify' => 'This file has no public URL Shopify can download.']);
        }

        return $url;
    }
}
