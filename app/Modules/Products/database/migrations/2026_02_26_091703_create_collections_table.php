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
            $table->text('description')->nullable();
            $table->string('image_url')->nullable();
            $table->string('handle')->nullable();
            $table->string('type')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['store_id', 'shopify_collection_id']);
            $table->index(['store_id', 'title']);

            $table->unique(['store_id', 'shopify_collection_id']);
            $table->unique(['store_id', 'handle']);

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
        Schema::dropIfExists('collections');
    }
};
