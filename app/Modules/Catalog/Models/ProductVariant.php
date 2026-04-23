<?php

namespace App\Modules\Catalog\Models;

use App\Modules\CMS\Models\File;
use App\Modules\CMS\Models\Metafield;
use App\Modules\Inventory\Models\InventoryLevel;
use App\Modules\Inventory\Models\InventoryMovement;
use App\Modules\Inventory\Models\Inventory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductVariant extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'store_id',
        'product_id',
        'shopify_variant_id',
        'title',
        'sku',
        'barcode',
        'price',
        'compare_at_price',
        'is_default',
        'availableForSale',
        'taxable',
        'position',
        'raw_payload',
        'inventory_quantity',
        'shopify_created_at',
        'shopify_updated_at',
    ];

    protected $casts = [
        'raw_payload' => 'array',
        'shopify_created_at' => 'datetime',
        'shopify_updated_at' => 'datetime',
        'price' => 'decimal:2',
        'compare_at_price' => 'decimal:2',
        'weight' => 'decimal:2',
        'is_default' => 'boolean',
    ];


    public function optionValues()
    {
        return $this->belongsToMany(
            OptionValue::class,
            'variant_option_values',
            'product_variant_id',
            'option_value_id'
        )->withTimestamps();
    }

    public function files()
    {
        return $this->morphMany(File::class, 'fileable')->orderBy('position');
    }

    public function metafields()
    {
        return $this->morphMany(Metafield::class, 'metafieldable');
    }

    public function hasOptionValue(string $optionName, string $valueName): bool
    {
        foreach ($this->selected_options ?? [] as $opt) {
            if ($opt['name'] === $optionName && $opt['value'] === $valueName) {
                return true;
            }
        }
        return false;
    }

    // العلاقات
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function inventory()
    {
        return $this->hasOne(Inventory::class);
    }

    public function inventories()
    {
        return $this->hasMany(Inventory::class, 'product_variant_id');
    }

    public function inventoryLevels()
    {
        return $this->hasMany(InventoryLevel::class, 'product_variant_id');
    }

    public function inventoryMovements()
    {
        return $this->hasMany(InventoryMovement::class, 'product_variant_id')
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }

    public function priceListItems()
    {
        return $this->hasMany(PriceListItem::class, 'product_variant_id');
    }

    public function priceLists()
    {
        return $this->belongsToMany(
            PriceList::class,
            'price_list_items',
            'product_variant_id',
            'price_list_id'
        )->withPivot([
            'id',
            'store_id',
            'shopify_variant_id',
            'amount',
            'compare_at_amount',
            'currency',
            'origin_type',
            'raw_payload',
        ])->withTimestamps();
    }
}
