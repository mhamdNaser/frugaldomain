<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('theme_assets')) {
            return;
        }

        Schema::create('theme_assets', function (Blueprint $table) {
            $table->id();
            $table->uuid('store_id')->nullable()->index();
            $table->foreignId('theme_id')->nullable()->index();
            $table->string('shopify_asset_id')->nullable()->index();
            $table->string('filename')->nullable()->index();
            $table->string('content_type')->nullable()->index();
            $table->unsignedBigInteger('size')->nullable();
            $table->text('url')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamp('shopify_created_at')->nullable()->index();
            $table->timestamp('shopify_updated_at')->nullable()->index();
            $table->timestamps();

            $table->unique(['theme_id', 'filename'], 'theme_assets_theme_filename_unique');
            $table->foreign('store_id')->references('id')->on('stores')->nullOnDelete();
            $table->foreign('theme_id')->references('id')->on('themes')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('theme_assets');
    }
};

