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
        Schema::table('inquiries', function (Blueprint $table) {
            // Add user_id (who sent the inquiry)
            $table->foreignId('user_id')
                  ->nullable() // Allow null for guest inquiries
                  ->constrained('users')
                  ->onDelete('cascade');
            
            // Add agent_id (who the inquiry is sent to)
            $table->foreignId('agent_id')
                  ->nullable() // Allow null if no specific agent
                  ->constrained('users')
                  ->onDelete('cascade');
            
            // Optional: Add index for better performance
            $table->index(['user_id', 'agent_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['agent_id']);
            $table->dropColumn(['user_id', 'agent_id']);
        });
    }
};
