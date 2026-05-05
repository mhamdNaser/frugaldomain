<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shopify_outbound_syncs', function (Blueprint $table) {
            $table->id();
            $table->uuid('store_id')->index();
            $table->string('entity_type')->index();
            $table->string('entity_id')->index();
            $table->string('action')->index();
            $table->string('handler');
            $table->string('status')->default('pending')->index(); // pending, processing, retrying, synced, failed, dead
            $table->unsignedTinyInteger('priority')->default(5)->index();
            $table->unsignedInteger('attempts')->default(0);
            $table->unsignedInteger('max_attempts')->default(5);
            $table->timestamp('available_at')->nullable()->index();
            $table->timestamp('locked_at')->nullable()->index();
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamp('next_retry_at')->nullable()->index();
            $table->timestamp('synced_at')->nullable()->index();
            $table->string('correlation_id')->nullable()->index();
            $table->string('idempotency_key')->index();
            $table->string('shopify_resource_id')->nullable()->index();
            $table->json('payload')->nullable();
            $table->longText('response_payload')->nullable();
            $table->string('last_error_code')->nullable()->index();
            $table->text('last_error_message')->nullable();
            $table->timestamps();

            $table->unique(['store_id', 'idempotency_key'], 'shopify_outbound_unique_idempotency');
            $table->index(['store_id', 'status', 'available_at'], 'shopify_outbound_store_status_available');

            $table->foreign('store_id')
                ->references('id')
                ->on('stores')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shopify_outbound_syncs');
    }
};

