<?php

namespace Database\Seeders;

use App\Models\Property;
use App\Models\User;
use App\Models\UserActivity;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserActivitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get some users and properties
        $userIds = User::inRandomOrder()->limit(5)->pluck('id');
        $propertyIds = Property::inRandomOrder()->limit(5)->pluck('id');

        $activities = [
            [
                'user_id' => $userIds->random(),
                'activity_type' => 'property_created',
                'property_id' => $propertyIds->random(),
                'metadata' => json_encode(['note' => 'New property added to listings']),
                'ip_address' => '192.168.1.10',
                'user_agent' => 'Mozilla/5.0',
                'created_at' => Carbon::now()->subHours(2),
            ],
            [
                'user_id' => $userIds->random(),
                'activity_type' => 'property_sold',
                'property_id' => $propertyIds->random(),
                'metadata' => json_encode(['price' => 245000]),
                'ip_address' => '192.168.1.15',
                'user_agent' => 'Mozilla/5.0',
                'created_at' => Carbon::now()->subHours(6),
            ],
            [
                'user_id' => $userIds->random(),
                'activity_type' => 'property_marked_dead',
                'property_id' => $propertyIds->random(),
                'metadata' => json_encode(['reason' => 'Client withdrew offer']),
                'ip_address' => '192.168.1.20',
                'user_agent' => 'Mozilla/5.0',
                'created_at' => Carbon::now()->subDay(),
            ],
            [
                'user_id' => $userIds->random(),
                'activity_type' => 'inquiry_added',
                'inquiry_id' => rand(1, 20),
                'metadata' => json_encode(['status' => 'Follow-up required']),
                'ip_address' => '192.168.1.25',
                'user_agent' => 'Mozilla/5.0',
                'created_at' => Carbon::now()->subDays(2),
            ],
            [
                'user_id' => $userIds->random(),
                'activity_type' => 'property_recovered',
                'property_id' => $propertyIds->random(),
                'metadata' => json_encode(['note' => 'Re-listed after negotiation']),
                'ip_address' => '192.168.1.30',
                'user_agent' => 'Mozilla/5.0',
                'created_at' => Carbon::now()->subDays(3),
            ],
        ];

        foreach ($activities as $activity) {
            UserActivity::create($activity);
        }
    }
}
