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
        Schema::create('app_sessions', function (Blueprint $table) {
            $table->id();
            $table->uuid('store_id')->nullable()->index();
            $table->foreignId('customer_id')->nullable()->index();
            $table->foreignId('device_id')->nullable()->index();
            $table->string('access_token')->unique();
            $table->string('refresh_token')->unique()->nullable();
            $table->timestamp('expires_at')->index();
            $table->boolean('is_revoked')->default(false)->index();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('last_used_at')->nullable()->index();
            $table->timestamps();

            $table->index(['customer_id', 'is_revoked']);
            $table->index(['store_id', 'customer_id']);

            $table->foreign('store_id')
                ->references('id')
                ->on('stores')
                ->cascadeOnDelete();
            $table->foreign('customer_id')
                ->references('id')
                ->on('customers')
                ->cascadeOnDelete();
            $table->foreign('device_id')
                ->references('id')
                ->on('devices')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_sessions');
    }
};
