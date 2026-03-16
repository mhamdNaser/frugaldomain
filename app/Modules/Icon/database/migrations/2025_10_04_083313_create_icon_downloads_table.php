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
        Schema::create('icon_downloads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->index();
            $table->foreignId('icon_id')->index();
            $table->foreignId('icon_file_id')->index();
            $table->enum('download_type', ['svg', 'png']);
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('downloaded_at')->useCurrent();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['icon_id', 'download_type']);

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
            $table->foreign('icon_id')
                ->references('id')
                ->on('icons')
                ->cascadeOnDelete();
            $table->foreign('icon_file_id')
                ->references('id')
                ->on('icon_files')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('icon_downloads');
    }
};
