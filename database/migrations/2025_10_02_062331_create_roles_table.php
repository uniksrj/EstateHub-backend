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
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // super_admin, admin, agent, buyer, seller
            $table->string('display_name');
            $table->text('description')->nullable();
            $table->integer('level')->default(0); // Hierarchy level
            $table->boolean('is_default')->default(false);
            $table->boolean('is_system')->default(false); // Cannot delete system roles
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
