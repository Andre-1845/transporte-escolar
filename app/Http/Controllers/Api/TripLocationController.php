<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use App\Models\TripLocation;
use Illuminate\Http\Request;

class TripLocationController extends Controller
{
    public function index($tripId)
    {
        $trip = Trip::findOrFail($tripId);

        $locations = $trip->locations()
            ->orderBy('recorded_at', 'asc')
            ->limit(100) // evita sobrecarga
            ->get();

        return response()->json([
            'data' => $locations
        ]);
    }

    public function store(Request $request, $tripId)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        $trip = Trip::findOrFail($tripId);

        $location = TripLocation::create([
            'school_id' => $trip->school_id,
            'trip_id' => $trip->id,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'recorded_at' => now(),
        ]);

        return response()->json([
            'message' => 'Location recorded',
            'data' => $location
        ]);
    }

    public function latest($id)
    {
        $location = \App\Models\TripLocation::where('trip_id', $id)
            ->latest()
            ->first();

        if (!$location) {
            return response()->json([
                'success' => true,
                'data' => null
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'lat' => $location->latitude,
                'lng' => $location->longitude,
            ]
        ]);
    }
}