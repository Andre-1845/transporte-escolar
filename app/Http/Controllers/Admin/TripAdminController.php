<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use Illuminate\Http\Request;

class TripAdminController extends Controller
{

    public function index()
    {
        $trips = Trip::orderBy('trip_date', 'desc')->get();

        return view('admin.trips.index', compact('trips'));
    }

    public function edit($id)
    {
        $trip = Trip::findOrFail($id);

        return view('admin.trips.edit', compact('trip'));
    }

    public function update(Request $request, $id)
    {
        $trip = Trip::findOrFail($id);

        $trip->update([
            'date' => $request->date,
            'status' => $request->status
        ]);

        return redirect('/admin/trips');
    }

    public function start($id)
    {
        $trip = Trip::findOrFail($id);

        $trip->update(['status' => 'running']);

        return back();
    }

    public function finish($id)
    {
        $trip = Trip::findOrFail($id);

        $trip->update(['status' => 'finished']);

        return back();
    }

    public function create()
    {
        return view('admin.trips.create');
    }

    public function store(Request $request)
    {
        Trip::create([
            'school_route_id' => $request->school_route_id,
            'bus_id' => $request->bus_id,
            'driver_id' => $request->driver_id,
            'date' => $request->date,
            'status' => 'scheduled'
        ]);

        return redirect('/admin/trips');
    }
}
