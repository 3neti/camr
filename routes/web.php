<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// CAMR Management
Route::middleware(['auth'])->group(function () {
    Route::resource('sites', \App\Http\Controllers\SiteController::class);
    Route::resource('gateways', \App\Http\Controllers\GatewayController::class);
    Route::resource('meters', \App\Http\Controllers\MeterController::class);
    Route::post('meters/bulk-delete', [\App\Http\Controllers\MeterController::class, 'bulkDestroy'])->name('meters.bulk-destroy');
    Route::resource('users', \App\Http\Controllers\UserController::class);
    Route::get('reports', [\App\Http\Controllers\ReportsController::class, 'index'])->name('reports.index');
    
    // API routes for chart data
    Route::prefix('api/meters/{meter}')->group(function () {
        Route::get('/power-data', [\App\Http\Controllers\Api\ReportsController::class, 'meterPowerData']);
        Route::get('/load-profile', [\App\Http\Controllers\Api\ReportsController::class, 'meterLoadProfile']);
        Route::get('/energy-summary', [\App\Http\Controllers\Api\ReportsController::class, 'meterEnergySummary']);
    });
});

require __DIR__.'/settings.php';
