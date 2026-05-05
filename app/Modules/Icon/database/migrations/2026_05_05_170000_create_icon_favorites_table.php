<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('icon_favorites', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('icon_id');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['user_id', 'icon_id']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('icon_id')->references('id')->on('icons')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('icon_favorites');
    }
};
