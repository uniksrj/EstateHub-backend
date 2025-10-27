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
            // Change existing status column to INT
            $table->integer('status')
                ->default(0)
                ->comment('0 = New, 1 = Responded, 2 = Contacted, 3 = Closed')
                ->change();

            // Optional tracking fields (if you want them)
            if (!Schema::hasColumn('inquiries', 'closed_by')) {
                $table->unsignedBigInteger('closed_by')->nullable()->after('status');
            }

            if (!Schema::hasColumn('inquiries', 'closed_at')) {
                $table->timestamp('closed_at')->nullable()->after('closed_by');
            }

            if (!Schema::hasColumn('inquiries', 'close_reason')) {
                $table->text('close_reason')->nullable()->after('closed_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
       Schema::table('inquiries', function (Blueprint $table) {
            // Rollback to previous type (if it was ENUM)
            $table->enum('status', ['new', 'responded', 'contacted', 'closed'])
                ->default('new')
                ->change();

            // Drop optional fields if rollback
            $table->dropColumn(['closed_by', 'closed_at', 'close_reason']);
        });
    }
};
