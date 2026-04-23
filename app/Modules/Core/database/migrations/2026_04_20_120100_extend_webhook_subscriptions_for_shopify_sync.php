<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('webhook_subscriptions', function (Blueprint $table) {
            if (!Schema::hasColumn('webhook_subscriptions', 'shopify_webhook_id')) {
                $table->string('shopify_webhook_id')->nullable()->after('store_id')->index();
            }
            if (!Schema::hasColumn('webhook_subscriptions', 'endpoint_type')) {
                $table->string('endpoint_type')->nullable()->after('callback_url');
            }
            if (!Schema::hasColumn('webhook_subscriptions', 'format')) {
                $table->string('format')->nullable()->after('endpoint_type');
            }
            if (!Schema::hasColumn('webhook_subscriptions', 'raw_payload')) {
                $table->json('raw_payload')->nullable()->after('provider');
            }
        });
    }

    public function down(): void
    {
        Schema::table('webhook_subscriptions', function (Blueprint $table) {
            foreach (['shopify_webhook_id', 'endpoint_type', 'format', 'raw_payload'] as $column) {
                if (Schema::hasColumn('webhook_subscriptions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

