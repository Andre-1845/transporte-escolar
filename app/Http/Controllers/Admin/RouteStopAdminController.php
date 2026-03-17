<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RouteStop;
use App\Models\SchoolRoute;
use Illuminate\Http\Request;

class RouteStopAdminController extends Controller
{

    public function index($routeId)
    {
        $route = SchoolRoute::findOrFail($routeId);

        $stops = RouteStop::where('school_route_id', $routeId)
            ->orderBy('stop_order')
            ->get();

        return view(
            'admin.routes.stops',
            compact('route', 'stops')
        );
    }

    public function store(Request $request, $routeId)
    {

        $max = RouteStop::where('school_route_id', $routeId)
            ->max('stop_order');

        RouteStop::create([
            'school_id' => auth()->user()->school_id,
            'school_route_id' => $routeId,
            'name' => $request->name,
            'latitude' => $request->latitude ?? 0,
            'longitude' => $request->longitude ?? 0,
            'radius_meters' => $request->radius_meters ?? 200,
            'stop_order' => $max + 1
        ]);

        return redirect()->back();
    }

    public function update(Request $request, $id)
    {

        $stop = RouteStop::findOrFail($id);

        $stop->update([
            'name' => $request->name,
            'radius_meters' => $request->radius_meters
        ]);

        return redirect()->back();
    }

    public function destroy($id)
    {

        $stop = RouteStop::findOrFail($id);

        $stop->delete();

        return redirect()->back();
    }

    public function moveUp($id)
    {

        $stop = RouteStop::findOrFail($id);

        $prev = RouteStop::where(
            'school_route_id',
            $stop->school_route_id
        )
            ->where('stop_order', '<', $stop->stop_order)
            ->orderBy('stop_order', 'desc')
            ->first();

        if ($prev) {

            $temp = $stop->stop_order;

            $stop->stop_order = $prev->stop_order;
            $prev->stop_order = $temp;

            $stop->save();
            $prev->save();
        }

        return back();
    }

    public function moveDown($id)
    {

        $stop = RouteStop::findOrFail($id);

        $next = RouteStop::where(
            'school_route_id',
            $stop->school_route_id
        )
            ->where('stop_order', '>', $stop->stop_order)
            ->orderBy('stop_order')
            ->first();

        if ($next) {

            $temp = $stop->stop_order;

            $stop->stop_order = $next->stop_order;
            $next->stop_order = $temp;

            $stop->save();
            $next->save();
        }

        return back();
    }
}