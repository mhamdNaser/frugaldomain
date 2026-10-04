<?php

namespace App\Modules\Shopify\AutoSync\Support;

/**
 * Helpers for Shopify global IDs (gid://shopify/Type/123).
 * Local tables store either the full GID or only the numeric part.
 */
final class Gid
{
    public static function make(mixed $raw, string $type): ?string
    {
        if (!is_scalar($raw)) {
            return null;
        }

        $raw = trim((string) $raw);

        if ($raw === '') {
            return null;
        }

        if (str_starts_with($raw, 'gid://')) {
            return $raw;
        }

        return ctype_digit($raw) ? "gid://shopify/{$type}/{$raw}" : null;
    }

    public static function numeric(?string $gid): ?string
    {
        if (!is_string($gid) || $gid === '') {
            return null;
        }

        $last = basename(strtok($gid, '?'));

        return ctype_digit($last) ? $last : null;
    }
}
