<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_levels', function (Blueprint $table) {
            if (!Schema::hasColumn('inventory_levels', 'committed')) {
                $table->integer('committed')->default(0)->after('available')->index();
            }
            if (!Schema::hasColumn('inventory_levels', 'incoming')) {
                $table->integer('incoming')->default(0)->after('committed')->index();
            }
            if (!Schema::hasColumn('inventory_levels', 'reserved')) {
                $table->integer('reserved')->default(0)->after('incoming')->index();
            }
            if (!Schema::hasColumn('inventory_levels', 'on_hand')) {
                $table->integer('on_hand')->default(0)->after('reserved')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('inventory_levels', function (Blueprint $table) {
            foreach (['committed', 'incoming', 'reserved', 'on_hand'] as $column) {
                if (Schema::hasColumn('inventory_levels', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

