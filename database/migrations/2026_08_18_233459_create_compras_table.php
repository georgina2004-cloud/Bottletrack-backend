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
        Schema::create('compras', function (Blueprint $table) {
        $table->id();
        $table->foreignId('proveedor_id')
              ->constrained('proveedores')
              ->onDelete('restrict');
        $table->foreignId('user_id')
              ->constrained('users')
              ->onDelete('restrict');
        $table->date('fecha');
        $table->string('numero_factura_proveedor', 50)->nullable();
        $table->decimal('total', 12, 2)->default(0);
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('compras');
    }
};
