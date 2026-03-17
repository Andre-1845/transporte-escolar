<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bus;
use Illuminate\Http\Request;

class BusAdminController extends Controller
{

    public function index()
    {
        $buses = Bus::orderBy('plate')->get();

        return view('admin.buses.index', compact('buses'));
    }

    public function create()
    {
        return view('admin.buses.create');
    }

    public function store(Request $request)
    {

        Bus::create([
            'school_id' => auth()->user()->school_id,
            'plate' => $request->plate,
            'model' => $request->model,
            'capacity' => $request->capacity,
            'active' => $request->active ?? 1
        ]);

        return redirect()->route('admin.buses.index');
    }

    public function edit($id)
    {
        $bus = Bus::findOrFail($id);

        return view('admin.buses.edit', compact('bus'));
    }

    public function update(Request $request, $id)
    {

        $bus = Bus::findOrFail($id);

        $bus->update([
            'plate' => $request->plate,
            'model' => $request->model,
            'capacity' => $request->capacity,
            'active' => $request->active ?? 0
        ]);

        return redirect()->route('admin.buses.index');
    }

    public function destroy($id)
    {
        $bus = Bus::findOrFail($id);

        $bus->delete();

        return redirect()->route('admin.buses.index');
    }
}