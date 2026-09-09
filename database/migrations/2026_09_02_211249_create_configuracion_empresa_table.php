<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up(): void
    {
    Schema::create('configuracion_empresa', function (Blueprint $table) {
        $table->id();
        $table->string('nombre_licoreria');
        $table->string('eslogan')->nullable();
        $table->string('telefono')->nullable();
        $table->string('email')->nullable();
        $table->string('direccion')->nullable();
        $table->string('moneda', 10)->default('C$');
        $table->string('color_primario', 7)->default('#D17B00');
        $table->string('logo_path')->nullable();
        $table->timestamps();
    });
    }

    
    public function down(): void
    {
        Schema::dropIfExists('configuracion_empresa');
    }
};
