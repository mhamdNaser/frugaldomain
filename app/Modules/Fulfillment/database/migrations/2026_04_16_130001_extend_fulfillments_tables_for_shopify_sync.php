<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fulfillments', function (Blueprint $table) {
            if (!Schema::hasColumn('fulfillments', 'store_id')) {
                $table->uuid('store_id')->nullable()->after('id')->index();
            }
            if (!Schema::hasColumn('fulfillments', 'order_id')) {
                $table->foreignId('order_id')->nullable()->after('store_id')->constrained('orders')->nullOnDelete();
            }
            if (!Schema::hasColumn('fulfillments', 'fulfillment_service_id')) {
                $table->foreignId('fulfillment_service_id')->nullable()->after('order_id')->constrained('fulfillment_services')->nullOnDelete();
            }
            if (!Schema::hasColumn('fulfillments', 'shopify_fulfillment_id')) {
                $table->string('shopify_fulfillment_id')->nullable()->after('fulfillment_service_id')->index();
            }
            if (!Schema::hasColumn('fulfillments', 'shopify_order_id')) {
                $table->string('shopify_order_id')->nullable()->after('shopify_fulfillment_id')->index();
            }
            if (!Schema::hasColumn('fulfillments', 'name')) {
                $table->string('name')->nullable()->after('shopify_order_id');
            }
            if (!Schema::hasColumn('fulfillments', 'status')) {
                $table->string('status')->nullable()->after('name')->index();
            }
            if (!Schema::hasColumn('fulfillments', 'shipment_status')) {
                $table->string('shipment_status')->nullable()->after('status')->index();
            }
            if (!Schema::hasColumn('fulfillments', 'tracking_company')) {
                $table->string('tracking_company')->nullable()->after('shipment_status');
            }
            if (!Schema::hasColumn('fulfillments', 'tracking_number')) {
                $table->string('tracking_number')->nullable()->after('tracking_company');
            }
            if (!Schema::hasColumn('fulfillments', 'tracking_url')) {
                $table->text('tracking_url')->nullable()->after('tracking_number');
            }
            if (!Schema::hasColumn('fulfillments', 'raw_payload')) {
                $table->json('raw_payload')->nullable()->after('tracking_url');
            }
            if (!Schema::hasColumn('fulfillments', 'shopify_created_at')) {
                $table->timestamp('shopify_created_at')->nullable()->after('raw_payload')->index();
            }
            if (!Schema::hasColumn('fulfillments', 'shopify_updated_at')) {
                $table->timestamp('shopify_updated_at')->nullable()->after('shopify_created_at')->index();
            }
        });

        Schema::table('fulfillment_items', function (Blueprint $table) {
            if (!Schema::hasColumn('fulfillment_items', 'store_id')) {
                $table->uuid('store_id')->nullable()->after('id')->index();
            }
            if (!Schema::hasColumn('fulfillment_items', 'fulfillment_id')) {
                $table->foreignId('fulfillment_id')->nullable()->after('store_id')->constrained('fulfillments')->cascadeOnDelete();
            }
            if (!Schema::hasColumn('fulfillment_items', 'order_item_id')) {
                $table->foreignId('order_item_id')->nullable()->after('fulfillment_id')->constrained('order_items')->nullOnDelete();
            }
            if (!Schema::hasColumn('fulfillment_items', 'shopify_line_item_id')) {
                $table->string('shopify_line_item_id')->nullable()->after('order_item_id')->index();
            }
            if (!Schema::hasColumn('fulfillment_items', 'quantity')) {
                $table->integer('quantity')->default(0)->after('shopify_line_item_id');
            }
            if (!Schema::hasColumn('fulfillment_items', 'raw_payload')) {
                $table->json('raw_payload')->nullable()->after('quantity');
            }
        });

        Schema::table('fulfillment_tracking', function (Blueprint $table) {
            if (!Schema::hasColumn('fulfillment_tracking', 'store_id')) {
                $table->uuid('store_id')->nullable()->after('id')->index();
            }
            if (!Schema::hasColumn('fulfillment_tracking', 'fulfillment_id')) {
                $table->foreignId('fulfillment_id')->nullable()->after('store_id')->constrained('fulfillments')->cascadeOnDelete();
            }
            if (!Schema::hasColumn('fulfillment_tracking', 'company')) {
                $table->string('company')->nullable()->after('fulfillment_id');
            }
            if (!Schema::hasColumn('fulfillment_tracking', 'number')) {
                $table->string('number')->nullable()->after('company')->index();
            }
            if (!Schema::hasColumn('fulfillment_tracking', 'url')) {
                $table->text('url')->nullable()->after('number');
            }
            if (!Schema::hasColumn('fulfillment_tracking', 'raw_payload')) {
                $table->json('raw_payload')->nullable()->after('url');
            }
        });
    }

    public function down(): void
    {
        foreach ([
            'fulfillment_tracking' => ['store_id', 'fulfillment_id', 'company', 'number', 'url', 'raw_payload'],
            'fulfillment_items' => ['store_id', 'fulfillment_id', 'order_item_id', 'shopify_line_item_id', 'quantity', 'raw_payload'],
            'fulfillments' => [
                'store_id', 'order_id', 'fulfillment_service_id', 'shopify_fulfillment_id', 'shopify_order_id',
                'name', 'status', 'shipment_status', 'tracking_company', 'tracking_number', 'tracking_url',
                'raw_payload', 'shopify_created_at', 'shopify_updated_at',
            ],
        ] as $table => $columns) {
            Schema::table($table, function (Blueprint $blueprint) use ($table, $columns) {
                foreach ($columns as $column) {
                    if (Schema::hasColumn($table, $column)) {
                        $blueprint->dropColumn($column);
                    }
                }
            });
        }
    }
};
