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
        Schema::create('sync_metas', function (Blueprint $table) {
            $table->id();
            $table->uuid('store_id')->nullable()->index();
            $table->string('entity_type')->index();                     // customers, orders, products, etc
            $table->timestamp('last_synced_at')->nullable();
            $table->string('sync_status')->default('idle')->index();    // idle, syncing, completed, failed
            $table->unsignedInteger('processed_count')->default(0);
            $table->string('cursor')->nullable();                       // pagination cursor from provider
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->unique(['store_id', 'entity_type']);

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
        Schema::dropIfExists('sync_metas');
    }
};
