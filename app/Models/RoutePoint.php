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
        'order',
        'estimated_time'
    ];

    public function route()
    {
        return $this->belongsTo(SchoolRoute::class, 'school_route_id');
    }
}
