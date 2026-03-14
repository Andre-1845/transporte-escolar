<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\BelongsToSchool;

class SchoolRoute extends Model
{
    use HasFactory, BelongsToSchool;

    protected $fillable = [
        'school_id',
        'name',
        'description',
        'active'
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function buses()
    {
        return $this->belongsToMany(Bus::class);
    }

    public function points()
    {
        return $this->hasMany(RoutePoint::class)->orderBy('order');
    }

    public function stops()
    {
        return $this->hasMany(RouteStop::class)
            ->orderBy('stop_order');
    }

    public function trips()
    {
        return $this->hasMany(Trip::class);
    }
}
