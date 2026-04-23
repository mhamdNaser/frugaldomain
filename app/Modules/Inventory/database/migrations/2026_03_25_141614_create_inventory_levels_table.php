<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_levels', function (Blueprint $table) {
            $table->id();

            $table->uuid('store_id')->nullable()->index();
            $table->foreignId('product_variant_id')->nullable()->index();

            $table->string('inventory_item_id')->index();
            $table->string('shopify_location_id')->nullable()->index();

            $table->integer('available')->default(0)->index();

            $table->timestamp('shopify_updated_at')->nullable()->index();

            $table->json('raw_payload')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['store_id', 'inventory_item_id']);
            $table->index(['store_id', 'shopify_location_id']);

            $table->unique([
                'store_id',
                'inventory_item_id',
                'shopify_location_id'
            ], 'inventory_levels_store_item_location_unique');

            $table->foreign('store_id')
                ->references('id')
                ->on('stores')
                ->nullOnDelete();

            $table->foreign('product_variant_id')
                ->references('id')
                ->on('product_variants')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_levels');
    }
};
