<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use App\Models\TripLocation;
use App\Services\StopPointManager;
use App\Helpers\GeoHelper;
use App\Models\TripStopTracking;
use App\Services\AlertService;
use App\Services\MovementAnalyzer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TripLocationController extends Controller
{
    /**
     * CONTROLLER: TripLocationController
     *
     * FUNÇÃO: Recebe e processa localizações do motorista
     *
     * ALTERAÇÕES:
     * - Simplificado, delegando lógica para StopPointManager
     * - Endpoint de latest-location otimizado com ETA
     */
    // ----------------------------------------------------  //

    private $stopPointManager;
    private $movementAnalyzer;

    public function __construct(StopPointManager $stopPointManager, MovementAnalyzer $movementAnalyzer)
    {
        $this->stopPointManager = $stopPointManager;
        $this->movementAnalyzer = $movementAnalyzer;
    }

    /**
     * Recebe localização do motorista (AGORA COM DIREÇÃO)
     */
    public function store(Request $request, $tripId)
    {
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'bearing' => 'nullable|numeric|between:0,360', // Pode vir do GPS
            'speed' => 'nullable|numeric|min:0' // Pode vir do GPS
        ]);

        $trip = Trip::findOrFail($tripId);

        if ($trip->status !== 'in_progress') {
            return response()->json([
                'success' => false,
                'message' => 'Trip not active'
            ], 400);
        }

        $lat = $request->latitude;
        $lng = $request->longitude;

        // Busca última localização para calcular direção
        $previousLocation = TripLocation::where('trip_id', $trip->id)
            ->orderBy('recorded_at', 'desc')
            ->first();

        // Prepara dados da localização
        $locationData = [
            'school_id' => $trip->school_id,
            'trip_id' => $trip->id,
            'latitude' => $lat,
            'longitude' => $lng,
            'recorded_at' => now(),
        ];

        // Se o app enviou bearing/speed do GPS, usa eles
        if ($request->has('bearing')) {
            $locationData['bearing'] = $request->bearing;
            $locationData['heading'] = $this->movementAnalyzer->bearingToHeading($request->bearing);
        }

        if ($request->has('speed')) {
            $locationData['speed'] = $request->speed;
        }

        // Se não veio do GPS, calcula baseado no ponto anterior
        $location = new TripLocation($locationData);
        if (!$request->has('bearing') && $previousLocation) {
            $location->calculateMovementFromPrevious($previousLocation);
        }

        $location->save();

        // Processa lógica com direção
        $result = $this->stopPointManager->processLocation($trip, $lat, $lng, $previousLocation);

        // Atualiza status de movimento na localização
        if (isset($result['movement_status'])) {
            $location->movement_status = $result['movement_status'];
            $location->save();
        }

        return response()->json([
            'success' => true,
            'approaching_end' => $this->checkEndWarning($trip) ? 1 : 0,
            'data' => [
                'lat' => $lat,
                'lng' => $lng,
                'distance' => $result['distance'],
                'current_stop' => $result['current_stop'],
                'arrived' => $this->getArrivedStatus($trip) ? 1 : 0,
                'advanced' => $result['advanced'] ? 1 : 0,
                'eta_seconds' => $result['eta_seconds'],
                'bearing' => $result['bearing'],
                'speed' => $result['speed'],
                'movement_status' => $result['movement_status'],
                'predicted_next_stop' => $result['predicted_next_stop'] ?? null
            ]
        ]);
    }

    /**
     * Última localização (AGORA COM INFORMAÇÕES DE DIREÇÃO)
     */
    public function latest($tripId)
    {
        $trip = Trip::with(['latestLocation', 'stopTracking.stop'])
            ->findOrFail($tripId);

        if ($trip->auto_finish_pending && $trip->auto_finish_at <= now()) {
            $trip->update([
                'status' => 'finished',
                'auto_finish_pending' => false,
                'auto_finish_at' => null
            ]);
        }

        if (!$trip->latestLocation) {
            return response()->json([
                'success' => true,
                'data' => null
            ]);
        }

        $location = $trip->latestLocation;
        $lat = $location->latitude;
        $lng = $location->longitude;

        $currentTracking = TripStopTracking::where('trip_id', $trip->id)
            ->whereIn('status', ['pending', 'approaching'])
            ->orderBy('stop_order')
            ->first();
        $distance = null;
        $nextStop = null;
        $eta = null;

        if ($currentTracking) {
            $currentStop = $currentTracking->stop;
            $distance = GeoHelper::distanceMeters(
                $lat,
                $lng,
                $currentStop->latitude,
                $currentStop->longitude
            );

            $eta = $currentTracking->calculateETAFromCurrentPosition(
                $lat,
                $lng,
                $distance,
                $location->speed
            );

            $nextStop = [
                'id' => $currentStop->id,
                'name' => $currentStop->name,
                'order' => $currentStop->stop_order,
                'status' => $currentTracking->status,
                'distance' => $distance,
                'radius_meters' => $currentStop->radius_meters ?? 200,
                'bearing_to_stop' => $this->movementAnalyzer->calculateBearing(
                    $lat,
                    $lng,
                    $currentStop->latitude,
                    $currentStop->longitude
                )
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'lat' => $lat,
                'lng' => $lng,
                'bearing' => $location->bearing,
                'speed' => $location->speed,
                'heading' => $location->heading,
                'movement_status' => $location->movement_status,
                'distance' => $distance,
                'next_stop' => $nextStop,
                'eta_seconds' => $eta,
                'auto_finish_pending' => $trip->auto_finish_pending ? 1 : 0,
                'auto_finish_seconds' => $trip->auto_finish_at
                    ? max(0, now()->diffInSeconds($trip->auto_finish_at, false))
                    : null,
            ]
        ]);
    }


// -------------------------------------------------  //


    /**
     * Verifica se deve enviar aviso de fim de rota
     */
    private function checkEndWarning(Trip $trip): bool
    {
        if ($trip->end_warning_sent) {
            return false;
        }

        $currentTracking = $trip->getCurrentStop();
        if (!$currentTracking) {
            return false;
        }

        // Verifica se é o último stop
        $lastStop = $trip->stopTracking()
            ->orderBy('stop_order', 'desc')
            ->first();

        if ($lastStop && $lastStop->id === $currentTracking->stop_id) {
            // Marca como enviado
            $trip->update(['end_warning_sent' => true]);

            // Dispara alerta
            $eta = $currentTracking->calculateETAFromCurrentPosition(
                $trip->latestLocation->latitude,
                $trip->latestLocation->longitude
            );

            app(AlertService::class)->sendEndWarning($trip, $eta);

            return true;
        }

        return false;
    }

    /**
     * Obtém status de arrived para compatibilidade
     */
    private function getArrivedStatus(Trip $trip): bool
    {
        $currentTracking = $trip->getCurrentStop();
        if (!$currentTracking) {
            return false;
        }

        return $currentTracking->status === 'reached';
    }
}
