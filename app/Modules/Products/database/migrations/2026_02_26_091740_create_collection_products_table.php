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
        Schema::create('collection_products', function (Blueprint $table) {
            $table->id();
            $table->uuid('store_id')->nullable()->index();
            $table->foreignId('collection_id')->nullable()->index();
            $table->foreignId('product_id')->nullable()->index();
            $table->timestamps();

            $table->unique(['collection_id', 'product_id']);

            $table->foreign('store_id')
                ->references('id')
                ->on('stores')
                ->nullOnDelete();
            $table->foreign('collection_id')
                ->references('id')
                ->on('collections')
                ->nullOnDelete();
            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->cascadeOnDelete();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('collection_products');
    }
};
