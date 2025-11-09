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
        Schema::create('alert_property_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_alert_id')->constrained()->onDelete('cascade');
            $table->foreignId('property_id')->constrained()->onDelete('cascade');
            $table->timestamp('matched_at')->useCurrent();
            $table->timestamp('notified_at')->nullable();
            $table->boolean('is_new')->default(true);
            $table->timestamps();

            $table->unique(['property_alert_id', 'property_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alert_property_matches');
    }
};
