<?php

use App\Http\Controllers\Api\GatewayPollingController;
use App\Http\Controllers\Api\MeterReadingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Legacy meter reading ingestion endpoint
// No authentication - maintains backward compatibility with deployed IoT devices
Route::post('/meter-readings/ingest', [MeterReadingController::class, 'ingest'])
    ->name('api.meter-readings.ingest');

// Gateway polling endpoints
// No authentication - maintains backward compatibility with deployed gateway devices
// Gateways poll every 10 minutes for configuration updates
Route::prefix('gateway/{mac}')->group(function () {
    // CSV meter list updates (3-step process)
    Route::get('/check/csv', [GatewayPollingController::class, 'checkCsvUpdate'])
        ->name('api.gateway.check-csv');
    Route::get('/csv', [GatewayPollingController::class, 'getCsvContent'])
        ->name('api.gateway.csv');
    Route::post('/csv/reset', [GatewayPollingController::class, 'resetCsvUpdate'])
        ->name('api.gateway.reset-csv');

    // Site code updates (3-step process)
    Route::get('/check/site-code', [GatewayPollingController::class, 'checkSiteCodeUpdate'])
        ->name('api.gateway.check-site-code');
    Route::get('/site-code', [GatewayPollingController::class, 'getSiteCode'])
        ->name('api.gateway.site-code');
    Route::post('/site-code/reset', [GatewayPollingController::class, 'resetSiteCodeUpdate'])
        ->name('api.gateway.reset-site-code');

    // Force load profile (2-step process)
    Route::get('/check/load-profile', [GatewayPollingController::class, 'checkForceLoadProfile'])
        ->name('api.gateway.check-load-profile');
    Route::post('/load-profile/reset', [GatewayPollingController::class, 'resetForceLoadProfile'])
        ->name('api.gateway.reset-load-profile');
});

// Server time synchronization
Route::get('/server-time', [GatewayPollingController::class, 'getServerTime'])
    ->name('api.server-time');
