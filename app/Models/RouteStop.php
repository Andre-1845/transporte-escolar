<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RouteStop extends Model
{
    protected $table = 'route_stops';

    protected $fillable = [
        'school_id',
        'school_route_id',
        'name',
        'latitude',
        'longitude',
        'stop_order',
        'radius_meters'
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public function route()
    {
        return $this->belongsTo(SchoolRoute::class, 'school_route_id');
    }

    public function stops()
    {
        return $this->hasMany(RouteStop::class);
    }
}
