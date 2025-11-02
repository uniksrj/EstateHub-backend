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
        Schema::create('deal_pipeline', function (Blueprint $table) {
            $table->id();

            // Relations
            $table->foreignId('property_id')
                ->constrained('properties')
                ->onDelete('cascade');

            $table->foreignId('agent_id')
                ->constrained('users')
                ->onDelete('cascade');

            $table->foreignId('buyer_id')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null');

            // Pipeline info
            $table->enum('status', [
                'prospecting', 
                'contacted',
                'showing', 
                'negotiation', 
                'under_review', 
                'closed_won', 
                'closed_lost'
            ])->default('prospecting');

            $table->decimal('offer_price', 15, 2)->nullable();
            $table->text('notes')->nullable();

            // This links to your existing deal_losses table when deal is lost
            $table->foreignId('loss_id')
                ->nullable()
                ->constrained('deal_losses')
                ->onDelete('set null');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deal_pipeline');
    }
};
