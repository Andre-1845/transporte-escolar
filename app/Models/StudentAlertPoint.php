<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentAlertPoint extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'route_stop_id',
        'enabled',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    // =========================
    // RELACIONAMENTOS
    // =========================

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function stop()
    {
        return $this->belongsTo(RouteStop::class, 'route_stop_id');
    }
}