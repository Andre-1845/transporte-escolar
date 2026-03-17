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
        $schoolId = auth()->user()->school_id;

        $trips = Trip::with(['route', 'bus', 'driver'])
            ->where('school_id', $schoolId)
            ->orderBy('trip_date', 'desc')
            ->get();

        return view('admin.trips.index', compact('trips'));
    }

    public function create()
    {
        $schoolId = auth()->user()->school_id;

        $drivers = User::drivers()
            ->where('school_id', $schoolId)
            ->orderBy('name')
            ->get();

        $routes = SchoolRoute::where('school_id', $schoolId)
            ->orderBy('name')
            ->get();

        $buses = Bus::where('school_id', $schoolId)
            ->orderBy('plate')
            ->get();

        return view('admin.trips.create', compact(
            'drivers',
            'routes',
            'buses'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'school_route_id' => 'required',
            'bus_id' => 'required',
            'driver_id' => 'required',
            'trip_date' => 'required|date',
        ]);

        Trip::create([
            'school_id' => auth()->user()->school_id,
            'school_route_id' => $request->school_route_id,
            'bus_id' => $request->bus_id,
            'driver_id' => $request->driver_id,
            'trip_date' => $request->trip_date,
            'start_time' => $request->start_time,
            'status' => 'scheduled'
        ]);

        return redirect()->route('admin.trips.index');
    }

    public function edit($id)
    {
        $schoolId = auth()->user()->school_id;

        $trip = Trip::where('school_id', $schoolId)
            ->findOrFail($id);

        $drivers = User::drivers()
            ->where('school_id', $schoolId)
            ->orderBy('name')
            ->get();

        return view('admin.trips.edit', compact('trip', 'drivers'));
    }

    public function update(Request $request, $id)
    {
        $schoolId = auth()->user()->school_id;

        $trip = Trip::where('school_id', $schoolId)
            ->findOrFail($id);

        $request->validate([
            'trip_date' => 'required|date',
            'driver_id' => 'required'
        ]);

        $trip->update([
            'trip_date' => $request->trip_date,
            'start_time' => $request->start_time,
            'status' => $request->status,
            'driver_id' => $request->driver_id
        ]);

        return redirect()->route('admin.trips.index');
    }

    public function start($id)
    {
        $schoolId = auth()->user()->school_id;

        $trip = Trip::where('school_id', $schoolId)
            ->findOrFail($id);

        $trip->update([
            'status' => 'in_progress'
        ]);

        return back();
    }

    public function finish($id)
    {
        $schoolId = auth()->user()->school_id;

        $trip = Trip::where('school_id', $schoolId)
            ->findOrFail($id);

        $trip->update([
            'status' => 'completed'
        ]);

        return back();
    }
}