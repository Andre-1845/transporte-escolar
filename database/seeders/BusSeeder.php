<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BusSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('buses')->insert([
            'school_id' => 1,
            'plate' => 'ABC1D23',
            'model' => 'Mercedes Sprinter',
            'capacity' => 20,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }
}
