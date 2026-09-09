<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
    Schema::create('presentaciones_producto', function (Blueprint $table) {
        $table->id();
        $table->foreignId('producto_id')
              ->constrained('productos')
              ->onDelete('cascade');
        $table->string('nombre', 50);
        $table->integer('unidades_equivalentes');
        $table->decimal('precio_venta', 10, 2);
        $table->boolean('es_default')->default(false);
        $table->timestamps();
    });
    }

    
    public function down(): void
    {
        Schema::dropIfExists('presentaciones_producto');
    }
};
