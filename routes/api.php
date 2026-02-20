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
        Route::get('/buses', [BusController::class, 'index']);
        Route::get('/routes', [SchoolRouteController::class, 'index']);
        Route::get('/routes/{id}', [SchoolRouteController::class, 'show']);
        Route::get('/trips', [TripController::class, 'index']);
        Route::get('/trips/active', [TripController::class, 'active']);
        Route::get('/trips/{id}', [TripController::class, 'show']);
        Route::post('/trips/{id}/location', [TripLocationController::class, 'store']);
        Route::post('/trips/{id}/start', [TripController::class, 'start']);
        Route::post('/trips/{id}/finish', [TripController::class, 'finish']);
    });
});