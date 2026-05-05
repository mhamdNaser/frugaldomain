<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shopify_outbound_sync_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outbound_sync_id')
                ->constrained('shopify_outbound_syncs')
                ->cascadeOnDelete();
            $table->unsignedInteger('attempt_number');
            $table->string('status')->index(); // success, failed
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->unsignedSmallInteger('http_status')->nullable()->index();
            $table->json('request_payload')->nullable();
            $table->longText('response_payload')->nullable();
            $table->string('error_code')->nullable()->index();
            $table->text('error_message')->nullable();
            $table->boolean('retryable')->default(false)->index();
            $table->timestamps();

            $table->unique(['outbound_sync_id', 'attempt_number'], 'shopify_outbound_attempt_unique');
            $table->index(['outbound_sync_id', 'status'], 'shopify_outbound_attempt_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shopify_outbound_sync_attempts');
    }
};

