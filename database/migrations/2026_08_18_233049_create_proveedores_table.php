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
        Schema::create('proveedores', function (Blueprint $table) {
        $table->id();
        $table->string('ruc', 20)->unique();
        $table->string('razon_social', 150);
        $table->string('telefono', 20)->nullable();
        $table->string('email', 150)->nullable();
        $table->string('direccion')->nullable();
        $table->boolean('activo')->default(true);
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proveedores');
    }
};
