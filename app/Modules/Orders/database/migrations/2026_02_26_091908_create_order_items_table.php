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
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->uuid('store_id')->nullable()->index();
            $table->foreignId('order_id')->nullable()->index();
            $table->foreignId('variant_id')->nullable()->index();
            $table->string('product_title');
            $table->string('variant_title')->nullable();
            $table->string('sku')->nullable();
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 10, 2)->default(0);
            $table->decimal('total_price', 10, 2)->default(0);
            $table->timestamps();

            $table->index(['store_id', 'order_id']);
            $table->index(['order_id', 'variant_id']);


            $table->foreign('store_id')
                ->references('id')
                ->on('stores')
                ->nullOnDelete();
            $table->foreign('order_id')
                ->references('id')
                ->on('orders')
                ->cascadeOnDelete();
            $table->foreign('variant_id')
                ->references('id')
                ->on('product_variants')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
