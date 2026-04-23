<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fulfillment_services', function (Blueprint $table) {
            $table->id();
            $table->uuid('store_id')->nullable()->index();
            $table->string('shopify_fulfillment_service_id')->index();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('service_name')->nullable();
            $table->string('type')->nullable();
            $table->boolean('callback_url')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamps();

            $table->unique(['store_id', 'shopify_fulfillment_service_id'], 'fs_store_shopify_unique');
            $table->foreign('store_id')->references('id')->on('stores')->nullOnDelete();
        });

        Schema::create('fulfillment_orders', function (Blueprint $table) {
            $table->id();
            $table->uuid('store_id')->nullable()->index();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->foreignId('fulfillment_service_id')->nullable()->constrained('fulfillment_services')->nullOnDelete();
            $table->string('shopify_fulfillment_order_id')->index();
            $table->string('shopify_order_id')->nullable()->index();
            $table->string('shopify_assigned_location_id')->nullable()->index();
            $table->string('assigned_location_name')->nullable();
            $table->string('status')->nullable()->index();
            $table->string('request_status')->nullable()->index();
            $table->timestamp('fulfill_at')->nullable()->index();
            $table->timestamp('fulfill_by')->nullable()->index();
            $table->json('destination')->nullable();
            $table->json('delivery_method')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamp('shopify_created_at')->nullable()->index();
            $table->timestamp('shopify_updated_at')->nullable()->index();
            $table->timestamps();

            $table->unique(['store_id', 'shopify_fulfillment_order_id'], 'fo_store_shopify_unique');
            $table->foreign('store_id')->references('id')->on('stores')->nullOnDelete();
        });

        Schema::create('fulfillment_order_items', function (Blueprint $table) {
            $table->id();
            $table->uuid('store_id')->nullable()->index();
            $table->foreignId('fulfillment_order_id')->nullable()->constrained('fulfillment_orders')->cascadeOnDelete();
            $table->foreignId('order_item_id')->nullable()->constrained('order_items')->nullOnDelete();
            $table->string('shopify_fulfillment_order_line_item_id');
            $table->string('shopify_line_item_id')->nullable()->index();
            $table->integer('total_quantity')->default(0);
            $table->integer('remaining_quantity')->default(0);
            $table->json('raw_payload')->nullable();
            $table->timestamps();

            $table->unique(['fulfillment_order_id', 'shopify_fulfillment_order_line_item_id'], 'foi_order_line_unique');
            $table->index('shopify_fulfillment_order_line_item_id', 'foi_shopify_line_idx');
            $table->foreign('store_id')->references('id')->on('stores')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fulfillment_order_items');
        Schema::dropIfExists('fulfillment_orders');
        Schema::dropIfExists('fulfillment_services');
    }
};
