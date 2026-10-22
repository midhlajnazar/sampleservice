<?php

use App\Http\Controllers\Api\V1;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    // Public
    Route::post('auth/token', [V1\AuthController::class, 'token'])->middleware('throttle:6,1')->name('auth.token');
    Route::get('services', [V1\ServiceController::class, 'index'])->name('services.index');
    Route::get('services/{service}', [V1\ServiceController::class, 'show'])->name('services.show');
    Route::post('services/{service}/quote', [V1\ServiceController::class, 'quote'])->name('services.quote');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('user', [V1\AuthController::class, 'user'])->name('user');
        Route::delete('auth/token', [V1\AuthController::class, 'revoke'])->name('auth.revoke');

        Route::middleware('abilities:requests:read')->group(function () {
            Route::get('requests', [V1\ServiceRequestController::class, 'index'])->name('requests.index');
            Route::get('requests/{serviceRequest}', [V1\ServiceRequestController::class, 'show'])->name('requests.show');
            Route::get('documents/{document}', [V1\ServiceRequestController::class, 'document'])->name('documents.show');
        });

        Route::middleware('abilities:requests:write')->group(function () {
            Route::post('requests', [V1\ServiceRequestController::class, 'store'])->name('requests.store');
            Route::post('requests/{serviceRequest}/respond', [V1\ServiceRequestController::class, 'respond'])->name('requests.respond');
            Route::post('requests/{serviceRequest}/cancel', [V1\ServiceRequestController::class, 'cancel'])->name('requests.cancel');
        });

        Route::post('requests/{serviceRequest}/pay', [V1\ServiceRequestController::class, 'pay'])
            ->middleware(['abilities:payments:write', 'throttle:10,1'])
            ->name('requests.pay');

        Route::middleware(['abilities:admin', 'admin'])->prefix('admin')->name('admin.')->group(function () {
            Route::get('requests', [V1\Admin\ServiceRequestController::class, 'index'])->name('requests.index');
            Route::get('requests/{serviceRequest}', [V1\Admin\ServiceRequestController::class, 'show'])->name('requests.show');
            Route::post('requests/{serviceRequest}/transition', [V1\Admin\ServiceRequestController::class, 'transition'])->name('requests.transition');
        });
    });
});
