<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AdminBookingController;
use App\Http\Controllers\Api\V1\AdminDashboardController;
use App\Http\Controllers\Api\V1\AdminVenueController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BookingController;
use App\Http\Controllers\Api\V1\CourtAvailabilityController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\ReviewController;
use App\Http\Controllers\Api\V1\VenueController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {

    Route::get('/venues', [VenueController::class, 'index'])->name('api.v1.venues.index');

    Route::get('/venues/{venue}', [VenueController::class, 'show'])->name('api.v1.venues.show');

    Route::get('/courts/{court}/availability', CourtAvailabilityController::class)
        ->name('api.v1.courts.availability');

    Route::get('/courts/{court}/reviews', [ReviewController::class, 'index'])
        ->name('api.v1.courts.reviews.index');

    Route::prefix('auth')->group(function (): void {
        Route::post('/register', [AuthController::class, 'register'])->name('api.v1.auth.register');
        Route::post('/login', [AuthController::class, 'login'])->name('api.v1.auth.login');

        Route::middleware('auth:sanctum')->group(function (): void {
            Route::post('/logout', [AuthController::class, 'logout'])->name('api.v1.auth.logout');
            Route::get('/me', [AuthController::class, 'me'])->name('api.v1.auth.me');
        });
    });

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('/bookings', [BookingController::class, 'store'])->name('api.v1.bookings.store');
        Route::get('/bookings/my-bookings', [BookingController::class, 'myBookings'])->name('api.v1.bookings.my');
        Route::post('/bookings/{booking}/reviews', [ReviewController::class, 'store'])->name('api.v1.bookings.reviews.store');

        Route::post('/payments/{booking}/pay', [PaymentController::class, 'pay'])->name('api.v1.payments.pay');

        Route::patch('/admin/bookings/{booking}/check-in', [AdminBookingController::class, 'checkIn'])
            ->name('api.v1.admin.bookings.checkin');

        Route::post('/admin/venues/{venue}/courts', [AdminVenueController::class, 'storeCourt'])
            ->name('api.v1.admin.venues.courts.store');
        Route::put('/admin/courts/{court}', [AdminVenueController::class, 'updateCourt'])
            ->name('api.v1.admin.courts.update');
        Route::post('/admin/venues/{venue}/upload-image', [AdminVenueController::class, 'uploadImage'])
            ->name('api.v1.admin.venues.upload_image');
        Route::post('/admin/venues/{venue}/amenities', [AdminVenueController::class, 'syncAmenities'])
            ->name('api.v1.admin.venues.amenities.sync');

        Route::get('/admin/dashboard/summary', [AdminDashboardController::class, 'summary'])
            ->name('api.v1.admin.dashboard.summary');
    });

    Route::post('/payments/webhook', [PaymentController::class, 'webhook'])->name('api.v1.payments.webhook');

});
