<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('metaobject_definitions', function (Blueprint $table) {
            $table->id();
            $table->uuid('store_id')->nullable()->index();
            $table->string('shopify_metaobject_definition_id')->nullable()->index();
            $table->string('type')->nullable()->index();
            $table->string('name')->nullable();
            $table->string('display_name_key')->nullable();
            $table->json('access')->nullable();
            $table->json('capabilities')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamps();

            $table->unique(['store_id', 'shopify_metaobject_definition_id'], 'metaobject_definitions_store_shopify_unique');
            $table->foreign('store_id')->references('id')->on('stores')->nullOnDelete();
        });

        Schema::create('metaobject_definition_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('metaobject_definition_id')->nullable()->index();
            $table->string('field_key')->index();
            $table->string('name')->nullable();
            $table->string('type')->nullable()->index();
            $table->boolean('required')->default(false)->index();
            $table->json('validations')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamps();

            $table->unique(['metaobject_definition_id', 'field_key'], 'metaobject_definition_fields_unique');
            $table->foreign('metaobject_definition_id')
                ->references('id')
                ->on('metaobject_definitions')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('metaobject_definition_fields');
        Schema::dropIfExists('metaobject_definitions');
    }
};

