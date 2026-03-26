<?php

namespace App\Models;

use App\Helpers\GeoHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\BelongsToSchool;
use App\Services\MovementAnalyzer;

class TripLocation extends Model
{
    use HasFactory, BelongsToSchool;

    protected $fillable = [
        'school_id',
        'trip_id',
        'latitude',
        'longitude',
        'recorded_at',
        'bearing',
        'speed',
        'heading',
        'movement_status'
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'recorded_at' => 'datetime',
        'bearing' => 'float',
        'speed' => 'float'
    ];

    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }

    /**
     * 🔥 NOVO MÉTODO: Calcula distância até um stop point específico
     */
    public function getDistanceToStop($stopId)
    {
        $stop = RouteStop::find($stopId);
        if (!$stop) {
            return null;
        }

        return GeoHelper::distanceMeters(
            $this->latitude,
            $this->longitude,
            $stop->latitude,
            $stop->longitude
        );
    }

    /**
     * Calcula a direção e velocidade baseado no ponto anterior
     */
    public function calculateMovementFromPrevious(?TripLocation $previous): void
    {
        if (!$previous) {
            return;
        }

        $analyzer = app(MovementAnalyzer::class);

        $movement = $analyzer->analyzeMovement(
            $previous->latitude,
            $previous->longitude,
            $previous->recorded_at,
            $this->latitude,
            $this->longitude,
            $this->recorded_at
        );

        $this->bearing = $movement['bearing'];
        $this->speed = $movement['speed'];
        $this->heading = $movement['heading'];
    }
}
