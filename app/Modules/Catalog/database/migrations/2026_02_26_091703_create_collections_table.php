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
        Schema::create('collections', function (Blueprint $table) {
            $table->id();
            $table->uuid('store_id')->nullable()->index();
            $table->string('shopify_collection_id')->nullable();
            $table->string('title')->index();
            $table->string('handle')->nullable();
            $table->text('description')->nullable();
            $table->string('image_url')->nullable();
            $table->string('image_alt')->nullable();
            $table->enum('type', ['manual', 'automated', 'smart'])->default('manual');

            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->string('seo_title')->nullable();
            $table->string('seo_description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['store_id', 'shopify_collection_id']);
            $table->unique(['store_id', 'handle']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('collections');
    }
};
