<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('productos', function (Blueprint $table) {
        $table->id();
        $table->string('codigo_barras', 50)->unique()->nullable();
        $table->string('nombre', 150);
        $table->string('marca', 100)->nullable();
        $table->foreignId('categoria_id')
              ->constrained('categorias')
              ->onDelete('restrict');
        $table->decimal('precio_compra', 10, 2);
        $table->decimal('precio_venta', 10, 2);
        $table->integer('stock_actual')->default(0);
        $table->integer('stock_minimo')->default(0);
        $table->integer('stock_maximo')->nullable();
        $table->decimal('presentacion_ml', 8, 2)->nullable();
        $table->string('ubicacion', 100)->nullable();
        $table->boolean('activo')->default(true);
        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};
