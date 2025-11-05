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
         Schema::create('property_tours', function (Blueprint $table) {
            $table->id();

            // 🔗 Relationships
            $table->foreignId('property_id')->constrained('properties')->onDelete('cascade');
            $table->foreignId('buyer_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('agent_id')->nullable()->constrained('users')->onDelete('set null');

            // 🕒 Schedule details
            $table->dateTime('scheduled_at')->nullable();   // When the tour happens
            $table->enum('status', ['pending', 'approved', 'completed', 'cancelled'])->default('pending');
            $table->text('notes')->nullable();              // Optional notes by agent or buyer

            // 🧩 Metadata
            $table->string('meeting_type')->default('property_tour'); // can extend to 'virtual_meeting', etc.
            $table->string('location')->nullable();         // optional custom meeting location
            $table->boolean('is_virtual')->default(false);  // if virtual meeting (Zoom, etc.)

            // 🕓 Auditing
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrentOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('property_tours');
    }
};
