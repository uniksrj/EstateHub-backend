<?php

namespace App\Jobs;

use App\Models\BuyerPreference;
use App\Models\Property;
use App\Models\PropertyAlert;
use App\Notifications\NewMatchingProperties;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class MatchPropertiesToPreferences implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $preferences;

    public function __construct(BuyerPreference $preferences)
    {
        $this->preferences = $preferences;
    }

    public function handle()
    {
        // Build query based on preferences
        $query = Property::where('status', 'active');
        
        // Apply price filters
        if ($this->preferences->min_price) {
            $query->where('price', '>=', $this->preferences->min_price);
        }
        if ($this->preferences->max_price) {
            $query->where('price', '<=', $this->preferences->max_price);
        }
        
        // Apply bedroom/bathroom filters
        if ($this->preferences->min_bedrooms) {
            $query->where('bedrooms', '>=', $this->preferences->min_bedrooms);
        }
        if ($this->preferences->min_bathrooms) {
            $query->where('bathrooms', '>=', $this->preferences->min_bathrooms);
        }
        
        // Apply property type filter
        if ($this->preferences->property_type) {
            $query->where('type', $this->preferences->property_type);
        }
        
        // Get matching properties (last 7 days)
        $matchingProperties = $query->where('created_at', '>', now()->subDays(7))
            ->get();

        if ($matchingProperties->isNotEmpty() && $this->preferences->alerts_enabled) {
            // Create or update alert record
            $alert = PropertyAlert::updateOrCreate(
                [
                    'user_id' => $this->preferences->user_id,
                    'name' => 'Auto-generated Alert'
                ],
                [
                    'criteria' => $this->preferences->toArray(),
                    'match_count' => $matchingProperties->count(),
                    'last_matched_at' => now()
                ]
            );

            // Send notification based on frequency
            if ($this->shouldSendNotification($alert)) {
                $this->preferences->user->notify(
                    new NewMatchingProperties($matchingProperties, $alert)
                );
            }
        }
    }

    private function shouldSendNotification($alert)
    {
        $frequency = $this->preferences->alert_frequency;
        
        switch ($frequency) {
            case 'instant':
                return true;
            case 'daily':
                return !$alert->last_notified_at || 
                       $alert->last_notified_at->lt(now()->subDay());
            case 'weekly':
                return !$alert->last_notified_at || 
                       $alert->last_notified_at->lt(now()->subWeek());
            default:
                return true;
        }
    }
}