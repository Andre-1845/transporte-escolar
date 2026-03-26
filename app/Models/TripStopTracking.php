<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TripStopTracking extends Model
{
    /**
     * MODEL: TripStopTracking
     *
     * FUNÇÃO: Controla o progresso de cada stop point em uma viagem específica
     *
     * ALTERAÇÕES EM RELAÇÃO AO ORIGINAL:
     * - Adicionado método updateAverageTravelTime() para atualizar médias
     * - Adicionado método calculateETAFromCurrentPosition() para ETA dinâmico
     * - Campos completos para métricas de tempo e distância
     */

    protected $table = 'trip_stop_tracking';

    protected $fillable = [
        'trip_id',
        'stop_id',
        'stop_order',
        'status',
        'first_approach_at',
        'reached_at',
        'passed_at',
        'min_distance',
        'distance_at_approach',
        'student_alert_sent',
        'driver_alert_sent',
        'alert_sent_at'
    ];

    protected $casts = [
        'first_approach_at' => 'datetime',
        'reached_at' => 'datetime',
        'passed_at' => 'datetime',
        'alert_sent_at' => 'datetime',
        'student_alert_sent' => 'boolean',
        'driver_alert_sent' => 'boolean',
        'min_distance' => 'float',
        'distance_at_approach' => 'float'
    ];

    // ===============================
    // RELACIONAMENTOS
    // ===============================

    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }

    public function stop()
    {
        return $this->belongsTo(RouteStop::class, 'stop_id');
    }

    // ===============================
    // MÉTODOS DE MUDANÇA DE ESTADO
    // ===============================

    /**
     * Marca o stop point como em aproximação
     */
    public function markAsApproaching(float $distance, $now = null)
    {
        $now = $now ?? now();

        $this->update([
            'status' => 'approaching',
            'first_approach_at' => $this->first_approach_at ?? $now,
            'distance_at_approach' => $distance,
            'min_distance' => $distance
        ]);

        Log::info('🚀 Stop point em aproximação', [
            'trip_id' => $this->trip_id,
            'stop_order' => $this->stop_order,
            'distance' => $distance
        ]);
    }

    /**
     * Marca o stop point como alcançado (chegada)
     */
    public function markAsReached($now = null)
    {
        $now = $now ?? now();

        $this->update([
            'status' => 'reached',
            'reached_at' => $now
        ]);

        Log::info('✅ Stop point alcançado', [
            'trip_id' => $this->trip_id,
            'stop_order' => $this->stop_order
        ]);
    }

    /**
     * Marca o stop point como passado (após sair do raio)
     */
    public function markAsPassed($now = null)
    {
        $now = $now ?? now();

        $this->update([
            'status' => 'passed',
            'passed_at' => $now
        ]);

        // 🔥 ATUALIZA MÉTRICA DE TEMPO ENTRE STOPS
        $this->updateAverageTravelTime();

        Log::info('➡️ Stop point passado', [
            'trip_id' => $this->trip_id,
            'stop_order' => $this->stop_order
        ]);
    }

    /**
     * Atualiza a distância mínima registrada
     */
    public function updateMinDistance(float $distance)
    {
        if ($distance < ($this->min_distance ?? PHP_FLOAT_MAX)) {
            $this->update(['min_distance' => $distance]);
        }
    }

    // ===============================
    // MÉTRICAS DE TEMPO ENTRE STOPS
    // ===============================

    /**
     * Atualiza o tempo médio de viagem entre este stop e o anterior
     */
    private function updateAverageTravelTime()
    {
        // Busca o stop point anterior
        $previousStop = TripStopTracking::where('trip_id', $this->trip_id)
            ->where('stop_order', '<', $this->stop_order)
            ->whereNotNull('reached_at')
            ->orderBy('stop_order', 'desc')
            ->first();

        if (!$previousStop || !$previousStop->reached_at) {
            return;
        }

        // Calcula tempo gasto
        $timeSpent = $this->reached_at->diffInSeconds($previousStop->reached_at);

        // Valida tempo (mínimo 10s, máximo 30min)
        if ($timeSpent < 10 || $timeSpent > 1800) {
            return;
        }

        // Determina período do dia
        $period = $this->getPeriod();

        // Atualiza ou cria métrica
        $metric = DB::table('stop_time_metrics')
            ->where('route_id', $this->trip->school_route_id)
            ->where('from_stop_id', $previousStop->stop_id)
            ->where('to_stop_id', $this->stop_id)
            ->where('period', $period)
            ->first();

        if ($metric) {
            // Média ponderada: 70% histórico, 30% nova amostra
            $newAvg = ($metric->avg_time_seconds * 0.7) + ($timeSpent * 0.3);
            $newCount = $metric->sample_count + 1;

            DB::table('stop_time_metrics')
                ->where('id', $metric->id)
                ->update([
                    'avg_time_seconds' => (int) $newAvg,
                    'sample_count' => $newCount,
                    'updated_at' => now()
                ]);
        } else {
            DB::table('stop_time_metrics')->insert([
                'route_id' => $this->trip->school_route_id,
                'from_stop_id' => $previousStop->stop_id,
                'to_stop_id' => $this->stop_id,
                'avg_time_seconds' => $timeSpent,
                'sample_count' => 1,
                'period' => $period,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        Log::info('📊 Métrica de tempo atualizada', [
            'trip_id' => $this->trip_id,
            'from_stop' => $previousStop->stop_order,
            'to_stop' => $this->stop_order,
            'time_spent' => $timeSpent,
            'period' => $period
        ]);
    }

    /**
     * Calcula o período do dia baseado na hora atual
     */
    private function getPeriod()
    {
        $hour = now()->hour;

        if ($hour >= 6 && $hour < 12) return 'morning';
        if ($hour >= 12 && $hour < 18) return 'afternoon';
        if ($hour >= 18 && $hour < 24) return 'evening';

        return 'night';
    }

    // ===============================
    // CÁLCULO DE ETA
    // ===============================

    /**
     * Calcula o ETA em segundos para este stop point baseado na posição atual
     */
    // public function calculateETAFromCurrentPosition2(float $currentLat, float $currentLng, $currentDistance = null)
    // {
    //     $currentDistance = $currentDistance ?? $this->getCurrentDistance($currentLat, $currentLng);

    //     // Se já chegou, ETA = 0
    //     if ($this->status === 'reached' || $this->status === 'passed') {
    //         return 0;
    //     }

    //     // Se está no raio de aproximação, ETA estimado baseado na distância
    //     $radius = $this->stop->radius_meters ?? 200;
    //     if ($currentDistance <= $radius) {
    //         // Estimativa: se está dentro do raio, ETA é o tempo para percorrer o raio
    //         // Velocidade média estimada: 20 km/h = 5.56 m/s
    //         $estimatedSpeed = 5.56; // m/s
    //         return (int) ($currentDistance / $estimatedSpeed);
    //     }

    //     // Calcula ETA baseado nas médias históricas
    //     $eta = $this->getHistoricalETA();

    //     // Ajusta ETA baseado na distância atual (quanto mais perto, menos tempo)
    //     $distanceFactor = min(1, $radius / max($currentDistance, 1));
    //     $eta = (int) ($eta * $distanceFactor);

    //     return max(5, $eta); // Mínimo 5 segundos
    // }

    /**
     * Obtém o ETA histórico baseado nas médias dos trechos
     */
    public function getHistoricalETA()
    {
        $period = $this->getPeriod();
        $currentStopOrder = $this->stop_order;

        // Busca todos os stops restantes da viagem
        $remainingStops = TripStopTracking::where('trip_id', $this->trip_id)
            ->where('stop_order', '>=', $currentStopOrder)
            ->whereIn('status', ['pending', 'approaching'])
            ->orderBy('stop_order')
            ->get();

        if ($remainingStops->isEmpty()) {
            return 0;
        }

        $totalETA = 0;
        $previousStopId = null;

        foreach ($remainingStops as $index => $stopTrack) {
            if ($index === 0) {
                // Primeiro stop (o atual) - guarda para próximo
                $previousStopId = $stopTrack->stop_id;
                continue;
            }

            // Busca métrica histórica entre stops
            $metric = DB::table('stop_time_metrics')
                ->where('route_id', $this->trip->school_route_id)
                ->where('from_stop_id', $previousStopId)
                ->where('to_stop_id', $stopTrack->stop_id)
                ->where('period', $period)
                ->first();

            $totalETA += $metric ? $metric->avg_time_seconds : 120; // 120s padrão
            $previousStopId = $stopTrack->stop_id;
        }

        return $totalETA;
    }

    /**
     * Calcula distância atual até o stop point
     */
    private function getCurrentDistance(float $lat, float $lng)
    {
        return \App\Helpers\GeoHelper::distanceMeters(
            $lat,
            $lng,
            $this->stop->latitude,
            $this->stop->longitude
        );
    }

    /**
     * Calcula ETA com base na velocidade atual (mais preciso)
     */
    public function calculateETAFromCurrentPosition(
        float $currentLat,
        float $currentLng,
        ?float $currentDistance = null,
        ?float $currentSpeed = null
    ): int {
        $currentDistance = $currentDistance ?? $this->getCurrentDistance($currentLat, $currentLng);

        if ($this->status === 'reached' || $this->status === 'passed') {
            return 0;
        }

        $radius = $this->stop->radius_meters ?? 200;

        // Se tem velocidade atual, usa ela para estimativa mais precisa
        if ($currentSpeed && $currentSpeed > 1) {
            // Velocidade em m/s
            $speedMs = $currentSpeed / 3.6;

            // Tempo para chegar no raio baseado na velocidade atual
            $timeToRadius = max(0, ($currentDistance - $radius) / $speedMs);

            // Adiciona tempo histórico dos próximos stops
            $historicalTime = $this->getHistoricalETA();

            return (int) ($timeToRadius + $historicalTime);
        }

        // Fallback para método anterior (baseado em distância)
        if ($currentDistance <= $radius) {
            $estimatedSpeed = 5.56; // 20 km/h
            return (int) ($currentDistance / $estimatedSpeed);
        }

        $eta = $this->getHistoricalETA();
        $distanceFactor = min(1, $radius / max($currentDistance, 1));
        $eta = (int) ($eta * $distanceFactor);

        return max(5, $eta);
    }
}
