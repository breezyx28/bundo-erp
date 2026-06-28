<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * A no-op import whose only job is to make the reader key each row by its
 * heading. Reading is done with Excel::toArray(new RowsImport, $file); the
 * actual validation and persistence happen in ImportService.
 */
class RowsImport implements WithHeadingRow {}
