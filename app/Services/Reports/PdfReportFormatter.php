<?php

namespace App\Services\Reports;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;

class PdfReportFormatter
{
    public function generar(string $titulo, array $columnas, Collection $datos, ?array $tamanoPagina = null)
    {
        $pdf = Pdf::loadView('reportes.generico', [
            'titulo' => $titulo,
            'columnas' => $columnas,
            'datos' => $datos,
        ]);

        if ($tamanoPagina) {
            $pdf->setPaper($tamanoPagina);
        }

        return $pdf->download($titulo . '.pdf');
    }
}