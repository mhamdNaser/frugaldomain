<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('files')) {
            return;
        }

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE `files` MODIFY `path` TEXT NULL, MODIFY `url` TEXT NULL');

            return;
        }

        Schema::table('files', function (Blueprint $table) {
            $table->text('path')->nullable()->change();
            $table->text('url')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('files')) {
            return;
        }

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE `files` MODIFY `path` VARCHAR(255) NULL, MODIFY `url` VARCHAR(255) NULL');

            return;
        }

        Schema::table('files', function (Blueprint $table) {
            $table->string('path')->nullable()->change();
            $table->string('url')->nullable()->change();
        });
    }
};
