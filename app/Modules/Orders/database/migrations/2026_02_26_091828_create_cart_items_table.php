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
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->uuid('store_id')->nullable()->index();
            $table->foreignId('cart_id')->nullable()->index();
            $table->foreignId('variant_id')->nullable()->index();
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 10, 2)->default(0);
            $table->decimal('total_price', 10, 2)->default(0);
            $table->timestamps();

            $table->unique(['cart_id', 'variant_id']);

            $table->index(['store_id', 'cart_id']);
            $table->index(['cart_id', 'variant_id']);

            $table->foreign('store_id')
                ->references('id')
                ->on('stores')
                ->nullOnDelete();
            $table->foreign('cart_id')
                ->references('id')
                ->on('carts')
                ->cascadeOnDelete();
            $table->foreign('variant_id')
                ->references('id')
                ->on('product_variants')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};
