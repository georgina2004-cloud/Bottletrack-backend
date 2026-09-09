<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreVentaRequest extends FormRequest
{
    public function authorize(): bool
    {
        $rol = $this->user()->role->nombre;
        return in_array($rol, ['Gerente de Bodega', 'Encargado de Ventas']);
    }

    public function rules(): array
    {
    return [
        'cliente_nombre' => 'nullable|string|max:150',
        'descuento' => 'nullable|numeric|min:0',
        'productos' => 'required|array|min:1',
        'productos.*.producto_id' => 'required|exists:productos,id',
        'productos.*.presentacion_id' => 'required|exists:presentaciones_producto,id',
        'productos.*.cantidad' => 'required|integer|min:1',
    ];
    }

    public function messages(): array
    {
        return [
            'productos.required' => 'La venta debe tener al menos un producto.',
            'productos.*.cantidad.min' => 'La cantidad debe ser mayor a cero.',
        ];
    }
}
