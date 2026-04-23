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
        Schema::create('stores', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('owner_id')->nullable()->index();
            $table->unsignedBigInteger('shopify_store_id')->nullable()->index();
            $table->string('shopify_domain')->nullable()->unique()->index();
            $table->text('shopify_access_token')->nullable(); // encrypted
            $table->string('name')->nullable();
            $table->string('email')->nullable()->index();
            $table->string('currency', 10)->nullable();
            $table->string('timezone')->nullable();
            $table->foreignId('plan_id')->nullable()->constrained();
            $table->string('status')->default('active')->index();// active, suspended, uninstalled
            $table->timestamp('installed_at')->nullable();
            $table->timestamp('uninstalled_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['shopify_store_id']);

            // Foreign key constraint for owner_id referencing users table
            $table->foreign('owner_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stores');
    }
};
