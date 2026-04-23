<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_channels', function (Blueprint $table) {
            $table->id();
            $table->uuid('store_id')->nullable()->index();
            $table->foreignId('order_id')->nullable()->index();
            $table->string('shopify_order_id')->nullable()->index();
            $table->string('source_name')->nullable()->index();
            $table->string('source_identifier')->nullable()->index();
            $table->string('channel_id')->nullable()->index();
            $table->string('channel_name')->nullable();
            $table->string('app_id')->nullable()->index();
            $table->string('app_title')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamps();

            $table->unique(['store_id', 'shopify_order_id'], 'order_channels_store_shopify_order_unique');
            $table->foreign('store_id')->references('id')->on('stores')->nullOnDelete();
            $table->foreign('order_id')->references('id')->on('orders')->nullOnDelete();
        });

        Schema::create('order_risks', function (Blueprint $table) {
            $table->id();
            $table->uuid('store_id')->nullable()->index();
            $table->foreignId('order_id')->nullable()->index();
            $table->string('shopify_order_id')->nullable()->index();
            $table->string('assessment_id')->nullable()->index();
            $table->string('recommendation')->nullable()->index();
            $table->string('risk_level')->nullable()->index();
            $table->string('provider')->nullable()->index();
            $table->timestamp('assessed_at')->nullable()->index();
            $table->json('facts')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamps();

            $table->index(['store_id', 'shopify_order_id', 'assessment_id'], 'order_risks_store_order_assessment_idx');
            $table->foreign('store_id')->references('id')->on('stores')->nullOnDelete();
            $table->foreign('order_id')->references('id')->on('orders')->nullOnDelete();
        });

        Schema::create('order_duties', function (Blueprint $table) {
            $table->id();
            $table->uuid('store_id')->nullable()->index();
            $table->foreignId('order_id')->nullable()->index();
            $table->string('shopify_order_id')->nullable()->index();
            $table->string('shopify_duty_id')->nullable()->index();
            $table->string('harmonized_system_code')->nullable()->index();
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('currency', 10)->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamps();

            $table->index(['store_id', 'shopify_order_id', 'shopify_duty_id'], 'order_duties_store_order_duty_idx');
            $table->foreign('store_id')->references('id')->on('stores')->nullOnDelete();
            $table->foreign('order_id')->references('id')->on('orders')->nullOnDelete();
        });

        Schema::create('order_item_duties', function (Blueprint $table) {
            $table->id();
            $table->uuid('store_id')->nullable()->index();
            $table->foreignId('order_item_id')->nullable()->index();
            $table->foreignId('order_duty_id')->nullable()->index();
            $table->string('shopify_line_item_id')->nullable()->index();
            $table->string('shopify_duty_id')->nullable()->index();
            $table->string('harmonized_system_code')->nullable()->index();
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('currency', 10)->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamps();

            $table->index(['store_id', 'shopify_line_item_id', 'shopify_duty_id'], 'order_item_duties_store_line_duty_idx');
            $table->foreign('store_id')->references('id')->on('stores')->nullOnDelete();
            $table->foreign('order_item_id')->references('id')->on('order_items')->nullOnDelete();
            $table->foreign('order_duty_id')->references('id')->on('order_duties')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_item_duties');
        Schema::dropIfExists('order_duties');
        Schema::dropIfExists('order_risks');
        Schema::dropIfExists('order_channels');
    }
};

