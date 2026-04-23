<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_marketing_consents', function (Blueprint $table) {
            $table->id();
            $table->uuid('store_id')->nullable()->index();
            $table->foreignId('customer_id')->nullable()->index();
            $table->string('shopify_customer_id')->nullable()->index();
            $table->string('email_marketing_state')->nullable()->index();
            $table->string('email_marketing_opt_in_level')->nullable();
            $table->timestamp('email_consent_updated_at')->nullable()->index();
            $table->string('sms_marketing_state')->nullable()->index();
            $table->string('sms_marketing_opt_in_level')->nullable();
            $table->timestamp('sms_consent_updated_at')->nullable()->index();
            $table->string('source_location_id')->nullable()->index();
            $table->json('raw_payload')->nullable();
            $table->timestamps();

            $table->unique(['store_id', 'shopify_customer_id'], 'customer_marketing_consents_store_shopify_unique');
            $table->foreign('store_id')->references('id')->on('stores')->nullOnDelete();
            $table->foreign('customer_id')->references('id')->on('customers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_marketing_consents');
    }
};

