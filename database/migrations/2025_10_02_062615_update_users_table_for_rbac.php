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
        Schema::table('users', function (Blueprint $table) {
            // Remove old role column if exists
            if (Schema::hasColumn('users', 'role')) {
                $table->dropColumn('role');
            }
            
            // Add new RBAC fields
            $table->foreignId('role_id')->nullable()->constrained('roles')->onDelete('set null');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_verified')->default(false);
            $table->timestamp('last_login_at')->nullable();
            $table->string('timezone')->default('UTC');
            $table->json('settings')->nullable();
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
