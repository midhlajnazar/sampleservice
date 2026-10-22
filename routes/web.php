<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\ServiceRequestController;
use App\Http\Resources\ServiceResource;
use App\Models\Service;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn () => Inertia::render('Welcome', [
    'services' => ServiceResource::collection(Service::active()->get())->resolve(),
]))->name('home');

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    // Service catalogue
    Route::get('services', [ServiceController::class, 'index'])->name('services.index');
    Route::get('services/{service}', [ServiceController::class, 'show'])->name('services.show');

    // Applications
    Route::get('services/{service}/apply', [ServiceRequestController::class, 'create'])->name('requests.create');
    Route::post('services/{service}/apply', [ServiceRequestController::class, 'store'])->name('requests.store');
    Route::get('requests', [ServiceRequestController::class, 'index'])->name('requests.index');
    Route::get('requests/{serviceRequest}', [ServiceRequestController::class, 'show'])->name('requests.show');
    Route::post('requests/{serviceRequest}/respond', [ServiceRequestController::class, 'respond'])->name('requests.respond');
    Route::post('requests/{serviceRequest}/cancel', [ServiceRequestController::class, 'cancel'])->name('requests.cancel');
    Route::get('requests/{serviceRequest}/print/{document}', [ServiceRequestController::class, 'print'])
        ->whereIn('document', ['receipt', 'certificate'])
        ->name('requests.print');

    // Dummy payment gateway
    Route::get('requests/{serviceRequest}/checkout', [PaymentController::class, 'checkout'])->name('payments.checkout');
    Route::post('requests/{serviceRequest}/checkout', [PaymentController::class, 'process'])
        ->middleware('throttle:10,1')
        ->name('payments.process');

    Route::get('documents/{document}', [DocumentController::class, 'show'])->name('documents.show');
});

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', Admin\DashboardController::class)->name('dashboard');

    Route::get('requests', [Admin\ServiceRequestController::class, 'index'])->name('requests.index');
    Route::get('requests/{serviceRequest}', [Admin\ServiceRequestController::class, 'show'])->name('requests.show');
    Route::post('requests/{serviceRequest}/transition', [Admin\ServiceRequestController::class, 'transition'])->name('requests.transition');
    Route::post('requests/{serviceRequest}/notes', [Admin\ServiceRequestController::class, 'note'])->name('requests.notes');
    Route::patch('documents/{document}', [Admin\ServiceRequestController::class, 'reviewDocument'])->name('documents.review');

    Route::get('services', [Admin\ServiceController::class, 'index'])->name('services.index');
    Route::get('services/{service}/edit', [Admin\ServiceController::class, 'edit'])->name('services.edit');
    Route::put('services/{service}', [Admin\ServiceController::class, 'update'])->name('services.update');
    Route::post('services/{service}/toggle', [Admin\ServiceController::class, 'toggle'])->name('services.toggle');

    Route::get('payments', [Admin\PaymentController::class, 'index'])->name('payments.index');
});

require __DIR__.'/settings.php';
