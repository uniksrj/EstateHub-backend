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
        Schema::table('deal_documents', function (Blueprint $table) {
            // Add status column with default value
            $table->enum('status', ['pending', 'uploaded', 'marked_received', 'rejected', 'approved'])
                ->default('pending')
                ->after('document_type')
                ->comment('Document status: pending, uploaded, marked_received, rejected, approved');

            // Add marked_received_at column
            $table->timestamp('marked_received_at')
                ->nullable()
                ->after('signed_at')
                ->comment('When document was marked as received (without file upload)');

            // Optional: Add notes column if you want
            $table->text('notes')
                ->nullable()
                ->after('marked_received_at')
                ->comment('Additional notes about the document');

            // Optional: Add index on status for better query performance
            $table->index('status', 'deal_documents_status_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('deal_documents', function (Blueprint $table) {
            // Drop the index first
            $table->dropIndex('deal_documents_status_index');

            // Remove the columns
            $table->dropColumn(['status', 'marked_received_at', 'notes']);
        });
    }
};
