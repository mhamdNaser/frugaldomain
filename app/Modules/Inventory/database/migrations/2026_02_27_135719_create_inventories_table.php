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
        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            $table->uuid('store_id')->nullable()->index();
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
            $table->string('shopify_inventory_item_id')->index();

            $table->integer('available_quantity')->default(0);

            $table->boolean('tracked')->default(true);
            $table->boolean('requires_shipping')->default(true);

            $table->decimal('weight', 10, 2)->nullable();
            $table->string('weight_unit')->nullable();

            $table->timestamps();

            $table->unique(['product_variant_id', 'location_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventories');
    }
};
