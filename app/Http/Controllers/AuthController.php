<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Los datos proporcionados no son válidos.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'message' => 'Los datos proporcionados no son válidos.',
                'errors' => [
                    'email' => ['Estas credenciales no coinciden con nuestros registros.'],
                ],
            ], 422);
        }

        if (!$user->estado) {
            return response()->json([
                'message' => 'Tu cuenta está desactivada. Contacta al administrador.',
            ], 401);
        }

        $token = $user->createToken('bottletrack_token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->nombre,
            ],
        ], 200);
    }

    public function user(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role->nombre,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Sesión cerrada correctamente.',
        ]);
    }
}
