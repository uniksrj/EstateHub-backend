<?php

namespace App\Jobs;

use App\Models\Property;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\InvestmentMatchingService;

class MatchInvestmentsToProfiles implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public Property $property) {}

    /**
     * Execute the job.
     */
    public function handle(InvestmentMatchingService $service): void
    {
        $service->matchNewProperty($this->property);
    }
}
