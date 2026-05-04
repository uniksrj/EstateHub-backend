<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->boolean('is_boosted')->default(false)->after('featured');
            $table->enum('boost_type', ['basic', 'premium', 'homepage'])->nullable()->after('is_boosted');
            $table->dateTime('boost_starts_at')->nullable()->after('boost_type');
            $table->dateTime('boost_expires_at')->nullable()->after('boost_starts_at');

            $table->index(['is_boosted', 'boost_expires_at']);
            $table->index('boost_type');
        });
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->dropIndex(['is_boosted', 'boost_expires_at']);
            $table->dropIndex(['boost_type']);
            $table->dropColumn([
                'is_boosted',
                'boost_type',
                'boost_starts_at',
                'boost_expires_at',
            ]);
        });
    }
};
