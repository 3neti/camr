<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::get('analytics', function () {
    return \Inertia\Inertia::render('Analytics');
})->middleware(['auth'])->name('analytics');

Route::get('live-monitoring', function () {
    return \Inertia\Inertia::render('LiveMonitoring');
})->middleware(['auth'])->name('live.monitoring');

// CAMR Management
Route::middleware(['auth'])->group(function () {
    Route::resource('sites', \App\Http\Controllers\SiteController::class);
    Route::post('sites/bulk-delete', [\App\Http\Controllers\SiteController::class, 'bulkDestroy'])->name('sites.bulk-destroy');
    Route::resource('buildings', \App\Http\Controllers\BuildingController::class);
    Route::resource('locations', \App\Http\Controllers\LocationController::class);
    Route::resource('gateways', \App\Http\Controllers\GatewayController::class);
    Route::post('gateways/bulk-delete', [\App\Http\Controllers\GatewayController::class, 'bulkDestroy'])->name('gateways.bulk-destroy');
    Route::resource('meters', \App\Http\Controllers\MeterController::class);
    Route::post('meters/bulk-delete', [\App\Http\Controllers\MeterController::class, 'bulkDestroy'])->name('meters.bulk-destroy');
    Route::resource('config-files', \App\Http\Controllers\ConfigurationFileController::class);
    Route::resource('users', \App\Http\Controllers\UserController::class);
    Route::post('users/bulk-delete', [\App\Http\Controllers\UserController::class, 'bulkDestroy'])->name('users.bulk-destroy');
    Route::get('reports', [\App\Http\Controllers\ReportsController::class, 'index'])->name('reports.index');
    
    // API routes for chart data
    Route::prefix('api/meters/{meter}')->group(function () {
        Route::get('/power-data', [\App\Http\Controllers\Api\ReportsController::class, 'meterPowerData']);
        Route::get('/load-profile', [\App\Http\Controllers\Api\ReportsController::class, 'meterLoadProfile']);
        Route::get('/energy-summary', [\App\Http\Controllers\Api\ReportsController::class, 'meterEnergySummary']);
        Route::get('/comparison', [\App\Http\Controllers\Api\ReportsController::class, 'meterComparison']);
    });
    
    // Analytics API routes
    Route::prefix('api/analytics')->group(function () {
        Route::get('/realtime-power', [\App\Http\Controllers\AnalyticsController::class, 'realtimePower']);
        Route::get('/energy-trend', [\App\Http\Controllers\AnalyticsController::class, 'energyTrend']);
        Route::get('/power-quality', [\App\Http\Controllers\AnalyticsController::class, 'powerQuality']);
        Route::get('/demand-analysis', [\App\Http\Controllers\AnalyticsController::class, 'demandAnalysis']);
        Route::get('/site-aggregation', [\App\Http\Controllers\AnalyticsController::class, 'siteAggregation']);
        Route::get('/top-consumers', [\App\Http\Controllers\AnalyticsController::class, 'topConsumers']);
    });
    
    // Global search
    Route::get('api/search', [\App\Http\Controllers\Api\SearchController::class, 'search']);
    
    // Demo pages (for development)
    Route::get('demo/notifications', fn() => Inertia::render('Demo/Notifications'))->name('demo.notifications');
});


// ============================================================================
// Legacy PHP endpoint aliases (backward compatibility with deployed gateways)
// ============================================================================
// These routes maintain the exact URL structure from the old PHP system
// All route to the modern GatewayPollingController methods
// No authentication - maintains backward compatibility with deployed IoT devices

use App\Http\Controllers\Api\GatewayPollingController;

// CSV meter list updates (legacy URLs)
Route::get('/rtu/index.php/rtu/rtu_check_update/{mac}/get_update_csv', [GatewayPollingController::class, 'checkCsvUpdate'])
    ->name('legacy.gateway.check-csv');
Route::get('/rtu/index.php/rtu/rtu_check_update/{mac}/get_content_csv', [GatewayPollingController::class, 'getCsvContent'])
    ->name('legacy.gateway.csv');
Route::get('/rtu/index.php/rtu/rtu_check_update/{mac}/reset_update_csv', [GatewayPollingController::class, 'resetCsvUpdate'])
    ->name('legacy.gateway.reset-csv');

// Site code updates (legacy URLs)
Route::get('/rtu/index.php/rtu/rtu_check_update/{mac}/get_update_location', [GatewayPollingController::class, 'checkSiteCodeUpdate'])
    ->name('legacy.gateway.check-site-code');
Route::get('/rtu/index.php/rtu/rtu_check_update/{mac}/get_content_location', [GatewayPollingController::class, 'getSiteCode'])
    ->name('legacy.gateway.site-code');
Route::get('/rtu/index.php/rtu/rtu_check_update/{mac}/reset_update_location', [GatewayPollingController::class, 'resetSiteCodeUpdate'])
    ->name('legacy.gateway.reset-site-code');

// Force load profile (legacy URLs)
Route::get('/rtu/index.php/rtu/rtu_check_update/{mac}/force_lp', [GatewayPollingController::class, 'checkForceLoadProfile'])
    ->name('legacy.gateway.check-load-profile');
Route::get('/rtu/index.php/rtu/rtu_check_update/{mac}/reset_force_lp', [GatewayPollingController::class, 'resetForceLoadProfile'])
    ->name('legacy.gateway.reset-load-profile');

// Server time (legacy URL at root)
Route::get('/check_time.php', [GatewayPollingController::class, 'getServerTime'])
    ->name('legacy.server-time');

require __DIR__.'/settings.php';
