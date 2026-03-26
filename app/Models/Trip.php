<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\BelongsToSchool;
use Illuminate\Support\Facades\Log;

class Trip extends Model
{
    use HasFactory, BelongsToSchool;

    /**
     * MODEL: Trip
     *
     * ALTERAÇÕES:
     * - Adicionados relacionamentos com TripStopTracking
     * - Adicionado método initializeStops() para criar tracking ao iniciar viagem
     * - Adicionado método getCurrentStop() para buscar stop atual
     * - Removidos campos que agora estão em trip_stop_tracking (mantido por compatibilidade)
     */

    protected $fillable = [
        'school_id',
        'bus_id',
        'driver_id',
        'school_route_id',
        'trip_date',
        'start_time',
        'status',
        // Campos mantidos para compatibilidade (podem ser removidos gradualmente)
        'last_stop_order',
        'current_stop_order',
        'arrived_at_stop',
        'last_stop_at',
        'last_stop_id',
        'last_distance',
        'approaching_stop',
        'auto_finish_pending',
        'auto_finish_at',
        'end_warning_sent'
    ];

    protected $casts = [
        'trip_date' => 'date:Y-m-d',
        'start_time' => 'string',
        'last_stop_at' => 'datetime',
        'auto_finish_at' => 'datetime',
        'arrived_at_stop' => 'boolean',
        'approaching_stop' => 'boolean',
        'auto_finish_pending' => 'boolean',
        'end_warning_sent' => 'boolean'
    ];

    protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->format('Y-m-d');
    }

    // ===============================
    // RELACIONAMENTOS
    // ===============================

    public function bus()
    {
        return $this->belongsTo(Bus::class);
    }

    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function route()
    {
        return $this->belongsTo(SchoolRoute::class, 'school_route_id');
    }

    // public function currentStopTracking()
    // {
    //     return $this->hasOne(TripStopTracking::class)
    //         ->where('status', 'pending')
    //         ->orderBy('stop_order');
    // }

    /**
     * NOVO: Tracking dos stop points da viagem
     */
    public function stopTracking()
    {
        return $this->hasMany(TripStopTracking::class)->orderBy('stop_order');
    }

    /**
     * NOVO: Busca o stop point atual (primeiro com status pending ou approaching)
     */
    public function getCurrentStop()
    {
        return $this->stopTracking()
            ->whereIn('status', ['pending', 'approaching'])
            ->orderBy('stop_order')
            ->first();
    }

    /**
     * NOVO: Busca o próximo stop point após o atual
     */
    public function getNextStop()
    {
        $current = $this->getCurrentStop();
        if (!$current) return null;

        return $this->stopTracking()
            ->where('stop_order', '>', $current->stop_order)
            ->whereIn('status', ['pending', 'approaching'])
            ->orderBy('stop_order')
            ->first();
    }

    /**
     * NOVO: Inicializa os stop points da viagem (chamar ao iniciar a viagem)
     */
    public function initializeStops()
    {
        // Remove tracking existente se houver
        $this->stopTracking()->delete();

        // Busca stops da rota
        $stops = RouteStop::where('school_route_id', $this->school_route_id)
            ->orderBy('stop_order')
            ->get();

        // Cria tracking para cada stop
        foreach ($stops as $stop) {
            TripStopTracking::create([
                'trip_id' => $this->id,
                'stop_id' => $stop->id,
                'stop_order' => $stop->stop_order,
                'status' => 'pending'
            ]);
        }

        // Atualiza campo current_stop_order na tabela trips (compatibilidade)
        if ($stops->isNotEmpty()) {
            $this->update(['current_stop_order' => $stops->first()->stop_order]);
        }

        Log::info('🎯 Stops inicializados para viagem', [
            'trip_id' => $this->id,
            'total_stops' => $stops->count()
        ]);
    }

    public function locations()
    {
        return $this->hasMany(TripLocation::class)
            ->orderBy('recorded_at', 'asc');
    }

    public function latestLocation()
    {
        return $this->hasOne(TripLocation::class)
            ->latestOfMany('recorded_at');
    }

    public function stopAlerts()
    {
        return $this->hasMany(TripStopAlert::class);
    }
}
