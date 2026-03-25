<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use App\Models\TripLocation;
use App\Models\RouteStop;
use App\Models\StudentAlertPoint;
use App\Models\TripStopAlert;
use Illuminate\Http\Request;
use App\Helpers\GeoHelper;
use App\Services\FirebasePushService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
                'success' => false,
                'message' => 'Trip not active'
            ], 400);
        }

        $lat = $request->latitude;
        $lng = $request->longitude;

        // 📍 SALVAR LOCALIZAÇÃO
        $location = TripLocation::create([
            'school_id' => $trip->school_id,
            'trip_id' => $trip->id,
            'latitude' => $lat,
            'longitude' => $lng,
            'recorded_at' => now(),
        ]);

        $stops = RouteStop::where('school_route_id', $trip->school_route_id)
            ->orderBy('stop_order')
            ->get()
            ->values();

        if ($stops->isEmpty()) {
            return response()->json(['success' => true]);
        }

        $lastStopOrder = $stops->max('stop_order');

        // 🏁 FINAL DE ROTA
        if ($trip->current_stop_order > $lastStopOrder) {
            $trip->update([
                'status' => 'finished'
            ]);
            return response()->json(['success' => true]);
        }

        $currentStop = $stops->firstWhere('stop_order', $trip->current_stop_order);

        if (!$currentStop) {
            return response()->json(['success' => true]);
        }

        // 📏 DISTÂNCIA
        $distance = GeoHelper::distanceMeters(
            $lat,
            $lng,
            $currentStop->latitude,
            $currentStop->longitude
        );

        $lastDistance = $trip->last_distance;

        $data = [];
        $shouldUpdate = false;
        $approachingEnd = false;

        // Flag para controlar se já avançamos neste request
        $advancedToNextStop = false;

        // ===============================
        // 🚀 APROXIMAÇÃO (quando está se aproximando)
        // ===============================
        if ($lastDistance && $distance < $lastDistance) {
            $data['approaching_stop'] = true;
            $shouldUpdate = true;
        }

        // ===============================
        // 🚀 CHEGADA NO PONTO
        // ===============================
        // Chegou se:
        // 1. Ainda não marcou como arrived
        // 2. Distância menor que o raio do ponto OU menor que 40 metros
        $arrivalRadius = $currentStop->radius_meters ?? 200;
        $isWithinArrivalRadius = $distance <= $arrivalRadius;

        if (!$trip->arrived_at_stop && $isWithinArrivalRadius) {

            $now = now();

            // 🔥 MÉTRICAS ENTRE PARADAS
            if (
                $trip->last_stop_at &&
                $trip->last_stop_id &&
                $trip->last_stop_id != $currentStop->id
            ) {

                $timeSpent = $now->diffInSeconds($trip->last_stop_at);

                $this->updateStopMetrics(
                    $trip,
                    $trip->last_stop_id,
                    $currentStop->id,
                    $timeSpent
                );
            }

            $data['arrived_at_stop'] = true;
            $data['last_stop_at'] = $now;
            $data['last_stop_id'] = $currentStop->id;

            // 🔥 NOVA LÓGICA: Avança para o próximo stop IMEDIATAMENTE
            $nextStopOrder = $trip->current_stop_order + 1;

            // Verifica se existe próximo stop
            $nextStopExists = $stops->contains('stop_order', $nextStopOrder);

            if ($nextStopExists) {
                // Avança para o próximo stop
                $data['current_stop_order'] = $nextStopOrder;
                $data['arrived_at_stop'] = false;  // Reseta para o próximo stop
                $data['approaching_stop'] = false; // Reseta flag de aproximação

                $advancedToNextStop = true;

                // Log para debug
                \Log::info('Stop avançado', [
                    'trip_id' => $trip->id,
                    'from_stop' => $trip->current_stop_order,
                    'to_stop' => $nextStopOrder,
                    'distance' => $distance,
                    'radius' => $arrivalRadius
                ]);
            } else {
                // É o último stop, marca para finalizar
                $data['auto_finish_pending'] = true;
                $data['auto_finish_at'] = now()->addSeconds(15);
            }

            $shouldUpdate = true;
        }

        // ===============================
        // ⚠️ GARANTIA: Se por algum motivo não avançou mas já estava arrived
        // (caso de fallback para evitar travamento)
        // ===============================
        if (!$advancedToNextStop && $trip->arrived_at_stop && !$trip->auto_finish_pending) {
            // Verifica se já deveria ter avançado
            $nextStopOrder = $trip->current_stop_order + 1;
            $nextStopExists = $stops->contains('stop_order', $nextStopOrder);

            if ($nextStopExists) {
                $data['current_stop_order'] = $nextStopOrder;
                $data['arrived_at_stop'] = false;
                $data['approaching_stop'] = false;
                $shouldUpdate = true;

                \Log::warning('Stop avançado por fallback', [
                    'trip_id' => $trip->id,
                    'from_stop' => $trip->current_stop_order,
                    'to_stop' => $nextStopOrder
                ]);
            }
        }

        // ===============================
        // 🏁 ÚLTIMA PARADA - Enviar aviso de finalização
        // ===============================
        $isLastStop = $currentStop->stop_order === $stops->last()->stop_order;

        if ($isLastStop && !$trip->end_warning_sent && !$advancedToNextStop) {
            $data['end_warning_sent'] = true;
            $approachingEnd = true;
            $shouldUpdate = true;
        }

        // ===============================
        // 🔔 ALERTAS DE ALUNOS (só dispara quando chega no ponto)
        // ===============================
        if (
            $currentStop->allow_student_alert &&
            $isWithinArrivalRadius &&
            !$trip->arrived_at_stop
        ) {

            $alerts = StudentAlertPoint::where('route_stop_id', $currentStop->id)
                ->where('enabled', true)
                ->get();

            foreach ($alerts as $alert) {
                $alreadySent = TripStopAlert::where('trip_id', $trip->id)
                    ->where('student_id', $alert->student_id)
                    ->exists();

                if (!$alreadySent) {
                    FirebasePushService::sendToUser(
                        $alert->student_id,
                        "🚌 Seu ônibus está chegando",
                        "Parada: {$currentStop->name}"
                    );

                    TripStopAlert::create([
                        'trip_id' => $trip->id,
                        'student_id' => $alert->student_id,
                        'route_stop_id' => $currentStop->id,
                        'sent_at' => now()
                    ]);
                }
            }
        }

        // ===============================
        // 💾 SALVAR DISTÂNCIA
        // ===============================
        $data['last_distance'] = $distance;

        if ($shouldUpdate) {
            $trip->update($data);
        }

        // 🧹 LIMPEZA AUTOMÁTICA (mantido)
        if (rand(1, 100) === 1) {
            DB::table('trip_locations')
                ->where('recorded_at', '<', Carbon::now()->subDays(5))
                ->limit(1000)
                ->delete();
        }

        // No método store() do TripLocationController, adicione:
        Log::info('Verificação de chegada', [
            'trip_id' => $trip->id,
            'current_stop_order' => $trip->current_stop_order,
            'stop_name' => $currentStop->name,
            'stop_lat' => $currentStop->latitude,
            'stop_lng' => $currentStop->longitude,
            'bus_lat' => $lat,
            'bus_lng' => $lng,
            'distance' => $distance,
            'radius' => $currentStop->radius_meters ?? 200,
            'arrived_at_stop' => $trip->arrived_at_stop,
            'is_within_radius' => $distance <= ($currentStop->radius_meters ?? 200)
        ]);

        return response()->json([
            'success' => true,
            'approaching_end' => $approachingEnd,
            'data' => [
                'lat' => $location->latitude,
                'lng' => $location->longitude,
                'distance' => $distance,
                'current_stop' => $trip->current_stop_order,
                'arrived' => $trip->arrived_at_stop,
                'advanced' => $advancedToNextStop
            ]
        ]);
    }

    // ===============================
    // 📍 ÚLTIMA LOCALIZAÇÃO + ETA
    // ===============================
    public function latest($tripId)
    {
        $trip = Trip::with(['latestLocation'])->findOrFail($tripId);

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

        $lat = $trip->latestLocation->latitude;
        $lng = $trip->latestLocation->longitude;

        $stops = RouteStop::where('school_route_id', $trip->school_route_id)
            ->orderBy('stop_order')
            ->get()
            ->values();

        $nextStop = $stops->firstWhere(
            'stop_order',
            $trip->current_stop_order
        );

        $distance = null;

        if ($nextStop) {
            $distance = GeoHelper::distanceMeters(
                $lat,
                $lng,
                $nextStop->latitude,
                $nextStop->longitude
            );
        }

        $eta = $nextStop
            ? $this->calculateETA($trip, $stops, $nextStop)
            : null;

        return response()->json([
            'success' => true,
            'data' => [
                'lat' => $lat,
                'lng' => $lng,
                'distance' => $distance,
                'next_stop' => $nextStop ? [
                    'id' => $nextStop->id,
                    'name' => $nextStop->name,
                    'order' => $nextStop->stop_order,
                ] : null,
                'eta_seconds' => $eta,
                'auto_finish_pending' => $trip->auto_finish_pending,
                'auto_finish_seconds' => $trip->auto_finish_at
                    ? max(0, now()->diffInSeconds($trip->auto_finish_at, false))
                    : null,
            ]
        ]);
    }

    // ===============================
    // 📊 MÉTRICAS DE TEMPO
    // ===============================
    private function updateStopMetrics($trip, $fromStopId, $toStopId, $timeSpent)
    {
        if ($timeSpent < 10 || $timeSpent > 1800) return;

        $period = $this->getPeriod();

        $metric = DB::table('stop_time_metrics')
            ->where('route_id', $trip->school_route_id)
            ->where('from_stop_id', $fromStopId)
            ->where('to_stop_id', $toStopId)
            ->where('period', $period)
            ->first();

        if ($metric) {
            $newAvg = ($metric->avg_time_seconds * 0.7) + ($timeSpent * 0.3);

            DB::table('stop_time_metrics')
                ->where('id', $metric->id)
                ->update([
                    'avg_time_seconds' => (int) $newAvg,
                    'updated_at' => now()
                ]);
        } else {
            DB::table('stop_time_metrics')->insert([
                'route_id' => $trip->school_route_id,
                'from_stop_id' => $fromStopId,
                'to_stop_id' => $toStopId,
                'avg_time_seconds' => $timeSpent,
                'period' => $period,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }

    private function getPeriod()
    {
        $hour = now()->hour;

        if ($hour >= 6 && $hour < 12) return 'morning';
        if ($hour >= 12 && $hour < 18) return 'afternoon';
        if ($hour >= 18 && $hour < 24) return 'evening';

        return 'night';
    }

    // ===============================
    // ⏱️ ETA REAL
    // ===============================
    private function calculateETA($trip, $stops, $currentStop)
    {
        $currentIndex = $stops->search(fn($s) => $s->id === $currentStop->id);

        if ($currentIndex === false) return null;

        $eta = 0;
        $period = $this->getPeriod();

        for ($i = $currentIndex; $i < count($stops) - 1; $i++) {

            $from = $stops[$i];
            $to = $stops[$i + 1];

            $metric = DB::table('stop_time_metrics')
                ->where('route_id', $trip->school_route_id)
                ->where('from_stop_id', $from->id)
                ->where('to_stop_id', $to->id)
                ->where('period', $period)
                ->first();

            $eta += $metric ? $metric->avg_time_seconds : 120;
        }

        return $eta;
    }
}