<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Keep newest menu row per (store_id, shopify_menu_id) before adding unique index.
        DB::statement("
            DELETE m1
            FROM menus m1
            INNER JOIN menus m2
                ON m1.store_id = m2.store_id
               AND m1.shopify_menu_id = m2.shopify_menu_id
               AND m1.id < m2.id
            WHERE m1.shopify_menu_id IS NOT NULL
        ");

        // Keep newest menu item row per (menu_id, shopify_menu_item_id) before adding unique index.
        DB::statement("
            DELETE mi1
            FROM menu_items mi1
            INNER JOIN menu_items mi2
                ON mi1.menu_id = mi2.menu_id
               AND mi1.shopify_menu_item_id = mi2.shopify_menu_item_id
               AND mi1.id < mi2.id
            WHERE mi1.shopify_menu_item_id IS NOT NULL
        ");

        Schema::table('menus', function (Blueprint $table) {
            $table->unique(['store_id', 'shopify_menu_id'], 'menus_store_shopify_unique');
        });

        Schema::table('menu_items', function (Blueprint $table) {
            $table->unique(['menu_id', 'shopify_menu_item_id'], 'menu_items_menu_shopify_unique');
        });
    }

    public function down(): void
    {
        Schema::table('menu_items', function (Blueprint $table) {
            $table->dropUnique('menu_items_menu_shopify_unique');
        });

        Schema::table('menus', function (Blueprint $table) {
            $table->dropUnique('menus_store_shopify_unique');
        });
    }
};

