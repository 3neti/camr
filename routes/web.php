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
});

require __DIR__.'/settings.php';
