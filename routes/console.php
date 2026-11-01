<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function () {
    app(\App\Services\MarketAnalyticsService::class)->buildSnapshot();
})->dailyAt('02:00')->name('market-snapshot');

Schedule::call(function () {
    \App\Models\Property::query()
        ->where('is_investment_friendly', true)
        ->chunk(100, function ($properties) {
            foreach ($properties as $p) {
                \App\Jobs\RecalculatePropertyInvestmentMetrics::dispatch($p);
            }
        });
})->weekly()->name('recalc-investment-metrics');