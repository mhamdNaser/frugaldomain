<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'shopify_customer_id')) {
                $table->string('shopify_customer_id')->nullable()->after('customer_id')->index();
            }
            if (!Schema::hasColumn('orders', 'email')) {
                $table->string('email')->nullable()->after('shopify_customer_id')->index();
            }
            if (!Schema::hasColumn('orders', 'raw_payload')) {
                $table->json('raw_payload')->nullable()->after('placed_at');
            }
            if (!Schema::hasColumn('orders', 'shopify_created_at')) {
                $table->timestamp('shopify_created_at')->nullable()->after('raw_payload')->index();
            }
            if (!Schema::hasColumn('orders', 'shopify_updated_at')) {
                $table->timestamp('shopify_updated_at')->nullable()->after('shopify_created_at')->index();
            }
        });

        Schema::table('order_items', function (Blueprint $table) {
            if (!Schema::hasColumn('order_items', 'shopify_line_item_id')) {
                $table->string('shopify_line_item_id')->nullable()->after('order_id')->index();
            }
            if (!Schema::hasColumn('order_items', 'shopify_product_id')) {
                $table->string('shopify_product_id')->nullable()->after('variant_id')->index();
            }
            if (!Schema::hasColumn('order_items', 'shopify_variant_id')) {
                $table->string('shopify_variant_id')->nullable()->after('shopify_product_id')->index();
            }
            if (!Schema::hasColumn('order_items', 'raw_payload')) {
                $table->json('raw_payload')->nullable()->after('total_price');
            }
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            foreach (['shopify_line_item_id', 'shopify_product_id', 'shopify_variant_id', 'raw_payload'] as $column) {
                if (Schema::hasColumn('order_items', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            foreach (['shopify_customer_id', 'email', 'raw_payload', 'shopify_created_at', 'shopify_updated_at'] as $column) {
                if (Schema::hasColumn('orders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
