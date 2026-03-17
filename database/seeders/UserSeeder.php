<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::create([
            'school_id' => 1,
            'name' => 'Admin',
            'email' => 'admin@teste',
            'password' => Hash::make('123456')
        ]);

        $admin->assignRole('admin');

        $driver = User::create([
            'school_id' => 1,
            'name' => 'Motorista Teste',
            'email' => 'motorista@teste',
            'password' => Hash::make('123456')
        ]);

        $driver->assignRole('driver');

        $student = User::create([
            'school_id' => 1,
            'name' => 'Aluno Teste',
            'email' => 'aluno@teste',
            'password' => Hash::make('123456')
        ]);

        $student->assignRole('student');
    }
}