<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\School;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (!Auth::attempt($credentials)) {
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        $user = Auth::user();

        if ($user->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Conta aguardando aprovação'
            ], 403);
        }


        $token = $user->createToken('mobile-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'school_id' => $user->school_id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->getRoleNames(),
            ]
        ]);
    }

    public function validateSchoolCode($code)
    {
        $school = School::where('access_code', $code)->first();

        if (!$school) {
            return response()->json([
                'success' => false,
                'message' => 'Código inválido'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'school_id' => $school->id,
            'school_name' => $school->name,
            'auto_approve' => $school->auto_approve_students
        ]);
    }

    public function registerStudent(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
            'code' => 'required'
        ]);

        $school = School::where('access_code', $request->code)->first();

        if (!$school) {
            return response()->json([
                'success' => false,
                'message' => 'Código inválido'
            ], 400);
        }

        $status = $school->auto_approve_students ? 'active' : 'pending';

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'school_id' => $school->id,
            'status' => $status
        ]);

        return response()->json([
            'success' => true,
            'status' => $status,
            'message' => $status === 'active'
                ? 'Cadastro realizado com sucesso'
                : 'Cadastro realizado. Aguardando aprovação'
        ]);
    }
}