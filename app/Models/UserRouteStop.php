<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserRouteStop extends Model
{
    protected $fillable = [
        'user_id',
        'route_stop_id'
    ];
}