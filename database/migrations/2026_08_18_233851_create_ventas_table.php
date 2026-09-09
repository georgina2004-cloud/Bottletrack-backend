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
        Schema::create('ventas', function (Blueprint $table) {
        $table->id();
        $table->string('numero_factura', 50)->unique();
        $table->foreignId('user_id')
              ->constrained('users')
              ->onDelete('restrict');
        $table->string('cliente_nombre', 150)->nullable();
        $table->date('fecha');
        $table->decimal('subtotal', 12, 2);
        $table->decimal('impuesto', 12, 2)->default(0);
        $table->decimal('descuento', 12, 2)->default(0);
        $table->decimal('total', 12, 2);
        $table->boolean('estado_activa')->default(true);
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ventas');
    }
};
