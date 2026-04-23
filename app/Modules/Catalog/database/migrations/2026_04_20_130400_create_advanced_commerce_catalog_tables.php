<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_media', function (Blueprint $table) {
            $table->id();
            $table->uuid('store_id')->nullable()->index();
            $table->foreignId('product_id')->nullable()->index();
            $table->string('shopify_product_id')->nullable()->index();
            $table->string('shopify_media_id')->nullable()->index();
            $table->string('media_content_type')->nullable()->index();
            $table->string('status')->nullable()->index();
            $table->integer('position')->default(0)->index();
            $table->string('alt')->nullable();
            $table->string('url', 2048)->nullable();
            $table->string('preview_url', 2048)->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamps();

            $table->unique(['store_id', 'shopify_media_id'], 'product_media_store_shopify_media_unique');
            $table->foreign('store_id')->references('id')->on('stores')->nullOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->nullOnDelete();
        });

        Schema::create('markets', function (Blueprint $table) {
            $table->id();
            $table->uuid('store_id')->nullable()->index();
            $table->string('shopify_market_id')->nullable()->index();
            $table->string('name')->nullable()->index();
            $table->string('handle')->nullable()->index();
            $table->string('currency', 10)->nullable()->index();
            $table->boolean('enabled')->default(true)->index();
            $table->boolean('is_primary')->default(false)->index();
            $table->json('raw_payload')->nullable();
            $table->timestamps();

            $table->unique(['store_id', 'shopify_market_id'], 'markets_store_shopify_market_unique');
            $table->foreign('store_id')->references('id')->on('stores')->nullOnDelete();
        });

        Schema::create('price_lists', function (Blueprint $table) {
            $table->id();
            $table->uuid('store_id')->nullable()->index();
            $table->foreignId('market_id')->nullable()->index();
            $table->string('shopify_price_list_id')->nullable()->index();
            $table->string('shopify_catalog_id')->nullable()->index();
            $table->string('name')->nullable()->index();
            $table->string('currency', 10)->nullable()->index();
            $table->unsignedInteger('fixed_prices_count')->default(0);
            $table->json('raw_payload')->nullable();
            $table->timestamps();

            $table->unique(['store_id', 'shopify_price_list_id'], 'price_lists_store_shopify_price_list_unique');
            $table->foreign('store_id')->references('id')->on('stores')->nullOnDelete();
            $table->foreign('market_id')->references('id')->on('markets')->nullOnDelete();
        });

        Schema::create('price_list_items', function (Blueprint $table) {
            $table->id();
            $table->uuid('store_id')->nullable()->index();
            $table->foreignId('price_list_id')->nullable()->index();
            $table->foreignId('product_variant_id')->nullable()->index();
            $table->string('shopify_variant_id')->nullable()->index();
            $table->decimal('amount', 12, 2)->nullable();
            $table->decimal('compare_at_amount', 12, 2)->nullable();
            $table->string('currency', 10)->nullable()->index();
            $table->string('origin_type')->nullable()->index();
            $table->json('raw_payload')->nullable();
            $table->timestamps();

            $table->unique(['price_list_id', 'shopify_variant_id'], 'price_list_items_price_list_variant_unique');
            $table->foreign('store_id')->references('id')->on('stores')->nullOnDelete();
            $table->foreign('price_list_id')->references('id')->on('price_lists')->cascadeOnDelete();
            $table->foreign('product_variant_id')->references('id')->on('product_variants')->nullOnDelete();
        });

        Schema::create('selling_plan_groups', function (Blueprint $table) {
            $table->id();
            $table->uuid('store_id')->nullable()->index();
            $table->string('shopify_selling_plan_group_id')->nullable()->index();
            $table->string('name')->nullable()->index();
            $table->string('app_id')->nullable()->index();
            $table->json('options')->nullable();
            $table->text('summary')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamps();

            $table->unique(['store_id', 'shopify_selling_plan_group_id'], 'selling_plan_groups_store_shopify_group_unique');
            $table->foreign('store_id')->references('id')->on('stores')->nullOnDelete();
        });

        Schema::create('selling_plans', function (Blueprint $table) {
            $table->id();
            $table->uuid('store_id')->nullable()->index();
            $table->foreignId('selling_plan_group_id')->nullable()->index();
            $table->string('shopify_selling_plan_id')->nullable()->index();
            $table->string('name')->nullable()->index();
            $table->string('category')->nullable()->index();
            $table->json('billing_policy')->nullable();
            $table->json('delivery_policy')->nullable();
            $table->json('pricing_policies')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamps();

            $table->unique(['store_id', 'shopify_selling_plan_id'], 'selling_plans_store_shopify_plan_unique');
            $table->foreign('store_id')->references('id')->on('stores')->nullOnDelete();
            $table->foreign('selling_plan_group_id')->references('id')->on('selling_plan_groups')->nullOnDelete();
        });

        Schema::create('selling_plan_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->uuid('store_id')->nullable()->index();
            $table->foreignId('customer_id')->nullable()->index();
            $table->string('shopify_subscription_contract_id')->nullable()->index('sps_shopify_contract_idx');
            $table->string('shopify_customer_id')->nullable()->index();
            $table->string('status')->nullable()->index();
            $table->string('currency', 10)->nullable();
            $table->decimal('next_billing_amount', 12, 2)->nullable();
            $table->timestamp('next_billing_date')->nullable()->index();
            $table->json('raw_payload')->nullable();
            $table->timestamps();

            $table->unique(['store_id', 'shopify_subscription_contract_id'], 'selling_plan_subscriptions_store_shopify_contract_unique');
            $table->foreign('store_id')->references('id')->on('stores')->nullOnDelete();
            $table->foreign('customer_id')->references('id')->on('customers')->nullOnDelete();
        });

        Schema::create('product_selling_plan_groups', function (Blueprint $table) {
            $table->id();
            $table->uuid('store_id')->nullable()->index();
            $table->foreignId('product_id')->nullable()->index();
            $table->foreignId('selling_plan_group_id')->nullable()->index();
            $table->string('shopify_product_id')->nullable()->index();
            $table->timestamps();

            $table->unique(['product_id', 'selling_plan_group_id'], 'product_selling_plan_groups_product_group_unique');
            $table->foreign('store_id')->references('id')->on('stores')->nullOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->nullOnDelete();
            $table->foreign('selling_plan_group_id')->references('id')->on('selling_plan_groups')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_selling_plan_groups');
        Schema::dropIfExists('selling_plan_subscriptions');
        Schema::dropIfExists('selling_plans');
        Schema::dropIfExists('selling_plan_groups');
        Schema::dropIfExists('price_list_items');
        Schema::dropIfExists('price_lists');
        Schema::dropIfExists('markets');
        Schema::dropIfExists('product_media');
    }
};
