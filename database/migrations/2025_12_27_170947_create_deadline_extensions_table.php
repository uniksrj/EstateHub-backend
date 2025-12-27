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
        Schema::create('deadline_extensions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deal_id')->constrained()->onDelete('cascade');
            $table->unsignedInteger('extension_days');
            $table->string('extension_type', 50)->default('standard');
            $table->text('reason');
            $table->dateTime('old_deadline');
            $table->dateTime('new_deadline');
            $table->foreignId('extended_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            // Indexes for performance
            $table->index(['deal_id', 'created_at']);
            $table->index('extension_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deadline_extensions');
    }
};
