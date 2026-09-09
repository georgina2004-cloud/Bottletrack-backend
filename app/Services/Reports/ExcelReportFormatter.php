<?php

namespace App\Services\Reports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Contracts\Support\Responsable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ExcelReportFormatter
{
    public function generar(string $titulo, array $columnas, Collection $datos)
    {
        $export = new class($columnas, $datos) implements FromCollection, WithHeadings {
            public function __construct(private array $columnas, private Collection $datos) {}

            public function collection(): Collection
            {
                return $this->datos;
            }

            public function headings(): array
            {
                return $this->columnas;
            }
        };

        return Excel::download($export, $titulo . '.xlsx');
    }
}