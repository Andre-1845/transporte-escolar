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

        $lat = $request->latitude;
        $lng = $request->longitude;

        TripLocation::create([
            'trip_id' => $tripId,
            'latitude' => $lat,
            'longitude' => $lng,
        ]);

        $trip = Trip::findOrFail($tripId);

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



            return response()->json(['status' => 'ok']);
        }

        $trip = Trip::findOrFail($tripId);

        $location = TripLocation::create([
            'school_id' => $trip->school_id,
            'trip_id' => $trip->id,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'recorded_at' => now(),
        ]);

        //  DISPARAR EVENTO WEBSOCKET
        broadcast(new TripLocationUpdated($trip, $location));

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
