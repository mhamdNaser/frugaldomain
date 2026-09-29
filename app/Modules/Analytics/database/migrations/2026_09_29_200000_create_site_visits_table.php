<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per browser session. Raw IPs are never stored, only a salted hash.
        Schema::create('site_visits', function (Blueprint $table) {
            $table->id();
            $table->string('visitor_id', 64);
            $table->string('session_id', 64)->unique();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('ip_hash', 64)->nullable();
            $table->string('country_code', 2)->nullable();
            $table->string('country_name')->nullable();
            $table->string('city')->nullable();
            $table->string('device_type', 16)->default('desktop');
            $table->string('browser', 64)->nullable();
            $table->string('os', 64)->nullable();
            $table->string('referrer', 500)->nullable();
            $table->string('landing_path', 500);
            $table->string('exit_path', 500);
            $table->unsignedInteger('page_views')->default(1);
            $table->timestamp('started_at');
            $table->timestamp('last_seen_at');
            $table->unsignedInteger('duration_seconds')->default(0);
            $table->boolean('is_bounce')->default(true);
            $table->timestamps();

            $table->index('started_at');
            $table->index('last_seen_at');
            $table->index('visitor_id');
            $table->index('country_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_visits');
    }
};
