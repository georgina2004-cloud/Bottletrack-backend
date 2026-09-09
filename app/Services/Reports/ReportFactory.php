<?php

namespace App\Services\Reports;

use Illuminate\Support\Collection;

class ReportFactory
{
    public static function exportar(string $formato, string $titulo, array $columnas, Collection $datos, ?array $tamanoPagina = null)
    {
        return match ($formato) {
            'pdf' => (new PdfReportFormatter())->generar($titulo, $columnas, $datos, $tamanoPagina),
            'excel' => (new ExcelReportFormatter())->generar($titulo, $columnas, $datos),
            default => throw new \InvalidArgumentException("Formato no soportado: {$formato}"),
        };
    }
}