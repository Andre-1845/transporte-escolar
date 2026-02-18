<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SchoolRouteResource;
use App\Models\SchoolRoute;

class SchoolRouteController extends Controller
{
    public function index()
    {
        return SchoolRouteResource::collection(
            SchoolRoute::all()
        );
    }

    public function show($id)
    {
        $route = SchoolRoute::with('points')->findOrFail($id);

        return new SchoolRouteResource($route);
    }
}
