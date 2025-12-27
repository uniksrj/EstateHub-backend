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
        Schema::table('deals', function (Blueprint $table) {
            // Deadline extension tracking
            $table->unsignedInteger('deadline_extensions')->default(0);
            $table->timestamp('last_extension_date')->nullable();
            $table->text('extension_reason')->nullable();
            
            // Deadline status tracking
            $table->enum('deadline_status', ['on_track', 'extended', 'missed'])
                  ->default('on_track');
            
            // Override columns
            $table->enum('priority_override', ['low', 'medium', 'high'])->nullable();
            $table->string('next_step_override', 255)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
       Schema::table('deals', function (Blueprint $table) {
            $table->dropColumn([
                'deadline_extensions',
                'last_extension_date',
                'extension_reason',
                'deadline_status',
                'priority_override',
                'next_step_override'
            ]);
        });
    }
};
