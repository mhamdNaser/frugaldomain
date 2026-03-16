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
        Schema::create('store_brandings', function (Blueprint $table) {
            $table->id();
            $table->uuid('store_id')->unique();
            $table->string('logo_url')->nullable();
            $table->string('splash_image_url')->nullable();
            $table->string('favicon_url')->nullable();
            $table->string('primary_color', 20)->nullable();
            $table->string('secondary_color', 20)->nullable();
            $table->string('dark_primary_color', 20)->nullable();
            $table->string('dark_secondary_color', 20)->nullable();
            $table->string('font_family')->nullable();
            $table->json('extra_styles')->nullable();
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
        Schema::dropIfExists('store_brandings');
    }
};
