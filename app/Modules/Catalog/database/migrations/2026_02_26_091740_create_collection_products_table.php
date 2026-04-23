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
            $table->uuid('store_id')->index();
            $table->foreignId('collection_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->integer('position')->default(0);
            $table->enum('added_via', ['manual', 'auto'])->default('manual');
            $table->timestamps();

            $table->unique(['collection_id', 'product_id']);
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
