<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         $roles = [
            ['name' => 'super_admin', 'display_name' => 'Super Administrator', 'description' => 'Full system access', 'level' => 100, 'is_default' => false],
            ['name' => 'admin', 'display_name' => 'Administrator', 'description' => 'Platform management', 'level' => 90, 'is_default' => false],
            ['name' => 'agent', 'display_name' => 'Real Estate Agent', 'description' => 'Property agent', 'level' => 50, 'is_default' => false],
            ['name' => 'broker', 'display_name' => 'Broker', 'description' => 'Real estate broker', 'level' => 60, 'is_default' => false],
            ['name' => 'buyer', 'display_name' => 'Property Buyer', 'description' => 'Looking to buy properties', 'level' => 10, 'is_default' => true],
            ['name' => 'seller', 'display_name' => 'Property Seller', 'description' => 'Looking to sell properties', 'level' => 10, 'is_default' => false],
            ['name' => 'investor', 'display_name' => 'Investor', 'description' => 'Property investor', 'level' => 20, 'is_default' => false],
            ['name' => 'renter', 'display_name' => 'Renter/Tenant', 'description' => 'Looking to rent properties', 'level' => 5, 'is_default' => false],
        ];

        foreach ($roles as $role) {
            DB::table('roles')->updateOrInsert(
                ['name' => $role['name']], // check unique column
                array_merge($role, ['created_at' => now(), 'updated_at' => now()])
            );
        }
    }
}
