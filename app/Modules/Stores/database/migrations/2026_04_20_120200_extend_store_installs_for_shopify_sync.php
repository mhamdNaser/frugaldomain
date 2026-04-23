<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_installs', function (Blueprint $table) {
            if (!Schema::hasColumn('store_installs', 'shopify_store_id')) {
                $table->string('shopify_store_id')->nullable()->after('shop')->index();
            }
            if (!Schema::hasColumn('store_installs', 'raw_payload')) {
                $table->json('raw_payload')->nullable()->after('access_token');
            }
        });
    }

    public function down(): void
    {
        Schema::table('store_installs', function (Blueprint $table) {
            foreach (['shopify_store_id', 'raw_payload'] as $column) {
                if (Schema::hasColumn('store_installs', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

