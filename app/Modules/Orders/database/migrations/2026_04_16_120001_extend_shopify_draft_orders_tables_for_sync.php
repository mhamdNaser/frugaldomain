<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('draft_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('draft_orders', 'store_id')) {
                $table->uuid('store_id')->nullable()->after('id')->index();
            }
            if (!Schema::hasColumn('draft_orders', 'customer_id')) {
                $table->foreignId('customer_id')->nullable()->after('store_id')->index();
            }
            if (!Schema::hasColumn('draft_orders', 'shopify_customer_id')) {
                $table->string('shopify_customer_id')->nullable()->after('customer_id')->index();
            }
            if (!Schema::hasColumn('draft_orders', 'shopify_draft_order_id')) {
                $table->string('shopify_draft_order_id')->nullable()->after('shopify_customer_id');
            }
            if (!Schema::hasColumn('draft_orders', 'name')) {
                $table->string('name')->nullable()->after('shopify_draft_order_id');
            }
            if (!Schema::hasColumn('draft_orders', 'status')) {
                $table->string('status')->nullable()->after('name')->index();
            }
            if (!Schema::hasColumn('draft_orders', 'invoice_url')) {
                $table->text('invoice_url')->nullable()->after('status');
            }
            if (!Schema::hasColumn('draft_orders', 'subtotal')) {
                $table->decimal('subtotal', 10, 2)->default(0)->after('invoice_url');
            }
            if (!Schema::hasColumn('draft_orders', 'tax')) {
                $table->decimal('tax', 10, 2)->default(0)->after('subtotal');
            }
            if (!Schema::hasColumn('draft_orders', 'total')) {
                $table->decimal('total', 10, 2)->default(0)->after('tax');
            }
            if (!Schema::hasColumn('draft_orders', 'currency')) {
                $table->string('currency')->nullable()->after('total');
            }
            if (!Schema::hasColumn('draft_orders', 'completed_at')) {
                $table->timestamp('completed_at')->nullable()->after('currency')->index();
            }
            if (!Schema::hasColumn('draft_orders', 'raw_payload')) {
                $table->json('raw_payload')->nullable()->after('completed_at');
            }
            if (!Schema::hasColumn('draft_orders', 'shopify_created_at')) {
                $table->timestamp('shopify_created_at')->nullable()->after('raw_payload')->index();
            }
            if (!Schema::hasColumn('draft_orders', 'shopify_updated_at')) {
                $table->timestamp('shopify_updated_at')->nullable()->after('shopify_created_at')->index();
            }
        });

        Schema::table('draft_order_items', function (Blueprint $table) {
            if (!Schema::hasColumn('draft_order_items', 'store_id')) {
                $table->uuid('store_id')->nullable()->after('id')->index();
            }
            if (!Schema::hasColumn('draft_order_items', 'draft_order_id')) {
                $table->foreignId('draft_order_id')->nullable()->after('store_id')->index();
            }
            if (!Schema::hasColumn('draft_order_items', 'variant_id')) {
                $table->foreignId('variant_id')->nullable()->after('draft_order_id')->index();
            }
            if (!Schema::hasColumn('draft_order_items', 'shopify_line_item_id')) {
                $table->string('shopify_line_item_id')->nullable()->after('variant_id')->index();
            }
            if (!Schema::hasColumn('draft_order_items', 'shopify_product_id')) {
                $table->string('shopify_product_id')->nullable()->after('shopify_line_item_id')->index();
            }
            if (!Schema::hasColumn('draft_order_items', 'shopify_variant_id')) {
                $table->string('shopify_variant_id')->nullable()->after('shopify_product_id')->index();
            }
            if (!Schema::hasColumn('draft_order_items', 'product_title')) {
                $table->string('product_title')->nullable()->after('shopify_variant_id');
            }
            if (!Schema::hasColumn('draft_order_items', 'variant_title')) {
                $table->string('variant_title')->nullable()->after('product_title');
            }
            if (!Schema::hasColumn('draft_order_items', 'sku')) {
                $table->string('sku')->nullable()->after('variant_title')->index();
            }
            if (!Schema::hasColumn('draft_order_items', 'quantity')) {
                $table->integer('quantity')->default(1)->after('sku');
            }
            if (!Schema::hasColumn('draft_order_items', 'unit_price')) {
                $table->decimal('unit_price', 10, 2)->default(0)->after('quantity');
            }
            if (!Schema::hasColumn('draft_order_items', 'total_price')) {
                $table->decimal('total_price', 10, 2)->default(0)->after('unit_price');
            }
            if (!Schema::hasColumn('draft_order_items', 'raw_payload')) {
                $table->json('raw_payload')->nullable()->after('total_price');
            }
        });
    }

    public function down(): void
    {
        Schema::table('draft_order_items', function (Blueprint $table) {
            foreach ([
                'store_id', 'draft_order_id', 'variant_id', 'shopify_line_item_id', 'shopify_product_id',
                'shopify_variant_id', 'product_title', 'variant_title', 'sku', 'quantity', 'unit_price',
                'total_price', 'raw_payload',
            ] as $column) {
                if (Schema::hasColumn('draft_order_items', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('draft_orders', function (Blueprint $table) {
            foreach ([
                'store_id', 'customer_id', 'shopify_customer_id', 'shopify_draft_order_id', 'name', 'status',
                'invoice_url', 'subtotal', 'tax', 'total', 'currency', 'completed_at', 'raw_payload',
                'shopify_created_at', 'shopify_updated_at',
            ] as $column) {
                if (Schema::hasColumn('draft_orders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
