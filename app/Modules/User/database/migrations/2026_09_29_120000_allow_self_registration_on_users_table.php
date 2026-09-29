<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The public sign-up form only asks for a name, an email and a password, so the
 * profile fields it cannot provide must be optional (they are filled in later
 * from the profile page).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('medium_name')->nullable()->change();
            $table->bigInteger('phone')->nullable()->change();
            $table->boolean('status')->default(1)->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('medium_name')->nullable(false)->change();
            $table->bigInteger('phone')->nullable(false)->change();
            $table->boolean('status')->default(null)->change();
        });
    }
};
