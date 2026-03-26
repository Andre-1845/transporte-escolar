<?php

namespace App\Services;

use App\Models\TripLocation;
use App\Helpers\GeoHelper;

class MovementAnalyzer
{
    /**
     * SERVICE: MovementAnalyzer
     *
     * FUNÇÃO: Analisa movimento do ônibus baseado em pontos consecutivos
     *
     * NOVO: Fornece informações de direção, velocidade e tendência
     */

    /**
     * Analisa movimento entre dois pontos
     *
     * @return array [
     *   'bearing' => float, // direção em graus
     *   'speed' => float,   // velocidade em km/h
     *   'heading' => string, // direção cardinal
     *   'distance' => float, // distância percorrida em metros
     *   'time_diff' => int   // diferença de tempo em segundos
     * ]
     */
    public function analyzeMovement(
        float $lat1,
        float $lng1,
        $time1,
        float $lat2,
        float $lng2,
        $time2
    ): array {
        // Calcula distância percorrida
        $distance = GeoHelper::distanceMeters($lat1, $lng1, $lat2, $lng2);

        // Calcula diferença de tempo
        $timeDiff = abs(strtotime($time2) - strtotime($time1));

        // Calcula velocidade (km/h)
        $speed = $timeDiff > 0 ? ($distance / 1000) / ($timeDiff / 3600) : 0;

        // Calcula bearing (direção em graus)
        $bearing = $this->calculateBearing($lat1, $lng1, $lat2, $lng2);

        // Converte para heading cardinal
        $heading = $this->bearingToHeading($bearing);

        return [
            'bearing' => $bearing,
            'speed' => $speed,
            'heading' => $heading,
            'distance' => $distance,
            'time_diff' => $timeDiff
        ];
    }

    /**
     * Calcula bearing entre dois pontos (fórmula do haversine)
     * Retorna ângulo em graus (0-360) onde 0 = Norte
     */
    public function calculateBearing(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $lat1 = deg2rad($lat1);
        $lat2 = deg2rad($lat2);
        $lng1 = deg2rad($lng1);
        $lng2 = deg2rad($lng2);

        $deltaLng = $lng2 - $lng1;

        $x = sin($deltaLng) * cos($lat2);
        $y = cos($lat1) * sin($lat2) - sin($lat1) * cos($lat2) * cos($deltaLng);

        $bearing = atan2($x, $y);
        $bearing = rad2deg($bearing);
        $bearing = fmod(($bearing + 360), 360);

        return $bearing;
    }

    /**
     * Converte bearing (graus) para heading cardinal
     */
    public function bearingToHeading(float $bearing): string
    {
        $directions = [
            'N' => [0, 22.5],
            'NE' => [22.5, 67.5],
            'E' => [67.5, 112.5],
            'SE' => [112.5, 157.5],
            'S' => [157.5, 202.5],
            'SW' => [202.5, 247.5],
            'W' => [247.5, 292.5],
            'NW' => [292.5, 337.5],
            'N' => [337.5, 360]
        ];

        foreach ($directions as $heading => $range) {
            if ($bearing >= $range[0] && $bearing < $range[1]) {
                return $heading;
            }
        }

        return 'N';
    }

    /**
     * Determina se o ônibus está se aproximando do stop point
     *
     * @param float $busLat, $busLng - Posição atual do ônibus
     * @param float $stopLat, $stopLng - Posição do stop point
     * @param float $bearing - Direção do ônibus (opcional, se não fornecer, calcula)
     * @return bool
     */
    public function isApproachingStop(
        float $busLat,
        float $busLng,
        float $stopLat,
        float $stopLng,
        ?float $bearing = null
    ): bool {
        // Calcula ângulo do ônibus para o stop
        $angleToStop = $this->calculateBearing($busLat, $busLng, $stopLat, $stopLng);

        // Se não forneceu bearing, não podemos determinar direção
        if ($bearing === null) {
            return false;
        }

        // Calcula diferença angular (quanto menor, mais alinhado)
        $angleDiff = abs($bearing - $angleToStop);
        $angleDiff = min($angleDiff, 360 - $angleDiff);

        // Está aproximando se a direção está dentro de 45 graus do stop
        // e a distância está diminuindo
        return $angleDiff <= 45;
    }

    /**
     * Determina o status de movimento do ônibus
     */
    public function getMovementStatus(
        float $speed,
        float $distanceToStop,
        ?float $lastDistanceToStop = null,
        ?float $bearing = null,
        ?float $stopBearing = null
    ): string {
        // Parado: velocidade < 1 km/h
        if ($speed < 1) {
            return 'stopped';
        }

        // Se não temos histórico de distância ou direção, apenas "moving"
        if ($lastDistanceToStop === null || $bearing === null || $stopBearing === null) {
            return 'moving';
        }

        // Calcula diferença de direção
        $angleDiff = abs($bearing - $stopBearing);
        $angleDiff = min($angleDiff, 360 - $angleDiff);

        // Está se aproximando se:
        // 1. Distância está diminuindo
        // 2. Está dentro de 45 graus da direção do stop
        if ($distanceToStop < $lastDistanceToStop && $angleDiff <= 45) {
            return 'approaching';
        }

        // Está se afastando se distância está aumentando
        if ($distanceToStop > $lastDistanceToStop) {
            return 'leaving';
        }

        return 'moving';
    }

    /**
     * Prediz o próximo stop point baseado na direção atual
     */
    public function predictNextStop(
        float $busLat,
        float $busLng,
        float $bearing,
        $stops, // Collection de stops
        int $currentStopOrder
    ): ?object {
        $bestMatch = null;
        $bestScore = 0;

        foreach ($stops as $stop) {
            // Ignora stops anteriores ou o atual
            if ($stop->stop_order <= $currentStopOrder) {
                continue;
            }

            // Calcula ângulo do ônibus para este stop
            $angleToStop = $this->calculateBearing($busLat, $busLng, $stop->latitude, $stop->longitude);

            // Calcula alinhamento (diferença angular)
            $angleDiff = abs($bearing - $angleToStop);
            $angleDiff = min($angleDiff, 360 - $angleDiff);

            // Score: melhor alinhamento (menor ângulo) = maior score
            $alignmentScore = 1 - ($angleDiff / 180);

            // Distância também influencia (stops mais próximos têm prioridade)
            $distance = GeoHelper::distanceMeters($busLat, $busLng, $stop->latitude, $stop->longitude);
            $distanceScore = 1 - min(1, $distance / 5000); // 5km máximo

            // Score combinado
            $totalScore = ($alignmentScore * 0.7) + ($distanceScore * 0.3);

            if ($totalScore > $bestScore) {
                $bestScore = $totalScore;
                $bestMatch = $stop;
            }
        }

        return $bestMatch;
    }
}
