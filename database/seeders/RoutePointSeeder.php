<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoutePointSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('route_points')->insert([
            [
                'school_id' => 1,
                'school_route_id' => 1,
                'name' => 'Ponto 1 - Centro',
                'latitude' => -22.4705000,
                'longitude' => -44.4500000,
                'point_order' => 1,
                'estimated_time' => '07:00:00',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'school_id' => 1,
                'school_route_id' => 1,
                'name' => 'Ponto 2 - Bairro',
                'latitude' => -22.4680000,
                'longitude' => -44.4470000,
                'order' => 2,
                'estimated_time' => '07:10:00',
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);
    }
}