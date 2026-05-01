<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            if (!Schema::hasColumn('properties', 'slug')) {
                $table->string('slug', 255)->nullable()->after('title');
            }
        });

        DB::table('properties')
            ->whereNull('slug')
            ->orderBy('id')
            ->select(['id', 'title', 'city', 'state'])
            ->chunkById(100, function ($properties) {
                foreach ($properties as $property) {
                    $base = Str::slug(collect([$property->title, $property->city, $property->state])->filter()->implode(' ')) ?: 'property';
                    $slug = $base;
                    $counter = 2;

                    while (DB::table('properties')->where('slug', $slug)->where('id', '!=', $property->id)->exists()) {
                        $slug = $base.'-'.$counter++;
                    }

                    DB::table('properties')->where('id', $property->id)->update(['slug' => $slug]);
                }
            });

        Schema::table('properties', function (Blueprint $table) {
            $table->unique('slug', 'properties_slug_unique');
            $table->index('city', 'properties_city_index');
            $table->index('status', 'properties_status_index');
            $table->index('price', 'properties_price_index');
            $table->index('property_type', 'properties_property_type_index');
        });

        Schema::table('property_images', function (Blueprint $table) {
            if (!Schema::hasColumn('property_images', 'cloudinary_secure_url')) {
                $table->string('cloudinary_secure_url', 2048)->nullable()->after('image_path');
            }

            if (!Schema::hasColumn('property_images', 'cloudinary_public_id')) {
                $table->string('cloudinary_public_id', 255)->nullable()->after('cloudinary_secure_url');
            }
        });
    }

    public function down(): void
    {
        Schema::table('property_images', function (Blueprint $table) {
            if (Schema::hasColumn('property_images', 'cloudinary_public_id')) {
                $table->dropColumn('cloudinary_public_id');
            }

            if (Schema::hasColumn('property_images', 'cloudinary_secure_url')) {
                $table->dropColumn('cloudinary_secure_url');
            }
        });

        Schema::table('properties', function (Blueprint $table) {
            $table->dropUnique('properties_slug_unique');
            $table->dropIndex('properties_city_index');
            $table->dropIndex('properties_status_index');
            $table->dropIndex('properties_price_index');
            $table->dropIndex('properties_property_type_index');
            $table->dropColumn('slug');
        });
    }
};
