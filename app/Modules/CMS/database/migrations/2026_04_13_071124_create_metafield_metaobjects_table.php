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
        Schema::create('metafield_metaobjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('metafield_id')
                ->constrained('metafields')
                ->cascadeOnDelete();
            $table->foreignId('metaobject_id')
                ->constrained('metaobjects')
                ->cascadeOnDelete();

            $table->unique(['metafield_id', 'metaobject_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('metafield_metaobjects');
    }
};
