<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SchoolRouteSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('school_routes')->insert([
            'school_id' => 1,
            'name' => 'Rota Centro',
            'description' => 'Rota principal do centro da cidade',
            'active' => true,
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }
}
