<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bus;
use App\Models\SchoolRoute;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Http\Request;

class TripAdminController extends Controller
{

    public function index()
    {
        $trips = Trip::with(['route', 'bus'])
            ->orderBy('trip_date', 'desc')
            ->get();

        return view('admin.trips.index', compact('trips'));
    }

    public function create()
    {
        $drivers = User::role('driver')->get();
        $routes = SchoolRoute::orderBy('name')->get();
        $buses = Bus::orderBy('plate')->get();

        return view('admin.trips.create', compact(
            'drivers',
            'routes',
            'buses'
        ));
    }

    public function store(Request $request)
    {
        Trip::create([
            'school_route_id' => $request->school_route_id,
            'bus_id' => $request->bus_id,
            'driver_id' => $request->driver_id,
            'trip_date' => $request->trip_date,
            'start_time' => $request->start_time,
            'status' => 'scheduled'
        ]);

        return redirect('/admin/trips');
    }

    public function edit($id)
    {
        $trip = Trip::findOrFail($id);
        $drivers = User::role('driver')->get();

        return view('admin.trips.edit', compact('trip', 'drivers'));
    }

    public function update(Request $request, $id)
    {
        $trip = Trip::findOrFail($id);

        $trip->update([
            'trip_date' => $request->trip_date,
            'start_time' => $request->start_time,
            'status' => $request->status,
            'driver_id' => $request->driver_id
        ]);

        return redirect('/admin/trips');
    }

    public function start($id)
    {
        $trip = Trip::findOrFail($id);

        $trip->update(['status' => 'in_progress']);

        return back();
    }

    public function finish($id)
    {
        $trip = Trip::findOrFail($id);

        $trip->update(['status' => 'completed']);

        return back();
    }
}
