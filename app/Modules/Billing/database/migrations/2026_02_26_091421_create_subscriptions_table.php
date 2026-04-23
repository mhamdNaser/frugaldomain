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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->uuid('store_id')->nullable()->index();
            $table->foreignId('plan_id')->nullable()->constrained();
            $table->string('status')->index();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ends_at')->nullable()->index();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['store_id', 'status']);
            $table->index(['store_id', 'ends_at']);
            $table->index(['store_id', 'plan_id']);

            $table->unique(['store_id', 'status'], 'unique_active_subscription')
                ->where('status', 'active');

            $table->foreign('store_id')
                ->references('id')
                ->on('stores')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
