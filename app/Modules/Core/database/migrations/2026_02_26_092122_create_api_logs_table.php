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
        Schema::create('api_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('store_id')->nullable()->index();
            $table->string('provider')->nullable()->index();    // shopify, stripe, internal, etc
            $table->string('endpoint')->index();
            $table->string('method')->nullable();
            $table->text('request_payload')->nullable();
            $table->text('response_payload')->nullable();
            $table->integer('status_code')->index();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->string('correlation_id')->nullable()->index();
            $table->timestamps();

            $table->index(['store_id', 'created_at']);
            $table->index(['store_id', 'status_code']);

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
        Schema::dropIfExists('api_logs');
    }
};
