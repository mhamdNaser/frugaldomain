<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_page_views', function (Blueprint $table) {
            $table->id();
            $table->string('session_id', 64)->index();
            $table->string('path', 500);
            $table->timestamp('viewed_at')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_page_views');
    }
};
