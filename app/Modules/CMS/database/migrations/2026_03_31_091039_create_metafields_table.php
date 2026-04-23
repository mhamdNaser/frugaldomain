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
        Schema::create('metafields', function (Blueprint $table) {
            $table->id();
            $table->uuid('store_id')->index();
            $table->string('shopify_metafield_id')->nullable()->index();
            $table->morphs('metafieldable'); // product_id, variant_id, collection_id, vendor_id
            $table->string('key');
            $table->string('namespace'); // custom, seo, etc.
            $table->json('value')->nullable();
            $table->string('type', 50)->default('string');
            $table->timestamps();

            $table->unique([
                'store_id',
                'metafieldable_type',
                'metafieldable_id',
                'namespace',
                'key'
            ], 'mf_unique_store_type_id_ns_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('metafields');
    }
};
