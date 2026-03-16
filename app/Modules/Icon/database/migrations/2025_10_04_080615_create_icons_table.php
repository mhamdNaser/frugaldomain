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
        Schema::create('icons', function (Blueprint $table) {
            $table->id();

            $table->string('title');
            $table->longText('file_svg');
            $table->longText('file_png');
            $table->text('description')->nullable();

            $table->foreignId('category_id')->index()->nullable();
            $table->foreignId('user_id')->index()->nullable();

            $table->boolean('is_premium')->default(false);
            $table->integer('download_count')->default(0);
            $table->json('tags')->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            // Constraints
            $table->foreign('category_id')
                ->references('id')
                ->on('icon_categories')
                ->cascadeOnDelete();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('icons');
    }
};
