<?php

namespace App\Modules\Shopify\AutoSync\Syncers;

use App\Modules\Shopify\AutoSync\Contracts\EntitySyncer;
use App\Modules\Shopify\AutoSync\Exceptions\ShopifyUserErrorsException;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

abstract class BaseSyncer implements EntitySyncer
{
    public function snapshot(Model $model): array
    {
        return $model->getAttributes();
    }

    /**
     * On create, true for every field; on update, true only when one of the keys was sent.
     *
     * @param array<int, string>|null $changed null means "create" (everything)
     */
    protected function touched(?array $changed, string ...$keys): bool
    {
        return $changed === null || array_intersect($keys, $changed) !== [];
    }

    /**
     * Runs a Shopify delete and ignores "not found" errors (already deleted in Shopify).
     */
    protected function ignoreNotFound(Closure $call): void
    {
        try {
            $call();
        } catch (ShopifyUserErrorsException $e) {
            if (!$e->isNotFound()) {
                throw $e;
            }
        }
    }

    /**
     * Shopify downloads images itself, so only publicly reachable URLs can be sent.
     */
    protected function isPublicUrl(?string $url): bool
    {
        if (!is_string($url) || !preg_match('#^https?://#i', $url)) {
            return false;
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        return $host !== ''
            && !in_array($host, ['localhost', '127.0.0.1', '0.0.0.0'], true)
            && !str_ends_with($host, '.test')
            && !str_ends_with($host, '.local');
    }

    /**
     * SEO title/description for blogs, articles and pages live in the "global" metafields.
     *
     * @return array<int, array<string, string>>
     */
    protected function seoMetafields(?string $title, ?string $description): array
    {
        $metafields = [];

        if (filled($title)) {
            $metafields[] = ['namespace' => 'global', 'key' => 'title_tag', 'type' => 'single_line_text_field', 'value' => (string) $title];
        }

        if (filled($description)) {
            $metafields[] = ['namespace' => 'global', 'key' => 'description_tag', 'type' => 'multi_line_text_field', 'value' => (string) $description];
        }

        return $metafields;
    }

    protected function isoDate(mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        return Carbon::parse($value)->toIso8601String();
    }

    /**
     * @return array<int, string>
     */
    protected function tagList(mixed $tags): array
    {
        if (is_string($tags)) {
            $decoded = json_decode($tags, true);
            $tags = is_array($decoded) ? $decoded : explode(',', $tags);
        }

        if (!is_array($tags)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map(
            fn ($tag) => trim(is_array($tag) ? (string) ($tag['name'] ?? '') : (string) $tag),
            $tags,
        ), fn ($tag) => $tag !== '')));
    }

    /**
     * Saves the Shopify id on the local row without firing model events.
     *
     * @param array<string, mixed> $attributes
     */
    protected function persist(Model $model, array $attributes): void
    {
        $model->forceFill(array_filter($attributes, fn ($value) => $value !== null))->saveQuietly();
    }
}
