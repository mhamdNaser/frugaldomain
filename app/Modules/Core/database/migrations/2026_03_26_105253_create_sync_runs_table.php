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
        Schema::create('sync_runs', function (Blueprint $table) {
            $table->id();
            $table->uuid('store_id')->index();
            $table->string('type')->index();
            $table->string('trigger')->index();
            $table->string('status')->index(); // pending, running, completed, failed
            $table->string('batch_id')->nullable()->index();
            $table->unsignedInteger('fetched_count')->default(0);
            $table->unsignedInteger('synced_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->text('error_message')->nullable();
            $table->string('correlation_id')->nullable()->index();
            $table->timestamp('started_at')->nullable()->index();
            $table->timestamp('finished_at')->nullable()->index();
            $table->timestamps();
            $table->index(['store_id', 'type']);
            $table->index(['store_id', 'status']);

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
        Schema::dropIfExists('sync_runs');
    }
};
