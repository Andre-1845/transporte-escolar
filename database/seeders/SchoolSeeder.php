<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SchoolSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('schools')->insert([
            'id' => 1,
            'name' => 'Escola Transporte Teste',
            'slug' => 'escola-teste',
            'contact_email' => 'contato@escola.com',
            'contact_phone' => '11999999999',
            'active' => true,
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }
}
