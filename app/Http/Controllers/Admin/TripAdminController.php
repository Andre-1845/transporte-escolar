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
            'start_time' => 'required'
        ]);

        $error = $this->checkConflicts($request);

        if ($error) {
            return redirect()->back()->with('error', $error)->withInput();
        }

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

        $routes = SchoolRoute::where('school_id', $schoolId)
            ->orderBy('name')
            ->get();

        $buses = Bus::where('school_id', $schoolId)
            ->orderBy('plate')
            ->get();

        return view('admin.trips.edit', compact('trip', 'drivers', 'buses', 'routes'));
    }

    public function update(Request $request, $id)
    {
        $schoolId = auth()->user()->school_id;

        $trip = Trip::where('school_id', $schoolId)
            ->findOrFail($id);

        $request->validate([
            'trip_date' => 'required|date',
            'start_time' => 'required',
            'driver_id' => 'required',
            'bus_id' => 'required',
            'school_route_id' => 'required',
        ]);

        $error = $this->checkConflicts($request, $id);

        if ($error) {
            return redirect()->back()->with('error', $error)->withInput();
        }

        $trip->update([
            'school_route_id' => $request->school_route_id,
            'bus_id' => $request->bus_id,
            'driver_id' => $request->driver_id,
            'trip_date' => $request->trip_date,
            'start_time' => $request->start_time,
            'status' => $request->status,
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

    private function checkConflicts($request, $ignoreTripId = null)
    {
        $queryDriver = Trip::where('trip_date', $request->trip_date)
            ->where('start_time', $request->start_time)
            ->where('driver_id', $request->driver_id);

        $queryBus = Trip::where('trip_date', $request->trip_date)
            ->where('start_time', $request->start_time)
            ->where('bus_id', $request->bus_id);

        if ($ignoreTripId) {
            $queryDriver->where('id', '!=', $ignoreTripId);
            $queryBus->where('id', '!=', $ignoreTripId);
        }

        if ($queryDriver->exists()) {
            return "Motorista já possui uma viagem nesse horário";
        }

        if ($queryBus->exists()) {
            return "Ônibus já está em uso nesse horário";
        }

        return null;
    }
}
