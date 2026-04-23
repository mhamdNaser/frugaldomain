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
        Schema::create('sync_errors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sync_run_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('sync_job_id')->nullable()->constrained()->cascadeOnDelete();
            $table->uuid('store_id')->nullable()->index();
            $table->string('type')->nullable(); // api, validation, timeout
            $table->text('message');
            $table->json('context')->nullable(); // request, payload, response
            $table->string('file')->nullable();
            $table->integer('line')->nullable();
            $table->timestamps();

            $table->index(['sync_run_id']);
            $table->index(['sync_job_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sync_errors');
    }
};
