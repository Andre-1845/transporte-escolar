<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TripResource;
use App\Models\Trip;
use Illuminate\Http\Request;

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

    public function active()
    {
        $trips = Trip::with(['bus', 'route'])
            ->where('school_id', auth()->user()->school_id)
            ->where('status', 'in_progress')
            ->get();

        return response()->json([
            'success' => true,
            'data' => TripResource::collection($trips)
        ]);
    }

    public function start($id)
    {
        $trip = Trip::findOrFail($id);

        if ($trip->status !== 'scheduled') {
            return response()->json([
                'success' => false,
                'message' => 'Trip cannot be started'
            ], 400);
        }

        // Apenas uma trip ativa por ônibus
        Trip::where('bus_id', $trip->bus_id)
            ->where('status', 'in_progress')
            ->update(['status' => 'completed']);

        $trip->update([
            'status' => 'in_progress'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Trip started',
            'data' => new TripResource($trip->load(['bus', 'route.points']))
        ]);
    }

    public function finish($id)
    {
        $trip = Trip::findOrFail($id);

        if ($trip->status !== 'in_progress') {
            return response()->json([
                'success' => false,
                'message' => 'Trip cannot be finished'
            ], 400);
        }

        $trip->update([
            'status' => 'completed'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Trip finished',
            'data' => new TripResource($trip->load(['bus', 'route.points']))
        ]);
    }

    public function todayForDriver()
    {
        $user = auth()->user();

        if (!$user->hasRole('driver')) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        $trip = Trip::where('school_id', $user->school_id)
            ->where('driver_id', $user->id)
            ->whereDate('trip_date', today())
            ->where('status', 'scheduled')
            ->first();

        return response()->json([
            'success' => true,
            'data' => $trip
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
            'data' => $trip
        ]);
    }

    public function store(Request $request)
    {
        $trip = Trip::create([
            'school_id' => auth()->user()->school_id,
            'school_route_id' => $request->school_route_id,
            'bus_id' => $request->bus_id,
            'driver_id' => $request->driver_id,
            'trip_date' => $request->trip_date,
            'status' => 'scheduled'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Trip criada',
            'data' => $trip
        ]);
    }

    public function reset($id)
    {
        $trip = Trip::findOrFail($id);

        $trip->update([
            'status' => 'scheduled'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Trip resetada'
        ]);
    }
}
