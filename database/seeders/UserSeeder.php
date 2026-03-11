<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $driver = User::create([
            'school_id' => 1,
            'name' => 'Motorista Teste',
            'email' => 'motorista@teste.com',
            'password' => Hash::make('123456')
        ]);

        $driver->assignRole('driver');

        $student = User::create([
            'school_id' => 1,
            'name' => 'Aluno Teste',
            'email' => 'aluno@teste.com',
            'password' => Hash::make('123456')
        ]);

        $student->assignRole('student');
    }
}
