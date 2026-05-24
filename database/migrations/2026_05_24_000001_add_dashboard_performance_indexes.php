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
        Schema::table('properties', function (Blueprint $table) {
            $table->index(['agent_id', 'status'], 'properties_agent_status_index');
        });

        Schema::table('property_views', function (Blueprint $table) {
            $table->index(['viewed_at', 'property_id'], 'property_views_viewed_property_index');
        });

        Schema::table('inquiries', function (Blueprint $table) {
            $table->index(['created_at', 'property_id'], 'inquiries_created_property_index');
        });

        Schema::table('property_offers', function (Blueprint $table) {
            $table->index(['status', 'property_id'], 'property_offers_status_property_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('property_offers', function (Blueprint $table) {
            $table->dropIndex('property_offers_status_property_index');
        });

        Schema::table('inquiries', function (Blueprint $table) {
            $table->dropIndex('inquiries_created_property_index');
        });

        Schema::table('property_views', function (Blueprint $table) {
            $table->dropIndex('property_views_viewed_property_index');
        });

        Schema::table('properties', function (Blueprint $table) {
            $table->dropIndex('properties_agent_status_index');
        });
    }
};
