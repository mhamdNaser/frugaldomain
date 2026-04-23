<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->uuid('store_id')->nullable()->index();
            $table->foreignId('customer_id')->nullable()->index();
            $table->string('device_token')->unique();
            $table->string('platform')->index(); // ios, android, web
            $table->string('app_version')->nullable();
            $table->string('device_name')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('last_active_at')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('store_id')
                ->references('id')
                ->on('stores')
                ->cascadeOnDelete();
            $table->foreign('customer_id')
                ->references('id')
                ->on('customers')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
