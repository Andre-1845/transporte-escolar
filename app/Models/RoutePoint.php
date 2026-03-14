<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\BelongsToSchool;

class RoutePoint extends Model
{
    use HasFactory, BelongsToSchool;

    protected $fillable = [
        'school_id',
        'school_route_id',
        'name',
        'latitude',
        'longitude',
        'point_order',
        'estimated_time'
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public function route()
    {
        return $this->belongsTo(SchoolRoute::class, 'school_route_id');
    }
}
