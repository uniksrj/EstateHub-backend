<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserActivity;
use Carbon\Carbon;

class UserMetricsService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Get all user metrics in one call
     */
    public function getAllMetrics()
    {
        return [
            'total_users' => $this->getTotalUsersWithChange(),
            'active_users' => $this->getActiveUsersWithChange(),
            'inactive_users' => $this->getInactiveUsersCount(),
            'new_users_this_month' => $this->getNewUsersThisMonthWithChange(),
            'conversion_rate' => $this->getConversionRateWithChange(),
            'quick_metrics' => $this->getQuickMetrics(),
        ];
    }

    /**
     * Total Users with percentage change
     */
    public function getTotalUsersWithChange()
    {
        $current = User::count();
        $previous = User::where('created_at', '<=', $this->getLastMonthEnd())->count();

        return [
            'value' => $current,
            'change' => $this->calculatePercentageChange($previous, $current),
            'previous_period' => $previous
        ];
    }

    /**
     * Active Users with percentage change
     */
    public function getActiveUsersWithChange()
    {
        $current = User::where('is_active', true)->count();
        $previous = User::where('is_active', true)
            ->where('created_at', '<=', $this->getLastMonthEnd())
            ->count();

        return [
            'value' => $current,
            'change' => $this->calculatePercentageChange($previous, $current),
            'previous_period' => $previous
        ];
    }

    /**
     * New Users This Month with percentage change
     */
    public function getNewUsersThisMonthWithChange()
    {
        $current = User::whereBetween('created_at', [
            $this->getCurrentMonthStart(),
            $this->getCurrentMonthEnd()
        ])->count();

        $previous = User::whereBetween('created_at', [
            $this->getLastMonthStart(),
            $this->getLastMonthEnd()
        ])->count();

        return [
            'value' => $current,
            'change' => $this->calculatePercentageChange($previous, $current),
            'previous_period' => $previous
        ];
    }

    /**
     * Conversion Rate with percentage change
     */
    public function getConversionRateWithChange()
    {
        // Current month conversion
        $currentSignups = $this->getNewUsersThisMonthWithChange()['value'];
        $currentConverted = $this->getConvertedUsersThisMonth();
        $currentRate = $currentSignups > 0 ? ($currentConverted / $currentSignups) * 100 : 0;

        // Last month conversion
        $lastSignups = $this->getNewUsersThisMonthWithChange()['previous_period'];
        $lastConverted = $this->getConvertedUsersLastMonth();
        $lastRate = $lastSignups > 0 ? ($lastConverted / $lastSignups) * 100 : 0;

        return [
            'value' => round($currentRate, 1),
            'change' => $this->calculatePercentageChange($lastRate, $currentRate),
            'breakdown' => [
                'current_month' => [
                    'signups' => $currentSignups,
                    'converted' => $currentConverted,
                    'rate' => round($currentRate, 1)
                ],
                'last_month' => [
                    'signups' => $lastSignups,
                    'converted' => $lastConverted,
                    'rate' => round($lastRate, 1)
                ]
            ]
        ];
    }

    /**
     * Get converted users (adjust criteria based on your business)
     */
    private function getConvertedUsersThisMonth()
    {
        return User::whereBetween('created_at', [
            $this->getCurrentMonthStart(),
            $this->getCurrentMonthEnd()
        ])->where(function ($query) {
            $query->whereHas('properties')
                ->orWhereIn('role_id', [3, 4])
                ->orWhere('is_verified', true);
        })->count();
    }

    private function getConvertedUsersLastMonth()
    {
        return User::whereBetween('created_at', [
            $this->getLastMonthStart(),
            $this->getLastMonthEnd()
        ])->where(function ($query) {
            $query->whereHas('properties')
                ->orWhereIn('role_id', [3, 4])
                ->orWhere('is_verified', true);
        })->count();
    }

    /**
     * Quick metrics (today, week, etc.)
     */
    public function getQuickMetrics()
    {
        return [
            'today' => User::whereDate('created_at', today())->count(),
            'yesterday' => User::whereDate('created_at', today()->subDay())->count(),
            'this_week' => User::whereBetween('created_at', [
                now()->startOfWeek(),
                now()->endOfWeek()
            ])->count(),
            'last_week' => User::whereBetween('created_at', [
                now()->subWeek()->startOfWeek(),
                now()->subWeek()->endOfWeek()
            ])->count(),
        ];
    }

    /**
     * Inactive users count (simple count)
     */
    public function getInactiveUsersCount()
    {
        return User::where('is_active', false)->count();
    }

    /**
     * Calculate percentage change between two values
     */
    private function calculatePercentageChange($oldValue, $newValue)
    {
        if ($oldValue == 0) {
            return $newValue > 0 ? 100 : 0;
        }

        return round((($newValue - $oldValue) / $oldValue) * 100, 1);
    }

    /**
     * Date helpers
     */
    private function getCurrentMonthStart()
    {
        return now()->startOfMonth();
    }
    private function getCurrentMonthEnd()
    {
        return now()->endOfMonth();
    }
    private function getLastMonthStart()
    {
        return now()->subMonth()->startOfMonth();
    }
    private function getLastMonthEnd()
    {
        return now()->subMonth()->endOfMonth();
    }

    public function getMonthlyUserGrowth($months = 12)
    {
        $data = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $monthStart = now()->subMonths($i)->startOfMonth();
            $monthEnd = now()->subMonths($i)->endOfMonth();
            $userCount = User::whereBetween('created_at', [$monthStart, $monthEnd])->count();
            $data[] = [
                'date' => $monthStart->format('M'),
                'users' => $userCount
            ];
        }
        return $data;
    }

    public function getCummulativeMonthlyGrowth($months = 12)
    {
        $data = [];
        $cumulativeTotal = 0;
        for ($i = $months - 1; $i >= 0; $i--) {
            $monthStart = now()->subMonths($i)->startOfMonth();
            $monthEnd = now()->subMonths($i)->endOfMonth();
            $userCount = User::whereBetween('created_at', [$monthStart, $monthEnd])->count();
            $cumulativeTotal += $userCount;
            $data[] = [
                'date' => $monthStart->format('M'),
                'users' => $cumulativeTotal
            ];
        }
        return $data;
    }

    /**
     * Get users who signed up X months ago
     */

    private function getMonthlyCohort($monthsAgo)
    {
        $sigupDate = now()->subMonths($monthsAgo);
        $userId = User::whereBetween('created_at', [
            $sigupDate->copy()->startOfMonth(),
            $sigupDate->copy()->endOfMonth()
        ])->pluck('id')->toArray();

        return [
            'user_ids' => $userId,
            'total_users' => count($userId),
            'signup_month' => $sigupDate->format('M Y')
        ];
    }



    /**
     * Get cohort of users who signed up X weeks ago
     */
    private function getWeeklyCohort($weeksAgo)
    {
        $signupDate = now()->subWeeks($weeksAgo);

        $userIds = User::whereBetween('created_at', [
            $signupDate->copy()->startOfWeek(),
            $signupDate->copy()->endOfWeek()
        ])->pluck('id')->toArray();

        return [
            'user_ids' => $userIds,
            'total_users' => count($userIds),
            'signup_week' => $signupDate->format('Y-m-d')
        ];
    }
    /**
     * Calculate retention for a weekly cohort
     */
    private function calculateMonthlyRetention($cohort, $monthsAgo)
    {
        if (empty($cohort['user_ids'])) {
            return ['retained_rate' => 0, 'churned_rate' => 0, 'retained_count' => 0];
        }

        $retainedCount = User::whereIn('id', $cohort['user_ids'])
            ->where(function ($q) {
                // Different retention criteria based on user role

                // For Agents/Brokers/Sellers (role_id 3,4,6)
                $q->where(function ($roleQuery) {
                    $roleQuery->whereIn('role_id', [3, 4, 6]) // Agents, Brokers, Sellers
                        ->where(function ($agentQuery) {
                            $agentQuery->whereHas('properties', function ($p) {
                                $p->where('status', 'for_sale') // Active listings
                                    ->orWhere('created_at', '>=', now()->subDays(60)) // New listings
                                    ->orWhere('updated_at', '>=', now()->subDays(30)); // Updated listings
                            });
                        });
                })

                    // For Buyers/Renters/Investors (role_id 5,7,8)
                    ->orWhere(function ($roleQuery) {
                        $roleQuery->whereIn('role_id', [5, 7, 8]) // Buyers, Investors, Renters
                            ->where(function ($buyerQuery) {
                                $buyerQuery->whereHas('inquiries', function ($inquiry) {
                                    $inquiry->where('created_at', '>=', now()->subDays(30));
                                })
                                    ->orWhereHas('propertyViews', function ($view) {
                                        $view->where('created_at', '>=', now()->subDays(30));
                                    })
                                    ->orWhereHas('favorites', function ($fav) {
                                        $fav->where('created_at', '>=', now()->subDays(30));
                                    });
                            });
                    });
            })
            ->count();

        $retainedRate = ($retainedCount / $cohort['total_users']) * 100;
        $churnedRate = 100 - $retainedRate;

        return [
            'retained_rate' => round($retainedRate, 1),
            'churned_rate' => round($churnedRate, 1),
            'retained_count' => $retainedCount
        ];
    }

    /**
     * Monthly retention rates (more practical for real estate)
     */
    public function getWeeklyRetentionData()
    {
        $retRate = [];
        $periods =  [1, 2, 3, 4, 5, 6, 7];

        foreach ($periods as $w) {
            $userCount = $this->getWeeklyCohort($w);
            if ($userCount['total_users'] == 0) {
                $retRate[] = $this->getDemoRetentionData($w);
                continue;
            }
            $retRate = $this->calculateMonthlyRetention($userCount, $w);
            $retRate[] = [
                'week' => "Week {$w}",
                'retained' => $retRate['retained_rate'],
                'churned' => $retRate['churned_rate'],
                'retained_count' => $retRate['retained_count'],
                'total_users' => $userCount['total_users']
            ];
        }
        return $retRate;
    }

    /**
     * Demo data for testing (remove when you have real data)
     */

    private function getDemoRetentionData($week)
    {
        $demoRates = [
            1 => ['retained' => 95, 'churned' => 5],
            2 => ['retained' => 85, 'churned' => 15],
            3 => ['retained' => 78, 'churned' => 22],
            4 => ['retained' => 72, 'churned' => 28],
            5 => ['retained' => 72, 'churned' => 28],
            6 => ['retained' => 72, 'churned' => 28],
            7 => ['retained' => 72, 'churned' => 28],           
        ];

        return [
            'week' => "Week {$week}",
            'retained' => $demoRates[$week]['retained'],
            'churned' => $demoRates[$week]['churned'],
            'retained_count' => 0,
            'total_users' => 0,
            'is_demo' => true 
        ];
    }

    public function getUserRolesDistribution()
    {
        $roles = [
            2 => 'Admin',
            3 => 'Agent',
            4 => 'Broker',
            5 => 'Buyer',
            6 => 'Seller',
            7 => 'Investor',
            8 => 'Renter'
        ];

        $distribution = [];
        foreach ($roles as $roleId => $roleName) {
            $count = User::where('role_id', $roleId)->count();
            $distribution[] = [
                'role' => $roleName,
                'count' => $count,
                'color' => $this->getRoleColor($roleName)
            ];
        }

        return $distribution;
    }

    private function getRoleColor($roleName)
    {
        $colors = [
            'Admin' => '#ef4444',
            'Agent' => '#3b82f6',
            'Broker' => '#f59e0b',
            'Seller' => '#10b981',
            'Buyer' => '#0434d1ff',
            'Renter' => '#fe369aff',
            'Investor' => '#8b5cf6'
        ];

        return $colors[$roleName] ?? '#6b7280';
    }

    public function getRecentActivity()
    {
        $activities = UserActivity::with(['user:id,name', 'property:id,property_code'])
            ->latest()
            ->take(10)
            ->get()
            ->map(function ($activity) {
                $type = match (true) {
                    str_contains(strtolower($activity->activity_type), 'delete'),
                    str_contains(strtolower($activity->activity_type), 'dead'),
                    str_contains(strtolower($activity->activity_type), 'remove') => 'negative',

                    str_contains(strtolower($activity->activity_type), 'add'),
                    str_contains(strtolower($activity->activity_type), 'create'),
                    str_contains(strtolower($activity->activity_type), 'sold'),
                    str_contains(strtolower($activity->activity_type), 'recover') => 'positive',

                    default => 'neutral'
                };

                return [
                    'id' => $activity->id,
                    'deal' => $activity->property?->property_code ?? 'N/A',
                    'action' => ucfirst(str_replace('_', ' ', $activity->activity_type)),
                    'user' => $activity->user?->name ?? 'System',
                    'time' => Carbon::parse($activity->created_at)->diffForHumans(),
                    'type' => $type,
                ];
            });

        return response()->json([
            'activities' => $activities
        ]);
    }
}
