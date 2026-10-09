<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductoRequest;
use App\Http\Requests\UpdateProductoRequest;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProductoController extends Controller
{
    public function index(Request $request)
    {
        $query = Producto::with('categoria')->where('activo', true);

        if ($request->filled('busqueda')) {
            $texto = $request->input('busqueda');
            $query->where(function ($q) use ($texto) {
                $q->where('nombre', 'like', "%{$texto}%")
                  ->orWhere('codigo_barras', 'like', "%{$texto}%");
            });
        }

        if ($request->filled('categoria_id')) {
            $query->where('categoria_id', $request->input('categoria_id'));
        }

        $productos = $query->orderBy('nombre')->paginate(15);

        return response()->json($productos);
    }

    public function show(Producto $producto)
    {
        return response()->json($producto->load('categoria'));
    }

    public function store(StoreProductoRequest $request)
    {
        $datos = $request->validated();
        unset($datos['imagen']);

        if ($request->hasFile('imagen')) {
            $datos['imagen_path'] = $request->file('imagen')->store('productos', 'public');
        }

        $producto = Producto::create($datos);

        return response()->json([
            'message' => 'Producto creado correctamente.',
            'producto' => $producto->load('categoria'),
        ], 201);
    }

    public function storeBulk(Request $request)
    {
        if (!$request->user()->tienePermiso('productos.crear')) {
            return response()->json([
                'message' => 'No tienes permiso para realizar esta acción.',
            ], 403);
        }

        $request->validate([
            'productos' => 'required|array|min:1',
        ]);

        $creados = [];
        $errores = [];

        foreach ($request->input('productos') as $indice => $item) {
            $validador = validator($item, [
                'codigo_barras' => 'nullable|string|max:50|unique:productos,codigo_barras',
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
            ], [
                'nombre.required' => 'El nombre del producto es obligatorio.',
                'categoria_id.required' => 'Debes seleccionar una categoría.',
                'categoria_id.exists' => 'La categoría seleccionada no existe.',
                'precio_compra.required' => 'El precio de compra es obligatorio.',
                'precio_venta.required' => 'El precio de venta es obligatorio.',
                'codigo_barras.unique' => 'Ya existe un producto con ese código de barras.',
            ]);

            if ($validador->fails()) {
                $errores[] = [
                    'fila' => $indice,
                    'nombre' => $item['nombre'] ?? '(sin nombre)',
                    'errores' => $validador->errors()->all(),
                ];
                continue;
            }

            $producto = Producto::create($validador->validated());
            $creados[] = $producto->load('categoria');
        }

        return response()->json([
            'message' => count($creados) . ' de ' . count($request->input('productos')) . ' productos creados correctamente.',
            'creados' => $creados,
            'errores' => $errores,
        ], 201);
    }

    public function update(UpdateProductoRequest $request, Producto $producto)
    {
        $datos = $request->validated();
        unset($datos['imagen']);

        if ($request->hasFile('imagen')) {
            if ($producto->imagen_path) {
                Storage::disk('public')->delete($producto->imagen_path);
            }
            $datos['imagen_path'] = $request->file('imagen')->store('productos', 'public');
        }

        $producto->update($datos);

        return response()->json([
            'message' => 'Producto actualizado correctamente.',
            'producto' => $producto->load('categoria'),
        ]);
    }

    public function destroy(Request $request, Producto $producto)
    {
        if (!$request->user()->tienePermiso('productos.eliminar')) {
            return response()->json([
                'message' => 'No tienes permiso para realizar esta acción.',
            ], 403);
        }

        $producto->update(['activo' => false]);

        return response()->json([
            'message' => 'Producto desactivado correctamente.',
        ]);
    }

    public function buscarPorCodigo(string $codigo)
    {
        $producto = Producto::with('categoria')
            ->where('codigo_barras', $codigo)
            ->where('activo', true)
            ->first();

        if (!$producto) {
            return response()->json([
                'existe' => false,
            ], 404);
        }

        return response()->json([
            'existe' => true,
            'producto' => $producto,
        ]);
    }


    public function alertasVencimiento(Request $request)
    {
    $dias = (int) $request->input('dias', 30);
    $hoy = now()->toDateString();
    $limite = now()->addDays($dias)->toDateString();

    $vencidos = Producto::where('activo', true)
        ->whereNotNull('fecha_vencimiento')
        ->whereDate('fecha_vencimiento', '<', $hoy)
        ->get(['id', 'nombre', 'fecha_vencimiento']);

    $porVencer = Producto::where('activo', true)
        ->whereNotNull('fecha_vencimiento')
        ->whereDate('fecha_vencimiento', '>=', $hoy)
        ->whereDate('fecha_vencimiento', '<=', $limite)
        ->get(['id', 'nombre', 'fecha_vencimiento']);

    return response()->json(['vencidos' => $vencidos, 'por_vencer' => $porVencer]);
    }

        public function lookupBarcode(Request $request)
    {
        if (!$request->user()->tienePermiso('productos.crear')) {
            return response()->json(['message' => 'No tienes permiso para realizar esta acción.'], 403);
        }

        $codigo = trim((string) $request->query('barcode', ''));

        if ($codigo === '' || mb_strlen($codigo) > 50) {
            return response()->json(['message' => 'Código de barras inválido.'], 422);
        }

        $local = Producto::where('codigo_barras', $codigo)
            ->where('activo', true)
            ->first(['id', 'nombre']);

        if ($local) {
            return response()->json([
                'existe_local' => true,
                'producto' => $local,
                'found' => false,
                'nombre' => '',
                'marca' => '',
                'categoria_sugerida' => '',
            ]);
        }

        return response()->json(['existe_local' => false] + $this->consultarOpenFoodFacts($codigo));
    }

    private function consultarOpenFoodFacts(string $codigo): array
    {
        $vacio = ['found' => false, 'nombre' => '', 'marca' => '', 'categoria_sugerida' => ''];

        // Open Food Facts solo maneja EAN/UPC numéricos
        if (!ctype_digit($codigo) || strlen($codigo) < 8 || strlen($codigo) > 14) {
            return $vacio;
        }

        $cacheKey = "off_barcode_{$codigo}";

        if ($cacheado = Cache::get($cacheKey)) {
            return $cacheado;
        }

        try {
            $respuesta = Http::timeout(5)
                ->withHeaders(['User-Agent' => 'BottleTrack/1.0 (proyecto academico)'])
                ->get("https://world.openfoodfacts.org/api/v2/product/{$codigo}.json", [
                    'fields' => 'product_name,brands,categories_tags',
                ]);
        } catch (\Throwable $e) {
            Log::warning('Open Food Facts: fallo de conexión', ['barcode' => $codigo, 'error' => $e->getMessage()]);
            return $vacio;
        }

        if (!$respuesta->successful() || (int) $respuesta->json('status') !== 1) {
            return $vacio;
        }

        $producto = $respuesta->json('product', []);
        $nombre = $this->limpiarTexto((string) ($producto['product_name'] ?? ''));

        if ($nombre === '') {
            return $vacio;
        }

        $marcas = explode(',', (string) ($producto['brands'] ?? ''));

        $resultado = [
            'found' => true,
            'nombre' => $nombre,
            'marca' => $this->limpiarTexto($marcas[0] ?? ''),
            'categoria_sugerida' => $this->mapearCategoria($producto['categories_tags'] ?? []),
        ];

        Cache::put($cacheKey, $resultado, now()->addDay());

        return $resultado;
    }

    private function limpiarTexto(string $texto): string
    {
        return mb_substr(trim(strip_tags($texto)), 0, 150);
    }

    private function mapearCategoria(array $tags): string
    {
        $reglas = [
            'Ron' => '/\b(rums?|rhums?)\b/',
            'Whisky' => '/\b(whiskey|whisky|whiskies|bourbons?)\b/',
            'Vodka' => '/\bvodkas?\b/',
            'Tequila' => '/\b(tequilas?|mezcals?)\b/',
            'Ginebra' => '/\bgins?\b/',
            'Brandy' => '/\b(brandy|brandies|cognacs?)\b/',
            'Licor' => '/\bliqueurs?\b/',
            'Cerveza' => '/\b(beers?|lagers?)\b/',
            'Vino' => '/\b(wines?|champagnes?)\b/',
        ];

        $texto = strtolower(implode(' ', $tags));

        foreach ($reglas as $categoria => $regex) {
            if (preg_match($regex, $texto)) {
                return $categoria;
            }
        }

        return '';
    }
}