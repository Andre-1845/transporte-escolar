<?php

namespace App\Http\Controllers\Api;

use App\Events\TripLocationUpdated;
use App\Http\Controllers\Controller;
use App\Models\Trip;
use App\Models\TripLocation;
use Illuminate\Http\Request;
use App\Models\RouteStop;
use App\Helpers\GeoHelper;
use App\Services\FirebasePushService;

class TripLocationController extends Controller
{
    public function store(Request $request, $tripId)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        $trip = Trip::findOrFail($tripId);

        if ($trip->status !== 'in_progress') {
            return response()->json([
                'message' => 'Trip not active'
            ], 400);
        }

        $lat = $request->latitude;
        $lng = $request->longitude;

        $lastLocation = TripLocation::where('trip_id', $trip->id)
            ->latest('recorded_at')
            ->first();

        if ($lastLocation) {

            $seconds = now()->diffInSeconds($lastLocation->recorded_at);

            if ($seconds < 3) {
                return response()->json([
                    'message' => 'Location ignored (too soon)'
                ]);
            }

            $distance = GeoHelper::distanceMeters(
                $lat,
                $lng,
                $lastLocation->latitude,
                $lastLocation->longitude
            );

            if ($distance < 20) {
                return response()->json([
                    'message' => 'Location ignored (too close)'
                ]);
            }
        }

        $location = TripLocation::create([
            'school_id' => $trip->school_id,
            'trip_id' => $trip->id,
            'latitude' => $lat,
            'longitude' => $lng,
            'recorded_at' => now(),
        ]);

        $stops = RouteStop::where('school_route_id', $trip->school_route_id)
            ->orderBy('stop_order')
            ->get();

        foreach ($stops as $stop) {

            $distance = GeoHelper::distanceMeters(
                $lat,
                $lng,
                $stop->latitude,
                $stop->longitude
            );

            if ($distance <= $stop->radius_meters) {

                FirebasePushService::sendToStop(
                    $stop->id,
                    "🚌 Seu ônibus está chegando",
                    "Parada: {$stop->name}"
                );
            }
        }

        broadcast(new TripLocationUpdated($trip, $location));

        return response()->json([
            'message' => 'Location recorded',
            'data' => $location
        ]);
    }
}