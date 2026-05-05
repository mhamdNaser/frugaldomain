<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('icon_downloads', function (Blueprint $table) {
            $table->unsignedBigInteger('icon_file_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('icon_downloads', function (Blueprint $table) {
            $table->unsignedBigInteger('icon_file_id')->nullable(false)->change();
        });
    }
};
