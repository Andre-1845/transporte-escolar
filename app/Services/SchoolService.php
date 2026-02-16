<?php

namespace App\Services;

use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class SchoolService
{
    public function createSchoolWithAdmin(array $data): School
    {
        return DB::transaction(function () use ($data) {

            $school = School::create([
                'name' => $data['name'],
                'slug' => $data['slug'],
                'contact_email' => $data['contact_email'] ?? null,
                'contact_phone' => $data['contact_phone'] ?? null,
            ]);

            $admin = User::create([
                'name' => $data['admin_name'],
                'email' => $data['admin_email'],
                'password' => Hash::make($data['admin_password']),
                'school_id' => $school->id,
            ]);

            $role = Role::where('name', 'school_admin')->first();
            $admin->assignRole($role);

            return $school;
        });
    }
}
