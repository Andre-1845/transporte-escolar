<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SchoolRoute;

class RouteAdminController extends Controller
{

    public function index()
    {
        $routes = SchoolRoute::orderBy('name')->get();

        return view('admin.routes.index', compact('routes'));
    }

    public function create()
    {
        return view('admin.routes.create');
    }

    public function store(Request $request)
    {

        $request->validate([
            'name' => 'required'
        ]);

        SchoolRoute::create([
            'school_id' => auth()->user()->school_id,
            'name' => $request->name,
            'description' => $request->description,
            'active' => 1
        ]);

        return redirect()->route('admin.routes.index');
    }

    public function edit($id)
    {
        $route = SchoolRoute::findOrFail($id);

        return view('admin.routes.edit', compact('route'));
    }

    public function update(Request $request, $id)
    {

        $route = SchoolRoute::findOrFail($id);

        $route->update([
            'name' => $request->name,
            'description' => $request->description,
            'active' => $request->active ?? 0
        ]);

        return redirect()->route('admin.routes.index');
    }

    public function destroy($id)
    {
        $route = SchoolRoute::findOrFail($id);

        $route->delete();

        return redirect()->route('admin.routes.index');
    }
}