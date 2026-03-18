<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TripStopAlert extends Model
{
    use HasFactory;

    public $timestamps = false; // usamos sent_at manual

    protected $fillable = [
        'trip_id',
        'student_id',
        'route_stop_id',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    // =========================
    // RELACIONAMENTOS
    // =========================

    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function stop()
    {
        return $this->belongsTo(RouteStop::class, 'route_stop_id');
    }
}