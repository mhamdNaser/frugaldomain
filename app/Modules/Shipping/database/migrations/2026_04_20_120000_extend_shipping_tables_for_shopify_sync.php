<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipping_zones', function (Blueprint $table) {
            if (!Schema::hasColumn('shipping_zones', 'store_id')) {
                $table->uuid('store_id')->nullable()->after('id')->index();
            }
            if (!Schema::hasColumn('shipping_zones', 'shopify_zone_id')) {
                $table->string('shopify_zone_id')->nullable()->after('store_id')->index();
            }
            if (!Schema::hasColumn('shipping_zones', 'shopify_profile_id')) {
                $table->string('shopify_profile_id')->nullable()->after('shopify_zone_id')->index();
            }
            if (!Schema::hasColumn('shipping_zones', 'name')) {
                $table->string('name')->nullable()->after('shopify_profile_id');
            }
            if (!Schema::hasColumn('shipping_zones', 'countries')) {
                $table->json('countries')->nullable()->after('name');
            }
            if (!Schema::hasColumn('shipping_zones', 'raw_payload')) {
                $table->json('raw_payload')->nullable()->after('countries');
            }
        });

        Schema::table('shipping_methods', function (Blueprint $table) {
            if (!Schema::hasColumn('shipping_methods', 'store_id')) {
                $table->uuid('store_id')->nullable()->after('id')->index();
            }
            if (!Schema::hasColumn('shipping_methods', 'shipping_zone_id')) {
                $table->unsignedBigInteger('shipping_zone_id')->nullable()->after('store_id')->index();
            }
            if (!Schema::hasColumn('shipping_methods', 'shopify_zone_id')) {
                $table->string('shopify_zone_id')->nullable()->after('shipping_zone_id')->index();
            }
            if (!Schema::hasColumn('shipping_methods', 'shopify_method_id')) {
                $table->string('shopify_method_id')->nullable()->after('shopify_zone_id')->index();
            }
            if (!Schema::hasColumn('shipping_methods', 'name')) {
                $table->string('name')->nullable()->after('shopify_method_id');
            }
            if (!Schema::hasColumn('shipping_methods', 'description')) {
                $table->text('description')->nullable()->after('name');
            }
            if (!Schema::hasColumn('shipping_methods', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('description')->index();
            }
            if (!Schema::hasColumn('shipping_methods', 'method_type')) {
                $table->string('method_type')->nullable()->after('is_active');
            }
            if (!Schema::hasColumn('shipping_methods', 'conditions')) {
                $table->json('conditions')->nullable()->after('method_type');
            }
            if (!Schema::hasColumn('shipping_methods', 'raw_payload')) {
                $table->json('raw_payload')->nullable()->after('conditions');
            }
        });

        Schema::table('shipping_rates', function (Blueprint $table) {
            if (!Schema::hasColumn('shipping_rates', 'store_id')) {
                $table->uuid('store_id')->nullable()->after('id')->index();
            }
            if (!Schema::hasColumn('shipping_rates', 'shipping_zone_id')) {
                $table->unsignedBigInteger('shipping_zone_id')->nullable()->after('store_id')->index();
            }
            if (!Schema::hasColumn('shipping_rates', 'shipping_method_id')) {
                $table->unsignedBigInteger('shipping_method_id')->nullable()->after('shipping_zone_id')->index();
            }
            if (!Schema::hasColumn('shipping_rates', 'shopify_zone_id')) {
                $table->string('shopify_zone_id')->nullable()->after('shipping_method_id')->index();
            }
            if (!Schema::hasColumn('shipping_rates', 'shopify_method_id')) {
                $table->string('shopify_method_id')->nullable()->after('shopify_zone_id')->index();
            }
            if (!Schema::hasColumn('shipping_rates', 'shopify_rate_id')) {
                $table->string('shopify_rate_id')->nullable()->after('shopify_method_id')->index();
            }
            if (!Schema::hasColumn('shipping_rates', 'name')) {
                $table->string('name')->nullable()->after('shopify_rate_id');
            }
            if (!Schema::hasColumn('shipping_rates', 'amount')) {
                $table->decimal('amount', 12, 2)->nullable()->after('name');
            }
            if (!Schema::hasColumn('shipping_rates', 'currency')) {
                $table->string('currency', 10)->nullable()->after('amount');
            }
            if (!Schema::hasColumn('shipping_rates', 'raw_payload')) {
                $table->json('raw_payload')->nullable()->after('currency');
            }
        });

        $this->safeUnique('shipping_zones', ['store_id', 'shopify_zone_id'], 'shipping_zones_store_shopify_zone_unique');
        $this->safeUnique('shipping_methods', ['store_id', 'shopify_method_id'], 'shipping_methods_store_shopify_method_unique');
        $this->safeUnique('shipping_rates', ['store_id', 'shopify_rate_id'], 'shipping_rates_store_shopify_rate_unique');
    }

    public function down(): void
    {
        $this->safeDropUnique('shipping_rates', 'shipping_rates_store_shopify_rate_unique');
        $this->safeDropUnique('shipping_methods', 'shipping_methods_store_shopify_method_unique');
        $this->safeDropUnique('shipping_zones', 'shipping_zones_store_shopify_zone_unique');

        Schema::table('shipping_rates', function (Blueprint $table) {
            foreach (['store_id', 'shipping_zone_id', 'shipping_method_id', 'shopify_zone_id', 'shopify_method_id', 'shopify_rate_id', 'name', 'amount', 'currency', 'raw_payload'] as $column) {
                if (Schema::hasColumn('shipping_rates', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('shipping_methods', function (Blueprint $table) {
            foreach (['store_id', 'shipping_zone_id', 'shopify_zone_id', 'shopify_method_id', 'name', 'description', 'is_active', 'method_type', 'conditions', 'raw_payload'] as $column) {
                if (Schema::hasColumn('shipping_methods', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('shipping_zones', function (Blueprint $table) {
            foreach (['store_id', 'shopify_zone_id', 'shopify_profile_id', 'name', 'countries', 'raw_payload'] as $column) {
                if (Schema::hasColumn('shipping_zones', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    private function safeUnique(string $table, array $columns, string $indexName): void
    {
        try {
            Schema::table($table, function (Blueprint $blueprint) use ($columns, $indexName) {
                $blueprint->unique($columns, $indexName);
            });
        } catch (\Throwable) {
            // Ignore if index already exists.
        }
    }

    private function safeDropUnique(string $table, string $indexName): void
    {
        try {
            Schema::table($table, function (Blueprint $blueprint) use ($indexName) {
                $blueprint->dropUnique($indexName);
            });
        } catch (\Throwable) {
            // Ignore if index doesn't exist.
        }
    }
};

