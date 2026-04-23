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
        Schema::create('store_installs', function (Blueprint $table) {
            $table->id();
            $table->uuid('store_id')->nullable()->index();

            $table->string('shop')->index();
            $table->string('state')->nullable();

            $table->text('scopes')->nullable();

            $table->text('access_token')->nullable();
            $table->timestamp('token_created_at')->nullable();

            $table->timestamp('installed_at')->nullable();
            $table->timestamp('uninstalled_at')->nullable();

            $table->timestamps();

            $table->index(['shop', 'installed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('store_installs');
    }
};
