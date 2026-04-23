<?php

namespace App\Modules\Shopify\Support;

class ShopifyHelper
{
    public static function extractId(?string $gid): ?string
    {
        if (!is_string($gid) || $gid === '') {
            return null;
        }
        $segments = explode('/', $gid);
        $lastSegment = end($segments);
        return $lastSegment !== false ? (string) $lastSegment : null;
    }

    public static function parseValue(?string $value, ?string $type): mixed
    {
        if (!$value) return null;

        if (str_starts_with($type, 'list.')) {
            return json_decode($value, true);
        }

        if ($type === 'json') {
            return json_decode($value, true);
        }

        if (str_contains($type, 'number')) {
            return $value + 0;
        }

        return $value;
    }

    public static function formatFields(array $fields): array
    {
        $result = [];

        foreach ($fields as $field) {
            $val = $field['value'];

            // 🔥 parse nested JSON inside fields
            if (is_string($val) && str_starts_with($val, '[')) {
                $decoded = json_decode($val, true);
                $val = $decoded ?? $val;
            }

            $result[$field['key']] = $val;
        }

        return $result;
    }
}
