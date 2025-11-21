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
         if (Schema::hasColumn('users', 'role')) {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }

    Schema::table('users', function (Blueprint $table) {

        if (!Schema::hasColumn('users', 'role_id')) {
            $table->foreignId('role_id')->nullable()->constrained('roles')->onDelete('set null');
        }

        if (!Schema::hasColumn('users', 'is_active')) {
            $table->boolean('is_active')->default(true);
        }

        if (!Schema::hasColumn('users', 'is_verified')) {
            $table->boolean('is_verified')->default(false);
        }

        if (!Schema::hasColumn('users', 'last_login_at')) {
            $table->timestamp('last_login_at')->nullable();
        }

        if (!Schema::hasColumn('users', 'timezone')) {
            $table->string('timezone')->default('UTC');
        }

        if (!Schema::hasColumn('users', 'settings')) {
            $table->json('settings')->nullable();
        }
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
      Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropColumn(['role_id', 'is_active', 'is_verified', 'last_login_at', 'timezone', 'settings']);
            $table->string('role')->default('buyer');
        });
    }
};
