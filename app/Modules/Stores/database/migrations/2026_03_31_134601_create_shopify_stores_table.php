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
        Schema::create('shopify_stores', function (Blueprint $table) {
            $table->id();

            // ربط مع store عندك
            $table->uuid('store_id');
            $table->uuid('shopify_store_id')->unique();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('domain')->nullable();
            $table->string('myshopify_domain')->nullable();
            $table->string('shop_owner')->nullable();
            $table->string('phone')->nullable();

            // الموقع والعملة
            $table->string('country')->nullable();
            $table->string('country_code', 5)->nullable();
            $table->string('currency', 10)->nullable();
            $table->string('timezone')->nullable();
            $table->string('iana_timezone')->nullable();

            // خطة المتجر
            $table->string('plan_name')->nullable();
            $table->string('plan_display_name')->nullable();

            // خصائص المتجر
            $table->boolean('taxes_included')->default(false);
            $table->boolean('county_taxes')->default(false);
            $table->boolean('has_discounts')->default(false);
            $table->boolean('has_gift_cards')->default(false);
            $table->boolean('multi_location_enabled')->default(false);

            $table->unsignedBigInteger('primary_location_id')->nullable();

            // بيانات JSON كاملة لأي معلومات إضافية
            $table->json('raw_data')->nullable();

            $table->timestamps();

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
        Schema::dropIfExists('shopify_stores');
    }
};
