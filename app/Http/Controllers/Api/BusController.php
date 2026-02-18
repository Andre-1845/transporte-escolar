<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BusResource;
use App\Models\Bus;

class BusController extends Controller
{
    public function index()
    {
        return BusResource::collection(
            Bus::all()
        );
    }
}
