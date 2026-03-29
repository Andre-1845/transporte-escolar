<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StopTimeMetrics extends Model
{
    protected $table = 'stop_time_metrics';

    protected $fillable = [
        'route_id',
        'from_stop_id',
        'to_stop_id',
        'avg_time_seconds',
        'period',
        'sample_count'
    ];

    protected $casts = [
        'avg_time_seconds' => 'integer',
        'sample_count' => 'integer',
    ];

    public function fromStop()
    {
        return $this->belongsTo(RouteStop::class, 'from_stop_id');
    }

    public function toStop()
    {
        return $this->belongsTo(RouteStop::class, 'to_stop_id');
    }

    public function route()
    {
        return $this->belongsTo(SchoolRoute::class, 'route_id');
    }
}
