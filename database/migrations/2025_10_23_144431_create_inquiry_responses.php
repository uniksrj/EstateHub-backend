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
        Schema::create('inquiry_responses', function (Blueprint $table) {
            $table->id(); // bigint unsigned auto-increment primary key
            $table->unsignedBigInteger('inquiry_id');
            $table->enum('sender_type', ['buyer', 'seller', 'system'])->default('seller');
            $table->unsignedBigInteger('sender_id')->nullable();
            $table->text('message');
            $table->json('attachments')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            // Indexes
            $table->index('inquiry_id', 'inquiry_responses_inquiry_id_index');
            $table->index(['sender_type', 'sender_id'], 'inquiry_responses_sender_type_sender_id_index');
            $table->index('created_at', 'inquiry_responses_created_at_index');

            // Foreign key
            $table->foreign('inquiry_id')
                  ->references('id')
                  ->on('inquiries')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inquiry_responses');
    }
};
