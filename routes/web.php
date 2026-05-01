<?php

use App\Http\Controllers\Auth;
use App\Models\Property;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/robots.txt', function () {
    $baseUrl = rtrim(config('app.frontend_url', config('app.url')), '/');

    return response(
        "User-agent: *\nAllow: /\nSitemap: {$baseUrl}/sitemap.xml\n",
        200,
        ['Content-Type' => 'text/plain']
    );
});

Route::get('/sitemap.xml', function () {
    $baseUrl = rtrim(config('app.frontend_url', config('app.url')), '/');
    $properties = Property::query()
        ->whereIn('status', ['for_sale', 'available'])
        ->select(['id', 'slug', 'updated_at'])
        ->latest('updated_at')
        ->limit(5000)
        ->get();

    $urls = collect([
        ['loc' => $baseUrl.'/', 'lastmod' => now()],
        ['loc' => $baseUrl.'/properties', 'lastmod' => now()],
        ['loc' => $baseUrl.'/contact', 'lastmod' => now()],
    ])->merge($properties->map(fn ($property) => [
        'loc' => $baseUrl.'/properties/'.($property->slug ?: $property->id).'/view',
        'lastmod' => $property->updated_at,
    ]));

    $xml = view('sitemap', ['urls' => $urls])->render();

    return response($xml, 200, ['Content-Type' => 'application/xml']);
});
