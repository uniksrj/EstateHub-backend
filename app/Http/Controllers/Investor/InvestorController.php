<?php

namespace App\Http\Controllers\Investor;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExportInvestmentReportRequest;
use App\Http\Requests\StoreWatchlistRequest;
use App\Http\Requests\UpdateInvestmentProfileRequest;
use App\Models\InvestmentProfile;
use App\Models\InvestmentWatchlist;
use App\Models\Property;
use App\Models\PropertyInvestmentMetric;
use App\Services\InvestmentAnalysisService;
use App\Services\MarketAnalyticsService;
use App\Services\PortfolioService;
use Illuminate\Http\Request;

class InvestorController extends Controller
{
    public function __construct(
        private InvestmentAnalysisService $analysis,
        private PortfolioService $portfolio,
        private MarketAnalyticsService $market,
    ) {}

    /* ============ DASHBOARD ============ */
    public function dashboard(Request $request)
    {
        $user = $request->user();
        $portfolio = $this->portfolio->aggregate($user);
        $watchlistCount = $user->investmentWatchlist()->count();

        $topOpportunities = PropertyInvestmentMetric::query()
            ->with('property')
            ->orderByDesc('investment_score')
            ->whereHas('property', fn($q) => $q->where('is_investment_friendly', true))
            ->limit(5)
            ->get();

        return response()->json([
            'summary' => $portfolio['summary'],
            'watchlist_count' => $watchlistCount,
            'portfolio_allocation' => [
                'by_city' => $portfolio['allocation_by_city'],
                'by_type' => $portfolio['allocation_by_type'],
            ],
            'top_opportunities' => $topOpportunities,
            'market_snapshot' => $this->market->topCities(5),
        ]);
    }

    /* ============ DISCOVERY ============ */
    public function discover(Request $request)
    {
        $query = Property::query()
            ->with('investmentMetric')
            ->where('is_investment_friendly', true);

        if ($request->filled('min_cap_rate')) {
            $query->whereHas('investmentMetric', fn($q) =>
            $q->where('cap_rate', '>=', $request->min_cap_rate));
        }
        if ($request->filled('min_yield')) {
            $query->whereHas('investmentMetric', fn($q) =>
            $q->where('gross_yield', '>=', $request->min_yield));
        }
        if ($request->filled('min_score')) {
            $query->whereHas('investmentMetric', fn($q) =>
            $q->where('investment_score', '>=', $request->min_score));
        }
        if ($request->filled('city')) $query->where('city', $request->city);
        if ($request->filled('min_price')) $query->where('price', '>=', $request->min_price);
        if ($request->filled('max_price')) $query->where('price', '<=', $request->max_price);
        if ($request->filled('property_type')) $query->where('property_type', $request->property_type);

        $query->orderByDesc(
            PropertyInvestmentMetric::select('investment_score')
                ->whereColumn('property_id', 'properties.id')
                ->limit(1)
        );

        return response()->json($query->paginate($request->input('per_page', 15)));
    }

    /* ============ ANALYZE ============ */
    public function analyze(Request $request, Property $property)
    {
        $overrides = $request->only([
            'down_payment_pct',
            'interest_rate',
            'loan_tenure_years',
            'appreciation_rate',
            'rent_growth_rate',
        ]);

        $analysis = $this->analysis->analyze($property, $overrides);

        return response()->json([
            'property' => $property->only(['id', 'title', 'price', 'city', 'locality', 'property_type']),
            'analysis' => $analysis,
            'market_context' => $this->market->forCity($property->city, $property->locality),
        ]);
    }

    /* ============ PORTFOLIO ============ */
    public function portfolio(Request $request)
    {
        return response()->json($this->portfolio->aggregate($request->user()));
    }

    /* ============ COMPARE ============ */
    public function compare(Request $request)
    {
        $request->validate([
            'property_ids' => ['required', 'array', 'min:2', 'max:4'],
            'property_ids.*' => ['integer', 'exists:properties,id'],
        ]);

        $properties = Property::with('investmentMetric')
            ->whereIn('id', $request->property_ids)
            ->get();

        $comparisons = $properties->map(fn($p) => [
            'property' => $p->only(['id', 'title', 'price', 'city', 'property_type', 'area_sqft']),
            'metrics' => $p->investmentMetric,
            'analysis' => $this->analysis->analyze($p),
        ]);

        return response()->json(['comparisons' => $comparisons]);
    }

    /* ============ MARKET ============ */
    public function market(Request $request)
    {
        $city = $request->input('city');
        if ($city) {
            return response()->json($this->market->forCity($city, $request->input('locality')));
        }
        return response()->json([
            'top_cities' => $this->market->topCities(10),
        ]);
    }

    /* ============ WATCHLIST ============ */
    public function watchlist(Request $request)
    {
        return response()->json(
            $request->user()->investmentWatchlist()->with('property.investmentMetric')->get()
        );
    }

    public function storeWatchlist(StoreWatchlistRequest $request)
    {
        $item = InvestmentWatchlist::updateOrCreate(
            ['user_id' => $request->user()->id, 'property_id' => $request->property_id],
            $request->safe()->except('property_id')
        );

        return response()->json(['watchlist' => $item], 201);
    }

    public function destroyWatchlist(Request $request, InvestmentWatchlist $item)
    {
        abort_unless((int) $item->user_id === (int) $request->user()->id, 403);
        $item->delete();
        return response()->json(['deleted' => true]);
    }

    /* ============ PROFILE ============ */
    public function profile(Request $request)
    {
        $profile = InvestmentProfile::firstOrCreate(
            ['user_id' => $request->user()->id],
            ['risk_appetite' => 'moderate', 'hold_period_years' => 5]
        );
        return response()->json($profile);
    }

    public function updateProfile(UpdateInvestmentProfileRequest $request)
    {
        $profile = InvestmentProfile::updateOrCreate(
            ['user_id' => $request->user()->id],
            $request->validated()
        );
        return response()->json($profile);
    }

    /* ============ REPORTS ============ */
    public function reports(Request $request)
    {
        // Return last N exports (placeholder — swap with a Reports model if you want history).
        return response()->json(['reports' => []]);
    }

    public function exportReport(ExportInvestmentReportRequest $request)
    {
        $type = $request->type;
        $format = $request->format;

        $data = match ($type) {
            'portfolio' => $this->portfolio->aggregate($request->user())['assets'],
            'market' => $this->market->forCity($request->city),
            'property' => $this->analysis->analyze(Property::findOrFail($request->property_id)),
        };

        if ($format === 'csv') {
            return $this->csvResponse($data, "investor-{$type}-report.csv");
        }

        return response()->json([
            'message' => 'PDF export queued',
            'type' => $type,
            'data' => $data,
        ]);
    }

    private function csvResponse(array $data, string $filename)
    {
        $rows = is_array(reset($data)) ? $data : [$data];
        $headers = array_keys(reset($rows));

        $callback = function () use ($rows, $headers) {
            $h = fopen('php://output', 'w');
            fputcsv($h, $headers);
            foreach ($rows as $row) {
                fputcsv($h, array_map(fn($v) => is_scalar($v) ? $v : json_encode($v), $row));
            }
            fclose($h);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
