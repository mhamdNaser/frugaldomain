<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('discounts', function (Blueprint $table) {
            if (!Schema::hasColumn('discounts', 'store_id')) {
                $table->uuid('store_id')->nullable()->after('id')->index();
            }
            if (!Schema::hasColumn('discounts', 'shopify_discount_id')) {
                $table->string('shopify_discount_id')->nullable()->after('store_id')->index();
            }
            if (!Schema::hasColumn('discounts', 'discount_type')) {
                $table->string('discount_type')->nullable()->after('shopify_discount_id')->index();
            }
            if (!Schema::hasColumn('discounts', 'method')) {
                $table->string('method')->nullable()->after('discount_type')->index();
            }
            if (!Schema::hasColumn('discounts', 'title')) {
                $table->string('title')->nullable()->after('method');
            }
            if (!Schema::hasColumn('discounts', 'status')) {
                $table->string('status')->nullable()->after('title')->index();
            }
            if (!Schema::hasColumn('discounts', 'summary')) {
                $table->text('summary')->nullable()->after('status');
            }
            if (!Schema::hasColumn('discounts', 'short_summary')) {
                $table->text('short_summary')->nullable()->after('summary');
            }
            if (!Schema::hasColumn('discounts', 'usage_limit')) {
                $table->integer('usage_limit')->nullable()->after('short_summary');
            }
            if (!Schema::hasColumn('discounts', 'usage_count')) {
                $table->integer('usage_count')->default(0)->after('usage_limit');
            }
            if (!Schema::hasColumn('discounts', 'total_sales')) {
                $table->decimal('total_sales', 12, 2)->default(0)->after('usage_count');
            }
            if (!Schema::hasColumn('discounts', 'currency')) {
                $table->string('currency', 10)->nullable()->after('total_sales');
            }
            if (!Schema::hasColumn('discounts', 'starts_at')) {
                $table->timestamp('starts_at')->nullable()->after('currency')->index();
            }
            if (!Schema::hasColumn('discounts', 'ends_at')) {
                $table->timestamp('ends_at')->nullable()->after('starts_at')->index();
            }
            if (!Schema::hasColumn('discounts', 'raw_payload')) {
                $table->json('raw_payload')->nullable()->after('ends_at');
            }
            if (!Schema::hasColumn('discounts', 'shopify_updated_at')) {
                $table->timestamp('shopify_updated_at')->nullable()->after('raw_payload')->index();
            }
        });

        Schema::table('discount_codes', function (Blueprint $table) {
            if (!Schema::hasColumn('discount_codes', 'store_id')) {
                $table->uuid('store_id')->nullable()->after('id')->index();
            }
            if (!Schema::hasColumn('discount_codes', 'discount_id')) {
                $table->foreignId('discount_id')->nullable()->after('store_id')->index();
            }
            if (!Schema::hasColumn('discount_codes', 'shopify_discount_code_id')) {
                $table->string('shopify_discount_code_id')->nullable()->after('discount_id')->index();
            }
            if (!Schema::hasColumn('discount_codes', 'code')) {
                $table->string('code')->nullable()->after('shopify_discount_code_id')->index();
            }
            if (!Schema::hasColumn('discount_codes', 'usage_count')) {
                $table->integer('usage_count')->default(0)->after('code');
            }
            if (!Schema::hasColumn('discount_codes', 'raw_payload')) {
                $table->json('raw_payload')->nullable()->after('usage_count');
            }
        });

        Schema::table('discount_usages', function (Blueprint $table) {
            if (!Schema::hasColumn('discount_usages', 'store_id')) {
                $table->uuid('store_id')->nullable()->after('id')->index();
            }
            if (!Schema::hasColumn('discount_usages', 'discount_id')) {
                $table->foreignId('discount_id')->nullable()->after('store_id')->index();
            }
            if (!Schema::hasColumn('discount_usages', 'order_id')) {
                $table->foreignId('order_id')->nullable()->after('discount_id')->index();
            }
            if (!Schema::hasColumn('discount_usages', 'shopify_order_id')) {
                $table->string('shopify_order_id')->nullable()->after('order_id')->index();
            }
            if (!Schema::hasColumn('discount_usages', 'code')) {
                $table->string('code')->nullable()->after('shopify_order_id')->index();
            }
            if (!Schema::hasColumn('discount_usages', 'usage_count')) {
                $table->integer('usage_count')->default(0)->after('code');
            }
            if (!Schema::hasColumn('discount_usages', 'total_sales')) {
                $table->decimal('total_sales', 12, 2)->default(0)->after('usage_count');
            }
            if (!Schema::hasColumn('discount_usages', 'currency')) {
                $table->string('currency', 10)->nullable()->after('total_sales');
            }
            if (!Schema::hasColumn('discount_usages', 'raw_payload')) {
                $table->json('raw_payload')->nullable()->after('currency');
            }
        });
    }

    public function down(): void
    {
        Schema::table('discount_usages', function (Blueprint $table) {
            foreach (['store_id', 'discount_id', 'order_id', 'shopify_order_id', 'code', 'usage_count', 'total_sales', 'currency', 'raw_payload'] as $column) {
                if (Schema::hasColumn('discount_usages', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('discount_codes', function (Blueprint $table) {
            foreach (['store_id', 'discount_id', 'shopify_discount_code_id', 'code', 'usage_count', 'raw_payload'] as $column) {
                if (Schema::hasColumn('discount_codes', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('discounts', function (Blueprint $table) {
            foreach (['store_id', 'shopify_discount_id', 'discount_type', 'method', 'title', 'status', 'summary', 'short_summary', 'usage_limit', 'usage_count', 'total_sales', 'currency', 'starts_at', 'ends_at', 'raw_payload', 'shopify_updated_at'] as $column) {
                if (Schema::hasColumn('discounts', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
