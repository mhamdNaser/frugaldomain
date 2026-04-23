<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->uuid('store_id')->index();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete()->index();
            $table->string('shopify_variant_id')->index();

            $table->string('title')->nullable();
            $table->string('sku')->nullable()->index();
            $table->string('barcode')->nullable()->index();
            $table->decimal('price', 10, 2)->nullable();
            $table->decimal('compare_at_price', 10, 2)->nullable();

            $table->boolean('is_default')->default(false);
            $table->boolean('availableForSale')->default(false);
            $table->boolean('taxable')->default(false);
            $table->integer('position')->nullable();
            $table->json('raw_payload')->nullable();
            
            $table->integer('inventory_quantity')->default(0)->index();
            $table->timestamp('shopify_created_at')->nullable();
            $table->timestamp('shopify_updated_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['store_id', 'product_id']);
            $table->index(['store_id', 'sku']);
            $table->unique(['store_id', 'shopify_variant_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
