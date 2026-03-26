<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\BelongsToSchool;

class TripLocation extends Model
{
    use HasFactory, BelongsToSchool;

    /**
     * MODEL: TripLocation
     *
     * ALTERAÇÕES:
     * - Adicionados campos de direção e movimento
     * - Adicionado método para calcular direção entre pontos
     */

    protected $fillable = [
        'school_id',
        'trip_id',
        'latitude',
        'longitude',
        'recorded_at',
        'bearing',      // NOVO: direção em graus
        'speed',        // NOVO: velocidade km/h
        'heading',      // NOVO: direção cardinal
        'movement_status' // NOVO: status de movimento
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
     * Calcula a direção e velocidade baseado no ponto anterior
     */
    public function calculateMovementFromPrevious(?TripLocation $previous): void
    {
        if (!$previous) {
            return;
        }

        $analyzer = app(\App\Services\MovementAnalyzer::class);

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
