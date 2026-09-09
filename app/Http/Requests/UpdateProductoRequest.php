<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role->nombre === 'Gerente de Bodega';
    }

    public function rules(): array
    {
        $productoId = $this->route('producto')->id;

        return [
        'codigo_barras' => 'nullable|string|max:50|unique:productos,codigo_barras,' . $productoId,
        'nombre' => 'required|string|max:150',
        'marca' => 'nullable|string|max:100',
        'categoria_id' => 'required|exists:categorias,id',
        'precio_compra' => 'required|numeric|min:0',
        'precio_venta' => 'required|numeric|min:0',
        'stock_actual' => 'nullable|integer|min:0',
        'stock_minimo' => 'nullable|integer|min:0',
        'stock_maximo' => 'nullable|integer|min:0',
        'presentacion_ml' => 'nullable|numeric|min:0',
        'ubicacion' => 'nullable|string|max:100',
        'activo' => 'nullable|boolean',
        'imagen' => 'nullable|image|max:2048',
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre del producto es obligatorio.',
            'categoria_id.required' => 'Debes seleccionar una categoría.',
            'categoria_id.exists' => 'La categoría seleccionada no existe.',
            'precio_compra.required' => 'El precio de compra es obligatorio.',
            'precio_venta.required' => 'El precio de venta es obligatorio.',
            'codigo_barras.unique' => 'Ya existe otro producto con ese código de barras.',
        ];
    }
}
