<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUsuarioRequest;
use App\Http\Requests\UpdateUsuarioRequest;
use App\Models\User;
use Illuminate\Http\Request;

class UsuarioController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with('role');

        if ($request->filled('busqueda')) {
            $texto = $request->input('busqueda');
            $query->where(function ($q) use ($texto) {
                $q->where('name', 'like', "%{$texto}%")
                  ->orWhere('email', 'like', "%{$texto}%");
            });
        }

        return response()->json($query->orderBy('name')->paginate(15));
    }

    public function store(StoreUsuarioRequest $request)
    {
        $datos = $request->validated();

        $usuario = User::create($datos);

        return response()->json([
            'message' => 'Usuario creado correctamente.',
            'usuario' => $usuario->load('role'),
        ], 201);
    }

    public function show(User $usuario)
    {
        return response()->json($usuario->load('role'));
    }

    public function update(UpdateUsuarioRequest $request, User $usuario)
    {
        $datos = $request->validated();

        if (empty($datos['password'])) {
            unset($datos['password']);
        }

        $usuario->update($datos);

        return response()->json([
            'message' => 'Usuario actualizado correctamente.',
            'usuario' => $usuario->load('role'),
        ]);
    }

    public function destroy(Request $request, User $usuario)
{
    if ($request->user()->id === $usuario->id) {
        return response()->json([
            'message' => 'No puedes eliminar tu propia cuenta.',
        ], 409);
    }

    try {
        $usuario->delete();
    } catch (\Illuminate\Database\QueryException $e) {
        return response()->json([
            'message' => 'No se puede eliminar: este usuario tiene ventas o compras registradas. Puedes desactivarlo en su lugar desde la pantalla de edición.',
        ], 409);
    }

    return response()->json(['message' => 'Usuario eliminado permanentemente.']);
}
}
