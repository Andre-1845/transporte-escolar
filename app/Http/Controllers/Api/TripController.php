<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TripResource;
use App\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Helpers\GeoHelper;
use App\Models\TripStopTracking;
use Illuminate\Support\Facades\DB;

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
            ->where('school_id', Auth::user()->school_id)
            ->where('status', 'in_progress')
            ->get();

        return response()->json([
            'success' => true,
            'data' => TripResource::collection($trips)
        ]);
    }

    public function start(Request $request, $id)
    {
        return DB::transaction(function () use ($request, $id) {

            $trip = Trip::where('id', $id)
                ->lockForUpdate()
                ->with('route.stops')
                ->firstOrFail();

            if (!in_array($trip->status, ['scheduled', 'finished'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Trip cannot be started'
                ], 400);
            }

            // Verifica se motorista já está em viagem
            $driverBusy = Trip::where('driver_id', $trip->driver_id)
                ->where('status', 'in_progress')
                ->lockForUpdate()
                ->exists();

            if ($driverBusy) {
                return response()->json([
                    'success' => false,
                    'message' => 'Motorista já possui uma viagem em andamento'
                ], 400);
            }

            // Verifica se ônibus já está em uso
            $busBusy = Trip::where('bus_id', $trip->bus_id)
                ->where('status', 'in_progress')
                ->lockForUpdate()
                ->exists();

            if ($busBusy) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ônibus já está em uso'
                ], 400);
            }

            // Validação de distância
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
                    ], 200);
                }
            }

            // =========================
            // 🔥 START INTELIGENTE
            // =========================

            $stops = $trip->route->stops->sortBy('stop_order');

            $closestStop = null;
            $minDistance = PHP_FLOAT_MAX;

            foreach ($stops as $stop) {
                $distance = GeoHelper::distanceMeters(
                    $request->latitude,
                    $request->longitude,
                    $stop->latitude,
                    $stop->longitude
                );

                if ($distance < $minDistance) {
                    $minDistance = $distance;
                    $closestStop = $stop;
                }
            }

            $startOrder = $closestStop->stop_order;

            // 🔥 recria tracking
            $trip->stopTracking()->delete();

            foreach ($stops as $stop) {

                if ($stop->stop_order < $startOrder) {
                    $status = 'passed';
                } else {
                    $status = 'pending';
                }

                TripStopTracking::create([
                    'trip_id' => $trip->id,
                    'stop_id' => $stop->id,
                    'stop_order' => $stop->stop_order,
                    'status' => $status
                ]);
            }

            // atualiza compatibilidade
            $trip->update([
                'current_stop_order' => $startOrder
            ]);

            // ✅ INICIA VIAGEM
            $trip->update([
                'status' => 'in_progress',
                'start_time' => now()->format('H:i:s')
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Trip started'
            ]);
        });
    }


    public function finish($id)
    {
        $trip = Trip::findOrFail($id);

        $user = Auth::user();

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

        $error = $this->checkConflicts($request, $id);

        if ($error) {
            return response()->json([
                'success' => false,
                'message' => $error
            ], 400);
        }

        $trip->update([
            'school_route_id' => $request->school_route_id,
            'bus_id' => $request->bus_id,
            'driver_id' => $request->driver_id,
            'trip_date' => $request->trip_date,
            'start_time' => $request->start_time,
            'status' => $request->status,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Trip atualizada',
            'data' => new TripResource($trip)
        ]);
    }

    public function store(Request $request)
    {
        $error = $this->checkConflicts($request);

        if ($error) {
            return response()->json([
                'success' => false,
                'message' => $error
            ], 400);
        }

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

    public function todayTripsForDriver()
    {
        $user = Auth::user();

        if (!$user->hasRole('driver')) {
            return response()->json([
                'message' => 'Acesso negado'
            ], 403);
        }

        $today = today();

        $trips = Trip::with([
            'bus',
            'route',
            'route.points',
            'route.stops'
        ])
            ->where('school_id', $user->school_id)
            ->where('driver_id', $user->id)
            ->whereDate('trip_date', $today)
            ->whereIn('status', ['scheduled', 'in_progress', 'finished'])
            ->orderBy('start_time')
            ->get();

        return response()->json([
            'success' => true,
            'data' => TripResource::collection($trips)
        ]);
    }

    private function checkConflicts($request, $ignoreTripId = null)
    {
        $queryDriver = Trip::where('trip_date', $request->trip_date)
            ->where('start_time', $request->start_time)
            ->where('driver_id', $request->driver_id);

        $queryBus = Trip::where('trip_date', $request->trip_date)
            ->where('start_time', $request->start_time)
            ->where('bus_id', $request->bus_id);

        // 🔥 Ignora a própria trip (no update)
        if ($ignoreTripId) {
            $queryDriver->where('id', '!=', $ignoreTripId);
            $queryBus->where('id', '!=', $ignoreTripId);
        }

        if ($queryDriver->exists()) {
            return "Motorista já possui uma viagem nesse horário";
        }

        if ($queryBus->exists()) {
            return "Ônibus já está em uso nesse horário";
        }

        return null;
    }

    public function cancelAutoFinish($id)
    {
        $trip = Trip::findOrFail($id);

        if ($trip->status === 'finished') {
            return response()->json([
                'success' => false,
                'message' => 'Trip já finalizada'
            ], 400);
        }

        $trip->update([
            'auto_finish_pending' => false,
            'auto_finish_at' => null
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Auto finish cancelado'
        ]);
    }
}
