<?php

namespace App\Http\Requests;

use App\Models\PresentacionProducto;
use App\Models\Producto;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreVentaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->tienePermiso('ventas.crear');
    }

    protected function prepareForValidation(): void
    {
        // Si el descuento viene vacío o null, se normaliza a 0
        if ($this->descuento === '' || $this->descuento === null) {
            $this->merge([
                'descuento' => 0,
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'cliente_nombre'              => ['nullable', 'string', 'max:150'],
            'descuento'                   => ['nullable', 'numeric', 'min:0', 'decimal:0,2'],
            'productos'                   => ['required', 'array', 'min:1'],
            'productos.*.producto_id'     => ['required', 'integer', 'exists:productos,id'],
            'productos.*.presentacion_id' => ['required', 'integer', 'exists:presentaciones_producto,id'],
            'productos.*.cantidad'        => ['required', 'integer', 'min:1', 'max:99999'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $descuento = (float) $this->input('descuento', 0);

            if ($descuento <= 0 || !$this->has('productos') || !is_array($this->input('productos'))) {
                return;
            }

            // Calcular el subtotal real basado en los datos de la base de datos o el payload
            $subtotal = 0;
            foreach ($this->input('productos') as $item) {
                $cantidad = (int) ($item['cantidad'] ?? 0);
                if ($cantidad <= 0) {
                    continue;
                }

                $precioItem = 0;

                // 1. Intentar obtener el precio desde PresentacionProducto
                if (!empty($item['presentacion_id'])) {
                    $presentacion = PresentacionProducto::with('producto')->find($item['presentacion_id']);
                    if ($presentacion) {
                        $precioItem = $presentacion->precio ?? $presentacion->precio_venta;

                        // Si la presentación no define precio propio, tomar el del producto vinculado
                        if (!$precioItem && $presentacion->producto) {
                            $precioItem = $presentacion->producto->precio_venta ?? $presentacion->producto->precio;
                        }
                    }
                }

                // 2. Fallback: buscar directamente en el modelo Producto
                if (!$precioItem && !empty($item['producto_id'])) {
                    $producto = Producto::find($item['producto_id']);
                    if ($producto) {
                        $precioItem = $producto->precio_venta ?? $producto->precio ?? 0;
                    }
                }

                // 3. Fallback: precio enviado directamente en el payload
                if (!$precioItem && !empty($item['precio_unitario'])) {
                    $precioItem = (float) $item['precio_unitario'];
                }

                $subtotal += ((float) $precioItem) * $cantidad;
            }

            if ($descuento > $subtotal) {
                $validator->errors()->add(
                    'descuento',
                    'El descuento no puede ser mayor al subtotal de la venta (C$ ' . number_format($subtotal, 2) . ').'
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'productos.required'              => 'La venta debe tener al menos un producto.',
            'productos.min'                   => 'La venta debe tener al menos un producto.',
            'productos.*.cantidad.required'   => 'La cantidad es obligatoria.',
            'productos.*.cantidad.integer'    => 'La cantidad debe ser un número entero.',
            'productos.*.cantidad.min'        => 'La cantidad debe ser mayor a cero.',
            'productos.*.cantidad.max'        => 'La cantidad excede el límite permitido.',
            'productos.*.producto_id.exists'  => 'El producto seleccionado no es válido.',
            'productos.*.presentacion_id.exists' => 'La presentación seleccionada no es válida.',
            'descuento.numeric'               => 'El descuento debe ser un valor numérico.',
            'descuento.min'                   => 'El descuento no puede ser negativo.',
            'descuento.decimal'               => 'El descuento admite máximo 2 decimales.',
        ];
    }
}