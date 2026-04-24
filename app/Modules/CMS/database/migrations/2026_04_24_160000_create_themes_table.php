<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('themes')) {
            return;
        }

        Schema::create('themes', function (Blueprint $table) {
            $table->id();
            $table->uuid('store_id')->nullable()->index();
            $table->string('shopify_theme_id')->index();
            $table->string('name')->nullable();
            $table->string('role')->nullable()->index();
            $table->boolean('processing')->default(false)->index();
            $table->boolean('previewable')->default(false)->index();
            $table->json('raw_payload')->nullable();
            $table->timestamp('shopify_created_at')->nullable()->index();
            $table->timestamp('shopify_updated_at')->nullable()->index();
            $table->timestamps();

            $table->unique(['store_id', 'shopify_theme_id'], 'themes_store_shopify_theme_unique');
            $table->foreign('store_id')->references('id')->on('stores')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('themes');
    }
};

