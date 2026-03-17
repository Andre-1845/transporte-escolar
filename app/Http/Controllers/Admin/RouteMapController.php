<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SchoolRoute;
use App\Models\RoutePoint;
use App\Models\RouteStop;

class RouteMapController extends Controller
{
    public function edit($id)
    {
        $route = SchoolRoute::with([
            'points' => function ($q) {
                $q->orderBy('point_order');
            },
            'stops' => function ($q) {
                $q->orderBy('stop_order');
            }
        ])->findOrFail($id);

        return view('admin.routes.map', compact('route'));
    }
}