<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TripSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('trips')->insert([
            'school_id' => 1,
            'bus_id' => 1,
            'school_route_id' => 1,
            'trip_date' => now()->toDateString(),
            'status' => 'scheduled',
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }
}
