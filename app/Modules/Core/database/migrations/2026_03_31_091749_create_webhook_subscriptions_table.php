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
        Schema::create('webhook_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->uuid('store_id')->nullable()->index();
            $table->string('event'); // products/create, orders/update
            $table->string('topic');  // Shopify topic أو provider topic
            $table->string('callback_url');
            $table->boolean('is_active')->default(true);
            $table->string('provider')->default('shopify');
            $table->timestamps();

            $table->unique(['store_id', 'event', 'provider']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_subscriptions');
    }
};
