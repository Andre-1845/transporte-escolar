<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TripResource;
use App\Models\Trip;

class TripController extends Controller
{
    public function index()
    {
        return TripResource::collection(
            Trip::with(['bus', 'route'])
                ->orderBy('trip_date', 'desc')
                ->get()
        );
    }

    public function show($id)
    {
        $trip = Trip::with([
            'bus',
            'route.points'
        ])->findOrFail($id);

        return new TripResource($trip);
    }
}
