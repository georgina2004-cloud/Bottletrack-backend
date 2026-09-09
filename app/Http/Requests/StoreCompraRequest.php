<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCompraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->tienePermiso('compras.crear');
    }

    public function rules(): array
    {
        return [
            'proveedor_id' => 'required|exists:proveedores,id',
            'numero_factura_proveedor' => 'nullable|string|max:50',
            'productos' => 'required|array|min:1',
            'productos.*.producto_id' => 'required|exists:productos,id',
            'productos.*.cantidad' => 'required|integer|min:1',
            'productos.*.precio_unitario' => 'required|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'proveedor_id.required' => 'Debes seleccionar un proveedor.',
            'productos.required' => 'La compra debe tener al menos un producto.',
        ];
    }
}