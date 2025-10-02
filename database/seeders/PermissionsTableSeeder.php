<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // Property
            ['name' => 'view_properties', 'display_name' => 'View Properties', 'group' => 'properties'],
            ['name' => 'create_properties', 'display_name' => 'Create Properties', 'group' => 'properties'],
            ['name' => 'edit_properties', 'display_name' => 'Edit Properties', 'group' => 'properties'],
            ['name' => 'delete_properties', 'display_name' => 'Delete Properties', 'group' => 'properties'],
            ['name' => 'manage_featured_properties', 'display_name' => 'Manage Featured Properties', 'group' => 'properties'],

            // Users
            ['name' => 'view_users', 'display_name' => 'View Users', 'group' => 'users'],
            ['name' => 'create_users', 'display_name' => 'Create Users', 'group' => 'users'],
            ['name' => 'edit_users', 'display_name' => 'Edit Users', 'group' => 'users'],
            ['name' => 'delete_users', 'display_name' => 'Delete Users', 'group' => 'users'],
            ['name' => 'manage_roles', 'display_name' => 'Manage Roles', 'group' => 'users'],

            // Analytics
            ['name' => 'view_analytics', 'display_name' => 'View Analytics', 'group' => 'analytics'],
            ['name' => 'view_financial_reports', 'display_name' => 'View Financial Reports', 'group' => 'analytics'],

            // System
            ['name' => 'manage_settings', 'display_name' => 'Manage Settings', 'group' => 'system'],
            ['name' => 'manage_media', 'display_name' => 'Manage Media', 'group' => 'system'],

            // Inquiries
            ['name' => 'view_inquiries', 'display_name' => 'View Inquiries', 'group' => 'inquiries'],
            ['name' => 'manage_inquiries', 'display_name' => 'Manage Inquiries', 'group' => 'inquiries'],
        ];

        foreach ($permissions as $permission) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $permission['name']],
                array_merge($permission, ['created_at' => now(), 'updated_at' => now()])
            );
        }
    }
}
