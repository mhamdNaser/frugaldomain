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
        Schema::create('webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('store_id')->nullable()->index();
            $table->string('provider');                             // shopify, stripe, etc
            $table->string('topic')->index();
            $table->string('external_id')->nullable();              // event id from provider (idempotency key)
            $table->text('payload');
            $table->string('status')->default('pending')->index();  // pending, processing, processed, failed
            $table->unsignedInteger('attempts')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamp('received_at')->nullable()->index();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->unique(['store_id', 'provider', 'external_id']);

            $table->foreign('store_id')
                ->references('id')
                ->on('stores')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_logs');
    }
};
