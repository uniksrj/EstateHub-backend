<?php

namespace App\Jobs;

use App\Models\Property;
use App\Services\InvestmentAnalysisService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RecalculatePropertyInvestmentMetrics implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 60;
    public int $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(public Property $property)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(InvestmentAnalysisService $service): void
    {
        $service->refresh($this->property);
    }
}
