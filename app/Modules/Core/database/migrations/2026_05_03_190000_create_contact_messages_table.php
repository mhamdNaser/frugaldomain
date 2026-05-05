<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->index();
            $table->string('subject')->index();
            $table->longText('message');
            $table->string('ip_address', 64)->nullable()->index();
            $table->text('user_agent')->nullable();
            $table->boolean('email_sent')->default(false)->index();
            $table->text('email_error')->nullable();
            $table->timestamp('email_sent_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_messages');
    }
};

