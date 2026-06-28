<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

/**
 * A simple, type-safe exporter: heading row plus pre-built data rows.
 * Building the rows in a service (rather than mapping models here) keeps the
 * branch/tenant scoping and formatting in one place and plays well with PHPStan.
 */
class ArrayExport implements FromArray, ShouldAutoSize, WithHeadings
{
    /**
     * @param  list<string>  $headings
     * @param  list<array<int, scalar|null>>  $rows
     */
    public function __construct(
        protected array $headings,
        protected array $rows,
    ) {}

    /** @return list<string> */
    public function headings(): array
    {
        return $this->headings;
    }

    /** @return list<array<int, scalar|null>> */
    public function array(): array
    {
        return $this->rows;
    }
}
