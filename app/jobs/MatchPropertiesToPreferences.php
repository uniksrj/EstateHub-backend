<?php

namespace App\Jobs;

use App\Models\BuyerPreference;
use App\Models\Property;
use App\Models\PropertyAlert;
use App\Notifications\NewMatchingProperties;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class MatchPropertiesToPreferences implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $preferenceId;

    public function __construct(int $preferenceId)
    {
        $this->preferenceId = $preferenceId;
    }

    public function handle()
    {
        try {
            // Reload the preference from database with user relationship
            $preference = BuyerPreference::with('user')->find($this->preferenceId);

            // Check if preference still exists
            if (!$preference) {
                Log::info('BuyerPreference not found, skipping job.', [
                    'preference_id' => $this->preferenceId
                ]);
                return;
            }

            // Check if user still exists
            if (!$preference->user) {
                Log::info('User not found for preference, skipping job.', [
                    'preference_id' => $this->preferenceId
                ]);
                return;
            }

            // Build query based on preferences
            $query = Property::where('status', 'for_sale');

            // Apply price filters
            if ($preference->min_price) {
                $query->where('price', '>=', $preference->min_price);
            }
            if ($preference->max_price) {
                $query->where('price', '<=', $preference->max_price);
            }

            // Apply bedroom/bathroom filters
            if ($preference->min_bedrooms) {
                $query->where('bedrooms', '>=', $preference->min_bedrooms);
            }
            if ($preference->min_bathrooms) {
                $query->where('bathrooms', '>=', $preference->min_bathrooms);
            }

            // Apply property type filter
            if ($preference->property_type) {
                $query->where('property_type', $preference->property_type);
            }

            // Get matching properties (last 7 days)
            $matchingProperties = $query->where('created_at', '>', now()->subDays(7))
                ->get();

            if ($matchingProperties->isNotEmpty() && $preference->alerts_enabled) {
                // Create or update alert record
                $alert = PropertyAlert::updateOrCreate(
                    [
                        'user_id' => $preference->user_id,
                        'preference_id' => $preference->id,
                        'name' => 'Auto-generated Alert'
                    ],
                    [
                        'criteria' => $preference->toArray(),
                        'match_count' => $matchingProperties->count(),
                        'last_matched_at' => now()
                    ]
                );

                // Send notification based on frequency
                if ($this->shouldSendNotification($alert)) {
                    $preference->user->notify(
                        new NewMatchingProperties($matchingProperties, $alert)
                    );

                    Log::info('Notification sent for preference', [
                        'preference_id' => $preference->id,
                        'properties_count' => $matchingProperties->count()
                    ]);
                }
            } else {
                Log::info('No matching properties or alerts disabled', [
                    'preference_id' => $preference->id,
                    'matches_count' => $matchingProperties->count(),
                    'alerts_enabled' => $preference->alerts_enabled
                ]);
            }
        } catch (ModelNotFoundException $e) {
            Log::warning('Model not found during property matching job', [
                'preference_id' => $this->preferenceId,
                'error' => $e->getMessage()
            ]);
            return;
        } catch (\Exception $e) {
            Log::error('Error in MatchPropertiesToPreferences job', [
                'preference_id' => $this->preferenceId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e; // Re-throw to mark job as failed
        }
    }

    private function shouldSendNotification(PropertyAlert $alert)
    {
        // Your existing notification frequency logic
        if (!$alert->last_notified_at) {
            return true;
        }

        $frequency = $alert->user->notification_frequency ?? 'daily';

        switch ($frequency) {
            case 'instant':
                return true;
            case 'hourly':
                return $alert->last_notified_at->lt(now()->subHour());
            case 'daily':
                return $alert->last_notified_at->lt(now()->subDay());
            case 'weekly':
                return $alert->last_notified_at->lt(now()->subWeek());
            default:
                return $alert->last_notified_at->lt(now()->subDay());
        }
    }
}
