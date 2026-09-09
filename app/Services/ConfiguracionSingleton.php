<?php

namespace App\Services;

use App\Models\ConfiguracionEmpresa;

class ConfiguracionSingleton
{
    private static ?ConfiguracionSingleton $instancia = null;

    private ?ConfiguracionEmpresa $configuracion = null;

    private function __construct() {}

    public static function obtenerInstancia(): self
    {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }

        return self::$instancia;
    }

    public function obtenerConfiguracion(): ?ConfiguracionEmpresa
    {
        if ($this->configuracion === null) {
            $this->configuracion = ConfiguracionEmpresa::first();
        }

        return $this->configuracion;
    }

    public function refrescar(): void
    {
        $this->configuracion = ConfiguracionEmpresa::first();
    }
}