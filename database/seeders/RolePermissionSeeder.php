<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
       $rolePermissions = [
            'super_admin' => ['view_properties','create_properties','edit_properties','delete_properties','manage_featured_properties','view_users','create_users','edit_users','delete_users','manage_roles','view_analytics','view_financial_reports','manage_settings','manage_media','view_inquiries','manage_inquiries'],
            'admin' => ['view_properties','create_properties','edit_properties','delete_properties','manage_featured_properties','view_users','create_users','edit_users','view_analytics','manage_media','view_inquiries','manage_inquiries'],
            'agent' => ['view_properties','create_properties','edit_properties','view_inquiries','manage_inquiries'],
            'broker' => ['view_properties','create_properties','edit_properties','view_inquiries','manage_inquiries','view_analytics'],
            'buyer' => ['view_properties','view_inquiries'],
            'seller' => ['view_properties','create_properties','edit_properties','view_inquiries'],
            'investor' => ['view_properties','view_analytics'],
        ];

        foreach ($rolePermissions as $roleName => $permissions) {
            $role = DB::table('roles')->where('name', $roleName)->first();
            if (!$role) continue;

            foreach ($permissions as $permissionName) {
                $permission = DB::table('permissions')->where('name', $permissionName)->first();
                if (!$permission) continue;

                DB::table('role_permission')->updateOrInsert(
                    ['role_id' => $role->id, 'permission_id' => $permission->id],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            }
        }
    }
}
