<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TripResource;
use App\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Helpers\GeoHelper;

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
            'route.points',
            'route.stops',
        ])->findOrFail($id);

        return new TripResource($trip);
    }

    public function active()
    {
        $trips = Trip::with(['bus', 'route', 'route.points', 'route.stops'])
            ->where('school_id', auth()->user()->school_id)
            ->where('status', 'in_progress')
            ->get();

        return response()->json([
            'success' => true,
            'data' => TripResource::collection($trips)
        ]);
    }

    public function start(Request $request, $id)
    {
        $trip = Trip::with('route.stops')->findOrFail($id);

        if ($trip->status !== 'scheduled') {
            return response()->json([
                'success' => false,
                'message' => 'Trip cannot be started'
            ], 400);
        }

        $firstStop = $trip->route->stops
            ->sortBy('stop_order')
            ->first();

        if ($firstStop && !$request->force_start) {

            $distance = GeoHelper::distanceMeters(
                $request->latitude,
                $request->longitude,
                $firstStop->latitude,
                $firstStop->longitude
            );

            if ($distance > 200) {

                return response()->json([
                    'success' => false,
                    'confirm_required' => true,
                    'message' => 'Você está distante do ponto inicial. Confirmar início da viagem?'
                ]);
            }
        }

        $trip->update([
            'status' => 'in_progress'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Trip started'
        ]);
    }

    public function finish($id)
    {
        $trip = Trip::findOrFail($id);

        $user = auth()->user();

        if ($trip->driver_id !== $user->id) {
            return response()->json([
                'message' => 'Driver not assigned to this trip'
            ], 403);
        }

        if ($trip->status !== 'in_progress') {
            return response()->json([
                'success' => false,
                'message' => 'Trip cannot be finished'
            ], 400);
        }

        $trip->update([
            'status' => 'finished'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Trip finished',
            'data' => new TripResource(
                $trip->load(['bus', 'route.points', 'route.stops'])
            )
        ]);
    }

    public function todayTrips()
    {
        $active = Trip::with([
            'route',
            'bus',
            'route.points',
            'route.stops'
        ])
            ->where('status', 'in_progress')
            ->get();

        $scheduled = Trip::with([
            'route',
            'bus',
            'route.points',
            'route.stops'
        ])
            ->whereDate('trip_date', today())
            ->where('status', 'scheduled')
            ->orderBy('start_time')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'active' => TripResource::collection($active),
                'scheduled' => TripResource::collection($scheduled)
            ]
        ]);
    }

    public function todayForDriver()
    {
        $user = Auth::user();

        if (!$user->hasRole('driver')) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $trip = Trip::with([
            'bus',
            'route',
            'route.points',
            'route.stops'
        ])
            ->where('school_id', $user->school_id)
            ->where('driver_id', $user->id)
            ->whereDate('trip_date', today())
            ->whereIn('status', ['scheduled', 'in_progress'])
            ->first();

        return response()->json([
            'success' => true,
            'data' => $trip ? new TripResource($trip) : null
        ]);
    }

    public function update(Request $request, $id)
    {
        $trip = Trip::findOrFail($id);

        $trip->update([
            'trip_date' => $request->trip_date ?? $trip->trip_date,
            'status' => $request->status ?? $trip->status,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Trip atualizada',
            'data' => new TripResource($trip)
        ]);
    }

    public function store(Request $request)
    {
        $trip = Trip::create([
            'school_route_id' => $request->school_route_id,
            'bus_id' => $request->bus_id,
            'driver_id' => $request->driver_id,
            'trip_date' => $request->trip_date,
            'start_time' => $request->start_time,
            'status' => 'scheduled'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Trip criada',
            'data' => new TripResource($trip)
        ]);
    }
}