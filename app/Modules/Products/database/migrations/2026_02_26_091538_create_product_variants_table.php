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
            $table->uuid('store_id')->nullable()->index();
            $table->foreignId('product_id')->nullable()->index();
            $table->string('shopify_variant_id')->nullable();
            $table->string('title')->nullable();
            $table->string('sku')->nullable()->index();
            $table->string('barcode')->nullable()->index();
            $table->decimal('price', 10, 2)->nullable();
            $table->decimal('compare_at_price', 10, 2)->nullable();
            $table->decimal('weight', 10, 2)->nullable();
            $table->string('weight_unit')->nullable();
            $table->string('option1')->nullable();
            $table->string('option2')->nullable();
            $table->string('option3')->nullable();
            $table->integer('inventory_quantity')->default(0)->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['store_id', 'product_id']);
            $table->index(['store_id', 'shopify_variant_id']);
            $table->index(['store_id', 'sku']);

            $table->unique(['store_id', 'shopify_variant_id']);

            $table->foreign('store_id')
                ->references('id')
                ->on('stores')
                ->nullOnDelete();
            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->nullOnDelete();
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
