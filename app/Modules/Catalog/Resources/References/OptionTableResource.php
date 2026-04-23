<?php

namespace App\Modules\Catalog\Resources\References;

use Illuminate\Http\Resources\Json\JsonResource;

class OptionTableResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'name' => $this->name,
            'products_count' => $this->products_count ?? 0,
            'values' => $this->whenLoaded('values', fn () => $this->values->map(fn ($value) => [
                'id' => $value->id,
                'option_id' => $value->option_id,
                'label' => $value->label,
                'value' => $value->value,
                'is_color' => $this->isColorOption(),
                'color_hex' => $this->isColorOption() ? $this->colorHex($value->value) : null,
            ])->values()),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    private function isColorOption(): bool
    {
        return in_array(strtolower((string) $this->name), ['color', 'colour', 'colors', 'colours'], true);
    }

    private function colorHex(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        $value = trim($value);

        if (preg_match('/^#(?:[0-9a-fA-F]{3}){1,2}$/', $value)) {
            return $value;
        }

        return null;
    }
}
