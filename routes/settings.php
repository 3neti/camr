<?php

use App\Http\Controllers\Settings\DataImportController;
use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\TwoFactorAuthenticationController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('auth')->group(function () {
    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/password', [PasswordController::class, 'edit'])->name('user-password.edit');

    Route::put('settings/password', [PasswordController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('user-password.update');

    Route::get('settings/appearance', function () {
        return Inertia::render('settings/Appearance');
    })->name('appearance.edit');

    Route::get('settings/two-factor', [TwoFactorAuthenticationController::class, 'show'])
        ->name('two-factor.show');
    
    // Data Import
    Route::get('settings/data-import', [DataImportController::class, 'index'])->name('data-import.index');
    Route::post('settings/data-import/upload', [DataImportController::class, 'upload'])->name('data-import.upload');
    Route::post('settings/data-import/import', [DataImportController::class, 'import'])->name('data-import.import');
    Route::post('settings/data-import/progress', [DataImportController::class, 'progress'])->name('data-import.progress');
    Route::post('settings/data-import/{importJob}/cancel', [DataImportController::class, 'cancel'])->name('data-import.cancel');
    Route::delete('settings/data-import/{importJob}', [DataImportController::class, 'destroy'])->name('data-import.destroy');
});
