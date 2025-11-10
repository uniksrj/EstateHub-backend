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
         Schema::table('property_alerts', function (Blueprint $table) {
            // Add preference_id column
            $table->foreignId('preference_id')->after('user_id')
                  ->constrained('buyer_preferences')
                  ->onDelete('cascade'); // Delete alerts when preference is deleted
            
            // Add frequency column if not exists
            if (!Schema::hasColumn('property_alerts', 'frequency')) {
                $table->string('frequency')->default('instant')->after('is_active');
            }
            
            // Add total_matches if not exists
            if (!Schema::hasColumn('property_alerts', 'total_matches')) {
                $table->integer('total_matches')->default(0)->after('match_count');
            }
            
            // Add last_notified_at if not exists
            if (!Schema::hasColumn('property_alerts', 'last_notified_at')) {
                $table->timestamp('last_notified_at')->nullable()->after('last_matched_at');
            }
            
            // Add soft deletes if not exists
            if (!Schema::hasColumn('property_alerts', 'deleted_at')) {
                $table->softDeletes();
            }
            
            // Add index for better performance
            $table->index(['user_id', 'preference_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('property_alerts', function (Blueprint $table) {
            // Drop foreign key and column
            $table->dropForeign(['preference_id']);
            $table->dropColumn('preference_id');
            
            // Drop additional columns if they were added
            if (Schema::hasColumn('property_alerts', 'frequency')) {
                $table->dropColumn('frequency');
            }
            
            if (Schema::hasColumn('property_alerts', 'total_matches')) {
                $table->dropColumn('total_matches');
            }
            
            if (Schema::hasColumn('property_alerts', 'last_notified_at')) {
                $table->dropColumn('last_notified_at');
            }
            
            if (Schema::hasColumn('property_alerts', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
            
            // Drop index
            $table->dropIndex(['user_id', 'preference_id']);
        });
    }
};
