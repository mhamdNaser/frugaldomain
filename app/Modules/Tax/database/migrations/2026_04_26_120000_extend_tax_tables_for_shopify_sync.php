<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tax_lines', function (Blueprint $table) {
            if (!Schema::hasColumn('tax_lines', 'store_id')) {
                $table->uuid('store_id')->nullable()->after('id')->index();
            }
            if (!Schema::hasColumn('tax_lines', 'order_id')) {
                $table->foreignId('order_id')->nullable()->after('store_id')->index();
            }
            if (!Schema::hasColumn('tax_lines', 'order_item_id')) {
                $table->foreignId('order_item_id')->nullable()->after('order_id')->index();
            }
            if (!Schema::hasColumn('tax_lines', 'shopify_tax_line_id')) {
                $table->string('shopify_tax_line_id')->nullable()->after('order_item_id')->index();
            }
            if (!Schema::hasColumn('tax_lines', 'source_key')) {
                $table->string('source_key', 64)->nullable()->after('shopify_tax_line_id')->index();
            }
            if (!Schema::hasColumn('tax_lines', 'title')) {
                $table->string('title')->nullable()->after('source_key');
            }
            if (!Schema::hasColumn('tax_lines', 'rate')) {
                $table->decimal('rate', 12, 6)->default(0)->after('title');
            }
            if (!Schema::hasColumn('tax_lines', 'rate_percentage')) {
                $table->decimal('rate_percentage', 12, 6)->default(0)->after('rate');
            }
            if (!Schema::hasColumn('tax_lines', 'price')) {
                $table->decimal('price', 12, 2)->default(0)->after('rate_percentage');
            }
            if (!Schema::hasColumn('tax_lines', 'currency')) {
                $table->string('currency', 10)->nullable()->after('price');
            }
            if (!Schema::hasColumn('tax_lines', 'channel_liable')) {
                $table->boolean('channel_liable')->nullable()->after('currency');
            }
            if (!Schema::hasColumn('tax_lines', 'source')) {
                $table->string('source', 80)->nullable()->after('channel_liable')->index();
            }
            if (!Schema::hasColumn('tax_lines', 'is_shipping')) {
                $table->boolean('is_shipping')->default(false)->after('source');
            }
            if (!Schema::hasColumn('tax_lines', 'raw_payload')) {
                $table->json('raw_payload')->nullable()->after('is_shipping');
            }
        });

        Schema::table('tax_rates', function (Blueprint $table) {
            if (!Schema::hasColumn('tax_rates', 'store_id')) {
                $table->uuid('store_id')->nullable()->after('id')->index();
            }
            if (!Schema::hasColumn('tax_rates', 'title')) {
                $table->string('title')->nullable()->after('store_id');
            }
            if (!Schema::hasColumn('tax_rates', 'country_code')) {
                $table->string('country_code', 10)->nullable()->after('title')->index();
            }
            if (!Schema::hasColumn('tax_rates', 'province_code')) {
                $table->string('province_code', 10)->nullable()->after('country_code')->index();
            }
            if (!Schema::hasColumn('tax_rates', 'rate')) {
                $table->decimal('rate', 12, 6)->default(0)->after('province_code');
            }
            if (!Schema::hasColumn('tax_rates', 'rate_percentage')) {
                $table->decimal('rate_percentage', 12, 6)->default(0)->after('rate');
            }
            if (!Schema::hasColumn('tax_rates', 'applies_to_shipping')) {
                $table->boolean('applies_to_shipping')->default(false)->after('rate_percentage');
            }
            if (!Schema::hasColumn('tax_rates', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('applies_to_shipping')->index();
            }
            if (!Schema::hasColumn('tax_rates', 'source')) {
                $table->string('source', 80)->nullable()->after('is_active')->index();
            }
            if (!Schema::hasColumn('tax_rates', 'raw_payload')) {
                $table->json('raw_payload')->nullable()->after('source');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tax_rates', function (Blueprint $table) {
            foreach (['store_id', 'title', 'country_code', 'province_code', 'rate', 'rate_percentage', 'applies_to_shipping', 'is_active', 'source', 'raw_payload'] as $column) {
                if (Schema::hasColumn('tax_rates', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('tax_lines', function (Blueprint $table) {
            foreach (['store_id', 'order_id', 'order_item_id', 'shopify_tax_line_id', 'source_key', 'title', 'rate', 'rate_percentage', 'price', 'currency', 'channel_liable', 'source', 'is_shipping', 'raw_payload'] as $column) {
                if (Schema::hasColumn('tax_lines', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
