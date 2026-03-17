<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RouteStop;
use App\Models\RoutePoint;
use App\Models\SchoolRoute;

class RouteEditorController extends Controller
{

    /*
    |--------------------------------------------------------------------------
    | MAP EDITOR PAGE
    |--------------------------------------------------------------------------
    */

    public function edit($id)
    {
        $route = SchoolRoute::with([
            'points',
            'stops'
        ])->findOrFail($id);

        return view('admin.routes.map', compact('route'));
    }


    /*
    |--------------------------------------------------------------------------
    | CREATE STOP
    |--------------------------------------------------------------------------
    */

    public function storeStop(Request $request)
    {

        $request->validate([
            'route_id' => 'required',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        $order = RouteStop::where(
            'school_route_id',
            $request->route_id
        )->max('stop_order');

        $stop = RouteStop::create([
            'school_id' => auth()->user()->school_id,
            'school_route_id' => $request->route_id,
            'name' => $request->name ?? 'Nova parada',
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'stop_order' => $order ? $order + 1 : 1,
            'radius_meters' => 200
        ]);

        return response()->json([
            'success' => true,
            'data' => $stop
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | CREATE ROUTE POINT
    |--------------------------------------------------------------------------
    */

    public function storePoint(Request $request)
    {

        $request->validate([
            'route_id' => 'required',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        $order = RoutePoint::where(
            'school_route_id',
            $request->route_id
        )->max('point_order');

        $point = RoutePoint::create([
            'school_id' => auth()->user()->school_id,
            'school_route_id' => $request->route_id,
            'name' => 'Ponto',
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'point_order' => $order ? $order + 1 : 1,
        ]);

        return response()->json([
            'success' => true,
            'data' => $point
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | UPDATE STOP POSITION
    |--------------------------------------------------------------------------
    */

    public function updateStop(Request $request, $id)
    {

        $stop = RouteStop::findOrFail($id);

        $stop->update([
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
        ]);

        return response()->json([
            'success' => true
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | UPDATE POINT POSITION
    |--------------------------------------------------------------------------
    */

    public function updatePoint(Request $request, $id)
    {

        $point = RoutePoint::findOrFail($id);

        $point->update([
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
        ]);

        return response()->json([
            'success' => true
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | DELETE STOP
    |--------------------------------------------------------------------------
    */

    public function deleteStop($id)
    {

        $stop = RouteStop::findOrFail($id);

        $stop->delete();

        return response()->json([
            'success' => true
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | DELETE POINT
    |--------------------------------------------------------------------------
    */

    public function deletePoint($id)
    {

        $point = RoutePoint::findOrFail($id);

        $point->delete();

        return response()->json([
            'success' => true
        ]);
    }

    /* REORDENAR STOPS   */

    public function reorderStops(Request $request)
    {

        foreach ($request->order as $item) {

            RouteStop::where('id', $item['id'])
                ->update([
                    'stop_order' => $item['order']
                ]);
        }

        return response()->json([
            'success' => true
        ]);
    }
}