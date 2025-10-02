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
       Schema::create('users', function (Blueprint $table) {
        $table->id(); // BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
        $table->string('name', 255);
        $table->string('email', 255)->unique();
        $table->timestamp('email_verified_at')->nullable();
        $table->string('password', 255);
        $table->string('phone', 20)->nullable();
        $table->string('avatar', 255)->nullable();
        $table->enum('role', ['admin', 'agent', 'user'])->default('user');
        $table->text('bio')->nullable();
        $table->boolean('is_active')->default(true);
        $table->rememberToken(); // remember_token VARCHAR(100) NULL
        $table->timestamps(); // created_at and updated_at
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
