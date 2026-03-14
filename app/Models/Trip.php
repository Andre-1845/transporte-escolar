<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\BelongsToSchool;

class Trip extends Model
{
    use HasFactory, BelongsToSchool;

    protected $fillable = [
        'school_id',
        'bus_id',
        'driver_id',
        'school_route_id',
        'trip_date',
        'start_time',
        'status',
    ];

    protected $casts = [
        'trip_date' => 'date:Y-m-d',
        'start_time' => 'string',
    ];

    protected static function booted()
    {
        static::creating(function ($trip) {

            if (!$trip->school_id) {
                $trip->school_id = auth()->user()->school_id;
            }
        });
    }

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

    public function locations()
    {
        return $this->hasMany(TripLocation::class)
            ->orderBy('recorded_at');
    }
}
