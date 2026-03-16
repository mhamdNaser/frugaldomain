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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->uuid('store_id')->nullable()->index();
            $table->foreignId('customer_id')->nullable()->index();
            $table->string('shopify_order_id')->nullable();
            $table->string('order_number')->nullable();
            $table->string('status')->default('pending')->index();
            $table->string('payment_status')->default('pending')->index();
            $table->string('fulfillment_status')->default('pending')->index();
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('tax', 10, 2)->default(0);
            $table->decimal('shipping', 10, 2)->default(0);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->string('currency')->default('USD');
            $table->timestamp('placed_at')->nullable()->index();
            $table->timestamps();

            $table->index(['store_id', 'status']);
            $table->index(['store_id', 'payment_status']);
            $table->index(['store_id', 'placed_at']);

            $table->unique(['store_id', 'order_number']);
            $table->unique(['store_id', 'shopify_order_id']);

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
        Schema::dropIfExists('orders');
    }
};
