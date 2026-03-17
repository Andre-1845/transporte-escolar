<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Trip;
use App\Models\User;
use App\Models\Bus;

class DashboardController extends Controller
{
    public function index()
    {

        $trips = Trip::count();
        $users = User::count();
        $buses = Bus::count();

        return view('admin.dashboard', compact(
            'trips',
            'users',
            'buses'
        ));
    }
}