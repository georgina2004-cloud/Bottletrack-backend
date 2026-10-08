<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PerfilController extends Controller
{
    public function mostrar(Request $request)
    {
        return response()->json($request->user());
    }

    public function actualizar(Request $request)
    {
        $validado = $request->validate([
            'name' => 'required|string|max:150',
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($request->user()->id)],
            'password' => 'nullable|string|min:6',
        ]);

        if (empty($validado['password'])) unset($validado['password']);

        $request->user()->update($validado);

        return response()->json(['message' => 'Perfil actualizado correctamente.', 'user' => $request->user()]);
    }
}