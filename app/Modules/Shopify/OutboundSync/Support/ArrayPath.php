<?php

namespace App\Modules\Shopify\OutboundSync\Support;

final class ArrayPath
{
    /**
     * @param array<string, mixed> $data
     */
    public static function get(array $data, ?string $path, mixed $default = null): mixed
    {
        if (!$path) {
            return $default;
        }

        $segments = array_values(array_filter(explode('.', $path), static fn ($segment) => $segment !== ''));
        $current = $data;

        foreach ($segments as $segment) {
            if (!is_array($current) || !array_key_exists($segment, $current)) {
                return $default;
            }

            $current = $current[$segment];
        }

        return $current;
    }
}

