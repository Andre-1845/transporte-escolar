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

    public function active()
    {
        $trips = Trip::with(['bus', 'route'])
            ->where('school_id', auth()->user()->school_id)
            ->where('status', 'in_progress')
            ->get();

        if ($trips->isEmpty()) {
            return response()->json([
                'success' => true,
                'data' => []
            ]);
        }

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

        // REGRA IMPORTANTE: apenas 1 trip ativa por ônibus
        Trip::where('bus_id', $trip->bus_id)
            ->where('status', 'in_progress')
            ->update(['status' => 'completed']);

        //  Inicia a nova trip
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
}