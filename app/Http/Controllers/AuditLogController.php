<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        if (!$request->user()->tienePermiso('auditoria.ver')) {
            return response()->json(['message' => 'No tienes permiso para realizar esta acción.'], 403);
        }

        $query = AuditLog::with('usuario')->orderByDesc('created_at');

        if ($request->filled('usuario_id')) $query->where('user_id', $request->input('usuario_id'));
        if ($request->filled('desde')) $query->whereDate('created_at', '>=', $request->input('desde'));
        if ($request->filled('hasta')) $query->whereDate('created_at', '<=', $request->input('hasta'));
        if ($request->filled('modulo')) $query->where('auditable_type', 'like', '%' . $request->input('modulo') . '%');

        return response()->json($query->paginate(20));
    }
}