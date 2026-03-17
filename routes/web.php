<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\TripAdminController;
use App\Http\Controllers\Admin\UserAdminController;
use App\Http\Controllers\Admin\BusAdminController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\RouteAdminController;
use App\Http\Controllers\Admin\RouteEditorController;
use App\Http\Controllers\Admin\RouteMapController;
use App\Http\Controllers\Admin\RouteStopAdminController;
use App\Http\Controllers\Auth\LoginController;

Route::get('/login', [LoginController::class, 'showLogin'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::prefix('admin')
    ->middleware(['auth'])
    ->group(function () {

        Route::get('/', [DashboardController::class, 'index'])
            ->name('admin.dashboard');


        /*
|--------------------------------------------------------------------------
| ROUTE MAP EDITOR
|--------------------------------------------------------------------------
*/

        Route::get(
            '/routes/{id}/map',
            [RouteMapController::class, 'edit']
        )->name('admin.routes.map');

        Route::post(
            '/routes/stop',
            [RouteEditorController::class, 'storeStop']
        );

        Route::post(
            '/routes/point',
            [RouteEditorController::class, 'storePoint']
        );

        Route::put(
            '/routes/stop/{id}',
            [RouteEditorController::class, 'updateStop']
        );

        Route::put(
            '/routes/point/{id}',
            [RouteEditorController::class, 'updatePoint']
        );

        Route::delete(
            '/routes/stop/{id}',
            [RouteEditorController::class, 'deleteStop']
        );

        Route::delete(
            '/routes/point/{id}',
            [RouteEditorController::class, 'deletePoint']
        );

        Route::post(
            '/routes/reorder-stops',
            [RouteEditorController::class, 'reorderStops']
        );
        /* ROTAS */


        Route::resource(
            'routes',
            RouteAdminController::class
        )->names('admin.routes');

        /* ROUTE STOPS */

        Route::get(
            '/routes/{route}/stops',
            [RouteStopAdminController::class, 'index']
        )->name('admin.routes.stops');

        Route::post(
            '/routes/{route}/stops',
            [RouteStopAdminController::class, 'store']
        );

        Route::put(
            '/stops/{stop}',
            [RouteStopAdminController::class, 'update']
        );

        Route::delete(
            '/stops/{stop}',
            [RouteStopAdminController::class, 'destroy']
        );

        Route::post(
            '/stops/{stop}/up',
            [RouteStopAdminController::class, 'moveUp']
        );

        Route::post(
            '/stops/{stop}/down',
            [RouteStopAdminController::class, 'moveDown']
        );

        /*
        |--------------------------------------------------------------------------
        | TRIPS
        |--------------------------------------------------------------------------
        */

        Route::get('/trips', [TripAdminController::class, 'index'])->name('admin.trips.index');

        Route::get('/trips/create', [TripAdminController::class, 'create'])->name('admin.trips.create');
        Route::post('/trips/store', [TripAdminController::class, 'store'])->name('admin.trips.store');

        Route::get('/trips/{id}/edit', [TripAdminController::class, 'edit'])->name('admin.trips.edit');
        Route::post('/trips/{id}/update', [TripAdminController::class, 'update'])->name('admin.trips.update');

        Route::post('/trips/{id}/start', [TripAdminController::class, 'start'])->name('admin.trips.start');
        Route::post('/trips/{id}/finish', [TripAdminController::class, 'finish'])->name('admin.trips.finish');


        /*
        |--------------------------------------------------------------------------
        | USERS
        |--------------------------------------------------------------------------
        */

        Route::resource('users', UserAdminController::class)
            ->names('admin.users');


        /*
        |--------------------------------------------------------------------------
        | BUSES
        |--------------------------------------------------------------------------
        */

        Route::resource('buses', BusAdminController::class)
            ->names('admin.buses');
    });