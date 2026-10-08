<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('compras', function (Blueprint $table) {
            $table->boolean('estado_activa')->default(true)->after('total');
        });
    }

    public function down(): void
    {
        Schema::table('compras', function (Blueprint $table) {
            $table->dropColumn('estado_activa');
        });
    }
};