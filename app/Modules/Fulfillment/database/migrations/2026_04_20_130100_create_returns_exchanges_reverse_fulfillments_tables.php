<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_returns', function (Blueprint $table) {
            $table->id();
            $table->uuid('store_id')->nullable()->index();
            $table->foreignId('order_id')->nullable()->index();
            $table->string('shopify_return_id')->nullable()->index();
            $table->string('status')->nullable()->index();
            $table->string('name')->nullable();
            $table->timestamp('requested_at')->nullable()->index();
            $table->timestamp('opened_at')->nullable()->index();
            $table->timestamp('closed_at')->nullable()->index();
            $table->json('raw_payload')->nullable();
            $table->timestamps();

            $table->unique(['store_id', 'shopify_return_id'], 'order_returns_store_shopify_return_unique');
            $table->foreign('store_id')->references('id')->on('stores')->nullOnDelete();
            $table->foreign('order_id')->references('id')->on('orders')->nullOnDelete();
        });

        Schema::create('order_return_items', function (Blueprint $table) {
            $table->id();
            $table->uuid('store_id')->nullable()->index();
            $table->foreignId('order_return_id')->nullable()->index();
            $table->foreignId('order_item_id')->nullable()->index();
            $table->string('shopify_return_line_item_id')->nullable()->index();
            $table->string('shopify_line_item_id')->nullable()->index();
            $table->integer('quantity')->default(0);
            $table->string('reason')->nullable()->index();
            $table->text('note')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamps();

            $table->index(['order_return_id', 'shopify_return_line_item_id'], 'order_return_items_return_line_idx');
            $table->foreign('store_id')->references('id')->on('stores')->nullOnDelete();
            $table->foreign('order_return_id')->references('id')->on('order_returns')->cascadeOnDelete();
            $table->foreign('order_item_id')->references('id')->on('order_items')->nullOnDelete();
        });

        Schema::create('exchanges', function (Blueprint $table) {
            $table->id();
            $table->uuid('store_id')->nullable()->index();
            $table->foreignId('order_return_id')->nullable()->index();
            $table->string('shopify_exchange_line_item_id')->nullable()->index();
            $table->string('shopify_line_item_id')->nullable()->index();
            $table->string('title')->nullable();
            $table->integer('quantity')->default(0);
            $table->string('status')->nullable()->index();
            $table->json('raw_payload')->nullable();
            $table->timestamps();

            $table->index(['store_id', 'shopify_exchange_line_item_id'], 'exchanges_store_shopify_exchange_idx');
            $table->foreign('store_id')->references('id')->on('stores')->nullOnDelete();
            $table->foreign('order_return_id')->references('id')->on('order_returns')->nullOnDelete();
        });

        Schema::create('reverse_fulfillments', function (Blueprint $table) {
            $table->id();
            $table->uuid('store_id')->nullable()->index();
            $table->foreignId('order_return_id')->nullable()->index();
            $table->string('shopify_reverse_fulfillment_order_id')->nullable()->index();
            $table->string('status')->nullable()->index();
            $table->json('raw_payload')->nullable();
            $table->timestamp('shopify_created_at')->nullable()->index();
            $table->timestamp('shopify_updated_at')->nullable()->index();
            $table->timestamps();

            $table->index(['store_id', 'shopify_reverse_fulfillment_order_id'], 'reverse_fulfillments_store_shopify_reverse_idx');
            $table->foreign('store_id')->references('id')->on('stores')->nullOnDelete();
            $table->foreign('order_return_id')->references('id')->on('order_returns')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reverse_fulfillments');
        Schema::dropIfExists('exchanges');
        Schema::dropIfExists('order_return_items');
        Schema::dropIfExists('order_returns');
    }
};

