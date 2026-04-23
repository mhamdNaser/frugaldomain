<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('payment_transactions', 'refund_id')) {
                $table->foreignId('refund_id')->nullable()->after('order_id')->index();
            }
            if (!Schema::hasColumn('payment_transactions', 'shopify_transaction_id')) {
                $table->string('shopify_transaction_id')->nullable()->after('refund_id')->index();
            }
            if (!Schema::hasColumn('payment_transactions', 'parent_shopify_transaction_id')) {
                $table->string('parent_shopify_transaction_id')->nullable()->after('shopify_transaction_id')->index();
            }
            if (!Schema::hasColumn('payment_transactions', 'kind')) {
                $table->string('kind')->nullable()->after('transaction_reference')->index();
            }
            if (!Schema::hasColumn('payment_transactions', 'account_number')) {
                $table->string('account_number')->nullable()->after('gateway');
            }
            if (!Schema::hasColumn('payment_transactions', 'test')) {
                $table->boolean('test')->default(false)->after('status')->index();
            }
            if (!Schema::hasColumn('payment_transactions', 'manual_payment_gateway')) {
                $table->boolean('manual_payment_gateway')->default(false)->after('test');
            }
            if (!Schema::hasColumn('payment_transactions', 'processed_at')) {
                $table->timestamp('processed_at')->nullable()->after('manual_payment_gateway')->index();
            }
        });

        Schema::table('refunds', function (Blueprint $table) {
            if (!Schema::hasColumn('refunds', 'store_id')) {
                $table->uuid('store_id')->nullable()->after('id')->index();
            }
            if (!Schema::hasColumn('refunds', 'order_id')) {
                $table->foreignId('order_id')->nullable()->after('store_id')->index();
            }
            if (!Schema::hasColumn('refunds', 'shopify_refund_id')) {
                $table->string('shopify_refund_id')->nullable()->after('order_id')->index();
            }
            if (!Schema::hasColumn('refunds', 'note')) {
                $table->text('note')->nullable()->after('shopify_refund_id');
            }
            if (!Schema::hasColumn('refunds', 'total')) {
                $table->decimal('total', 12, 2)->default(0)->after('note');
            }
            if (!Schema::hasColumn('refunds', 'currency')) {
                $table->string('currency', 10)->nullable()->after('total');
            }
            if (!Schema::hasColumn('refunds', 'raw_payload')) {
                $table->json('raw_payload')->nullable()->after('currency');
            }
            if (!Schema::hasColumn('refunds', 'processed_at')) {
                $table->timestamp('processed_at')->nullable()->after('raw_payload')->index();
            }
            if (!Schema::hasColumn('refunds', 'shopify_created_at')) {
                $table->timestamp('shopify_created_at')->nullable()->after('processed_at')->index();
            }
            if (!Schema::hasColumn('refunds', 'shopify_updated_at')) {
                $table->timestamp('shopify_updated_at')->nullable()->after('shopify_created_at')->index();
            }
        });

        Schema::table('refund_items', function (Blueprint $table) {
            if (!Schema::hasColumn('refund_items', 'store_id')) {
                $table->uuid('store_id')->nullable()->after('id')->index();
            }
            if (!Schema::hasColumn('refund_items', 'refund_id')) {
                $table->foreignId('refund_id')->nullable()->after('store_id')->index();
            }
            if (!Schema::hasColumn('refund_items', 'order_item_id')) {
                $table->foreignId('order_item_id')->nullable()->after('refund_id')->index();
            }
            if (!Schema::hasColumn('refund_items', 'shopify_refund_line_item_id')) {
                $table->string('shopify_refund_line_item_id')->nullable()->after('order_item_id')->index();
            }
            if (!Schema::hasColumn('refund_items', 'shopify_line_item_id')) {
                $table->string('shopify_line_item_id')->nullable()->after('shopify_refund_line_item_id')->index();
            }
            if (!Schema::hasColumn('refund_items', 'quantity')) {
                $table->integer('quantity')->default(0)->after('shopify_line_item_id');
            }
            if (!Schema::hasColumn('refund_items', 'restock_type')) {
                $table->string('restock_type')->nullable()->after('quantity')->index();
            }
            if (!Schema::hasColumn('refund_items', 'restocked')) {
                $table->boolean('restocked')->default(false)->after('restock_type');
            }
            if (!Schema::hasColumn('refund_items', 'subtotal')) {
                $table->decimal('subtotal', 12, 2)->default(0)->after('restocked');
            }
            if (!Schema::hasColumn('refund_items', 'tax')) {
                $table->decimal('tax', 12, 2)->default(0)->after('subtotal');
            }
            if (!Schema::hasColumn('refund_items', 'total')) {
                $table->decimal('total', 12, 2)->default(0)->after('tax');
            }
            if (!Schema::hasColumn('refund_items', 'currency')) {
                $table->string('currency', 10)->nullable()->after('total');
            }
            if (!Schema::hasColumn('refund_items', 'raw_payload')) {
                $table->json('raw_payload')->nullable()->after('currency');
            }
        });
    }

    public function down(): void
    {
        Schema::table('refund_items', function (Blueprint $table) {
            foreach (['store_id', 'refund_id', 'order_item_id', 'shopify_refund_line_item_id', 'shopify_line_item_id', 'quantity', 'restock_type', 'restocked', 'subtotal', 'tax', 'total', 'currency', 'raw_payload'] as $column) {
                if (Schema::hasColumn('refund_items', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('refunds', function (Blueprint $table) {
            foreach (['store_id', 'order_id', 'shopify_refund_id', 'note', 'total', 'currency', 'raw_payload', 'processed_at', 'shopify_created_at', 'shopify_updated_at'] as $column) {
                if (Schema::hasColumn('refunds', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('payment_transactions', function (Blueprint $table) {
            foreach (['refund_id', 'shopify_transaction_id', 'parent_shopify_transaction_id', 'kind', 'account_number', 'test', 'manual_payment_gateway', 'processed_at'] as $column) {
                if (Schema::hasColumn('payment_transactions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
