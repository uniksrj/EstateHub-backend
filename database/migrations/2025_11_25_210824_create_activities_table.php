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
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deal_id')->constrained('deals')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('document_id')->nullable()->constrained('deal_documents')->onDelete('set null');
            
            // Activity type and details
            $table->string('type'); // document_upload, document_signed, step_completed, etc.
            $table->string('action'); // uploaded, signed, completed, requested, etc.
            $table->string('item'); // Purchase Agreement, Bank Statements, etc.
            $table->string('step'); // contract_generation, earnest_money, etc.
            $table->string('status'); // completed, pending, urgent, active
            
            // Message and metadata
            $table->text('message')->nullable();
            $table->json('metadata')->nullable(); // For additional data like file names, amounts, etc.
            
            $table->timestamps();
            
            // Indexes for better performance
            $table->index(['deal_id', 'created_at']);
            $table->index(['type', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
