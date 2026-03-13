<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\TripAdminController;

Route::prefix('admin')->middleware(['auth'])->group(function () {

    Route::get('/trips', [TripAdminController::class, 'index']);
    Route::get('/trips/{id}/edit', [TripAdminController::class, 'edit']);
    Route::post('/trips/{id}/update', [TripAdminController::class, 'update']);

    Route::post('/trips/{id}/start', [TripAdminController::class, 'start']);
    Route::post('/trips/{id}/finish', [TripAdminController::class, 'finish']);

    Route::get('/trips/create', [TripAdminController::class, 'create']);
    Route::post('/trips/store', [TripAdminController::class, 'store']);
});
