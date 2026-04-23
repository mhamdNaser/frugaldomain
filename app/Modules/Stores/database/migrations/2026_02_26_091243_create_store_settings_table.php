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
        Schema::create('store_settings', function (Blueprint $table) {
            $table->id();
            $table->uuid('store_id')->unique();
            $table->boolean('allow_guest_checkout')->default(false);
            $table->boolean('enable_cod')->default(false);
            $table->boolean('enable_stripe')->default(false);
            $table->boolean('tax_included')->default(false);
            $table->string('currency_format')->nullable();
            $table->string('weight_unit')->nullable();
            $table->string('default_language')->default('en');
            $table->boolean('push_notifications_enabled')->default(false);
            $table->json('extra_settings')->nullable();
            $table->timestamps();

            // Foreign key constraint for store_id referencing stores table
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
        Schema::dropIfExists('store_settings');
    }
};
