<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TripAlertsLog extends Model
{
    use HasFactory;

    /**
     * MODEL: TripAlertsLog
     *
     * FUNÇÃO: Gerencia o log de alertas enviados durante as viagens
     *
     * 🔥 RECURSOS:
     * - Registro completo de todos alertas
     * - Métodos auxiliares para consultas
     * - Scopes para filtros comuns
     * - Relacionamentos com Trip, Stop e User
     */

    protected $table = 'trip_alerts_log';

    protected $fillable = [
        'trip_id',
        'stop_id',
        'user_id',
        'alert_type',
        'distance_at_alert',
        'metadata',
        'delivered',
        'sent_at'
    ];

    protected $casts = [
        'distance_at_alert' => 'float',
        'metadata' => 'array',
        'delivered' => 'boolean',
        'sent_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // ===============================
    // RELACIONAMENTOS
    // ===============================

    /**
     * Relacionamento com a viagem
     */
    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }

    /**
     * Relacionamento com o stop point
     */
    public function stop()
    {
        return $this->belongsTo(RouteStop::class, 'stop_id');
    }

    /**
     * Relacionamento com o usuário (motorista ou aluno)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ===============================
    // SCOPES (Filtros)
    // ===============================

    /**
     * Scope para filtrar por viagem
     */
    public function scopeForTrip($query, $tripId)
    {
        return $query->where('trip_id', $tripId);
    }

    /**
     * Scope para filtrar por stop point
     */
    public function scopeForStop($query, $stopId)
    {
        return $query->where('stop_id', $stopId);
    }

    /**
     * Scope para filtrar por usuário
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope para filtrar por tipo de alerta
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('alert_type', $type);
    }

    /**
     * Scope para alertas não entregues
     */
    public function scopeNotDelivered($query)
    {
        return $query->where('delivered', false);
    }

    /**
     * Scope para alertas entregues
     */
    public function scopeDelivered($query)
    {
        return $query->where('delivered', true);
    }

    /**
     * Scope para alertas em um período
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('sent_at', [$startDate, $endDate]);
    }

    // ===============================
    // MÉTODOS AUXILIARES
    // ===============================

    /**
     * Marca o alerta como entregue
     */
    public function markAsDelivered()
    {
        $this->update(['delivered' => true]);
    }

    /**
     * Verifica se é alerta de aproximação
     */
    public function isApproaching()
    {
        return $this->alert_type === 'approaching';
    }

    /**
     * Verifica se é alerta de chegada
     */
    public function isReached()
    {
        return $this->alert_type === 'reached';
    }

    /**
     * Verifica se é alerta de passagem
     */
    public function isPassed()
    {
        return $this->alert_type === 'passed';
    }

    /**
     * Verifica se é alerta de fim de rota
     */
    public function isEndWarning()
    {
        return $this->alert_type === 'end_warning';
    }

    /**
     * Retorna a distância formatada
     */
    public function getFormattedDistanceAttribute()
    {
        if ($this->distance_at_alert === null) {
            return null;
        }

        return $this->distance_at_alert < 1000
            ? round($this->distance_at_alert) . 'm'
            : number_format($this->distance_at_alert / 1000, 1) . 'km';
    }

    /**
     * Retorna o texto do alerta baseado no tipo
     */
    public function getAlertMessageAttribute()
    {
        $messages = [
            'approaching' => 'Aproximando do ponto',
            'reached' => 'Chegou ao ponto',
            'passed' => 'Passou pelo ponto',
            'end_warning' => 'Aproximando do fim da rota',
            'broadcast' => 'Aviso geral',
            'driver_alert' => 'Alerta para motorista',
            'student_alert' => 'Alerta para aluno'
        ];

        return $messages[$this->alert_type] ?? 'Alerta';
    }

    /**
     * Retorna o ícone baseado no tipo de alerta
     */
    public function getIconAttribute()
    {
        $icons = [
            'approaching' => '🚌',
            'reached' => '📍',
            'passed' => '➡️',
            'end_warning' => '🏁',
            'broadcast' => '📢',
            'driver_alert' => '👨‍✈️',
            'student_alert' => '👨‍🎓'
        ];

        return $icons[$this->alert_type] ?? '🔔';
    }
}
