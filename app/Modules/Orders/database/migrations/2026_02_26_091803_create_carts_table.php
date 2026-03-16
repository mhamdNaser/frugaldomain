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
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->uuid('store_id')->nullable()->index();
            $table->foreignId('customer_id')->nullable()->index();
            $table->string('status')->default('active')->index();
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->string('currency')->default('USD');
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['store_id', 'status']);
            $table->index(['customer_id', 'status']);
            $table->index(['store_id', 'customer_id', 'status']);

            $table->foreign('store_id')
                ->references('id')
                ->on('stores')
                ->nullOnDelete();
            $table->foreign('customer_id')
                ->references('id')
                ->on('customers')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('carts');
    }
};
