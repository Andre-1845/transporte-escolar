<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BusController;
use App\Http\Controllers\Api\SchoolRouteController;
use App\Http\Controllers\Api\TripController;
use App\Http\Controllers\Api\TripLocationController;

Route::prefix('v1')->group(function () {

    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {

        Route::get('/me', function () {
            return auth()->user();
        });
        Route::post('/register-student', [AuthController::class, 'registerStudent']);
        Route::get('/school/validate/{code}', [AuthController::class, 'validateSchoolCode']);
        Route::get('/buses', [BusController::class, 'index']);
        Route::get('/routes', [SchoolRouteController::class, 'index']);
        Route::get('/routes/{id}', [SchoolRouteController::class, 'show']);
        Route::get('/trips', [TripController::class, 'index']);
        Route::post('/trips', [TripController::class, 'store']);
        Route::get('/trips/active', [TripController::class, 'active']);
        Route::get('/trips/today', [TripController::class, 'todayTrips']);
        Route::get('/driver/today-trips', [TripController::class, 'todayTripsForDriver']);
        Route::get('/trips/{id}', [TripController::class, 'show']);
        Route::put('/trips/{id}', [TripController::class, 'update']);
        Route::post('/trips/{id}/location', [TripLocationController::class, 'store']);
        Route::get('/trips/{id}/latest-location', [TripLocationController::class, 'latest']);
        Route::post('/trips/{id}/cancel-auto-finish', [TripController::class, 'cancelAutoFinish']);
        Route::get('/driver/today-trip', [TripController::class, 'todayForDriver']);
        Route::post('/trips/{id}/start', [TripController::class, 'start']);
        Route::post('/trips/{id}/finish', [TripController::class, 'finish']);
    });
});