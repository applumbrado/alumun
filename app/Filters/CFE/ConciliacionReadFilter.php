<?php

namespace App\Filters\CFE;

use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

class ConciliacionReadFilter implements IReadFilter
{
    public function readCell($column, $row, $worksheetName = ''): bool
    {
        if ($row < 20) return false;

        return in_array($column, ['A','B','L','CC','CG','CH'], true);
    }
}
