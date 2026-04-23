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
        Schema::create('metaobjects', function (Blueprint $table) {
            $table->id();
            $table->uuid('store_id')->index();
            $table->string('shopify_metaobject_id')->unique();
            $table->string('type');
            $table->json('fields');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('metaobjects');
    }
};
