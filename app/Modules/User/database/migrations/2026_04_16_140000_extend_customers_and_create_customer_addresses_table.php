<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'display_name')) {
                $table->string('display_name')->nullable()->after('last_name');
            }
            if (!Schema::hasColumn('customers', 'state')) {
                $table->string('state')->nullable()->after('status')->index();
            }
            if (!Schema::hasColumn('customers', 'tags')) {
                $table->json('tags')->nullable()->after('state');
            }
            if (!Schema::hasColumn('customers', 'note')) {
                $table->text('note')->nullable()->after('tags');
            }
            if (!Schema::hasColumn('customers', 'verified_email')) {
                $table->boolean('verified_email')->default(false)->after('note');
            }
            if (!Schema::hasColumn('customers', 'tax_exempt')) {
                $table->boolean('tax_exempt')->default(false)->after('verified_email');
            }
            if (!Schema::hasColumn('customers', 'orders_count')) {
                $table->unsignedInteger('orders_count')->default(0)->after('tax_exempt');
            }
            if (!Schema::hasColumn('customers', 'total_spent')) {
                $table->decimal('total_spent', 12, 2)->default(0)->after('orders_count');
            }
            if (!Schema::hasColumn('customers', 'currency')) {
                $table->string('currency')->nullable()->after('total_spent');
            }
            if (!Schema::hasColumn('customers', 'default_address_id')) {
                $table->foreignId('default_address_id')->nullable()->after('currency')->index();
            }
            if (!Schema::hasColumn('customers', 'raw_payload')) {
                $table->json('raw_payload')->nullable()->after('default_address_id');
            }
            if (!Schema::hasColumn('customers', 'shopify_created_at')) {
                $table->timestamp('shopify_created_at')->nullable()->after('raw_payload')->index();
            }
            if (!Schema::hasColumn('customers', 'shopify_updated_at')) {
                $table->timestamp('shopify_updated_at')->nullable()->after('shopify_created_at')->index();
            }
        });

        if (!Schema::hasTable('customer_addresses')) {
            Schema::create('customer_addresses', function (Blueprint $table) {
                $table->id();
                $table->uuid('store_id')->nullable()->index();
                $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
                $table->string('shopify_customer_address_id')->index();
                $table->string('first_name')->nullable();
                $table->string('last_name')->nullable();
                $table->string('name')->nullable();
                $table->string('company')->nullable();
                $table->string('address1')->nullable();
                $table->string('address2')->nullable();
                $table->string('city')->nullable()->index();
                $table->string('province')->nullable();
                $table->string('province_code')->nullable();
                $table->string('country')->nullable()->index();
                $table->string('country_code')->nullable()->index();
                $table->string('zip')->nullable()->index();
                $table->string('phone')->nullable()->index();
                $table->boolean('is_default')->default(false)->index();
                $table->json('raw_payload')->nullable();
                $table->timestamps();

                $table->unique(['customer_id', 'shopify_customer_address_id'], 'customer_address_shopify_unique');
                $table->foreign('store_id')->references('id')->on('stores')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_addresses');

        Schema::table('customers', function (Blueprint $table) {
            foreach ([
                'display_name', 'state', 'tags', 'note', 'verified_email', 'tax_exempt', 'orders_count',
                'total_spent', 'currency', 'default_address_id', 'raw_payload', 'shopify_created_at',
                'shopify_updated_at',
            ] as $column) {
                if (Schema::hasColumn('customers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
