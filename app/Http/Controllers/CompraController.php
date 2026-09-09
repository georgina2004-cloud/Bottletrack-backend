<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCompraRequest;
use App\Models\Compra;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CompraController extends Controller
{
    public function index(Request $request)
    {
        $query = Compra::with(['proveedor', 'usuario', 'detalles.producto'])->orderByDesc('fecha');

        if ($request->filled('desde')) {
            $query->whereDate('fecha', '>=', $request->input('desde'));
        }

        if ($request->filled('hasta')) {
            $query->whereDate('fecha', '<=', $request->input('hasta'));
        }

        return response()->json($query->paginate(15));
    }

    public function store(StoreCompraRequest $request)
    {
        $datosValidados = $request->validated();

        $compra = DB::transaction(function () use ($datosValidados, $request) {
            $total = 0;
            $lineasCalculadas = [];

            foreach ($datosValidados['productos'] as $item) {
                $subtotalLinea = $item['precio_unitario'] * $item['cantidad'];
                $total += $subtotalLinea;

                $lineasCalculadas[] = [
                    'producto_id' => $item['producto_id'],
                    'cantidad' => $item['cantidad'],
                    'precio_unitario' => $item['precio_unitario'],
                    'subtotal' => $subtotalLinea,
                ];
            }

            $compra = Compra::create([
                'proveedor_id' => $datosValidados['proveedor_id'],
                'user_id' => $request->user()->id,
                'fecha' => now()->toDateString(),
                'numero_factura_proveedor' => $datosValidados['numero_factura_proveedor'] ?? null,
                'total' => $total,
            ]);

            foreach ($lineasCalculadas as $linea) {
                $compra->detalles()->create($linea);
            }

            return $compra;
        });

        return response()->json([
            'message' => 'Compra registrada correctamente.',
            'compra' => $compra->load(['detalles.producto', 'proveedor', 'usuario']),
        ], 201);
    }

    public function show(Compra $compra)
    {
        return response()->json($compra->load(['detalles.producto', 'proveedor', 'usuario']));
    }
}
