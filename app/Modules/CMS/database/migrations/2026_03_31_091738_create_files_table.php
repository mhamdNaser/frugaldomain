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
        Schema::create('files', function (Blueprint $table) {
            $table->id();
            $table->uuid('store_id')->nullable()->index();
            $table->string('disk')->default('public'); // local, s3, etc
            $table->string('url')->nullable();
            $table->string('path')->nullable();
            $table->string('width')->nullable();
            $table->string('height')->nullable();
            $table->text('altText')->nullable();

            $table->string('mime_type')->nullable(); // image/png, video/mp4
            $table->unsignedBigInteger('size')->nullable(); // bytes

            $table->string('type')->nullable()->index(); // image, video, document
            $table->string('role')->nullable()->index(); // product_image, thumbnail, logo, banner, etc
            $table->unsignedInteger('position')->default(0); 

            $table->nullableMorphs('fileable');
            $table->json('meta')->nullable();
            $table->unsignedBigInteger('shopify_id')->nullable()->index();

            $table->timestamps();
            $table->softDeletes();

            // 🔹 Indexes for performance
            $table->index(['store_id', 'type']);
            $table->index(['fileable_type', 'fileable_id'], 'fm_fileable_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('files');
    }
};
