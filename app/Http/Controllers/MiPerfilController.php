<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MiPerfilController extends Controller
{
    public function permisos(Request $request)
    {
        $claves = $request->user()->role->permisos()->pluck('clave');

        return response()->json($claves);
    }
}
