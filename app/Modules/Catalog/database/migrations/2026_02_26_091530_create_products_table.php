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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->uuid('store_id')->nullable()->index();
            $table->string('shopify_product_id')->nullable();
            $table->string('title')->index();
            $table->text('description')->nullable();
            $table->string('handle');
            $table->string('slug');
            $table->foreignId('vendor_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_type_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('status', ['draft', 'active', 'archived'])->default('draft')->index();
            $table->json('tags')->nullable();
            $table->boolean('isGiftCard')->default(false);
            $table->boolean('hasOnlyDefaultVariant')->default(false);
            $table->string('warehouse_location')->comment('Location of the product where it is viewable')->nullable();

            $table->json('featured_image')->nullable();

            $table->decimal('price_min', 12, 2)->nullable();
            $table->decimal('price_max', 12, 2)->nullable();

            $table->string('seo_title')->nullable();
            $table->string('seo_description')->nullable();

            $table->json('raw_payload')->nullable();
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamp('shopify_created_at')->nullable()->index();
            $table->timestamp('shopify_updated_at')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['store_id', 'status']);
            $table->index(['store_id', 'slug']);
            $table->unique(['store_id', 'shopify_product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
