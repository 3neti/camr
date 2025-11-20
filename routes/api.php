<?php

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
