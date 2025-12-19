<?php

use App\Models\CFE\Periodo;
use Carbon\Carbon;

if (! function_exists('periodo_vigente')) {
    function periodo_vigente(): ?Periodo
    {
        return Periodo::vigente();
    }

}


 function parseCfeDate(?string $value): ?string{
    $value = trim((string)$value);

    if ($value === '') {
        return null;
    }

    // Esperamos formato "DD MMM YY", ej: "14 AGO 25"
    $parts = preg_split('/\s+/', $value);
    if (count($parts) !== 3) {
        return null;
    }

    [$day, $monTxt, $yy] = $parts;

    $meses = [
        'ENE' => 1,
        'FEB' => 2,
        'MAR' => 3,
        'ABR' => 4,
        'MAY' => 5,
        'JUN' => 6,
        'JUL' => 7,
        'AGO' => 8,
        'SEP' => 9,
        'OCT' => 10,
        'NOV' => 11,
        'DIC' => 12,
    ];

    $monTxt = mb_strtoupper($monTxt, 'UTF-8');

    if (!isset($meses[$monTxt])) {
        return null;
    }

    $day  = (int) $day;
    $year = (int) $yy;

    // Ajusta a tu realidad, aquí asumo 20xx
    $year = 2000 + $year;

    return sprintf('%04d-%02d-%02d', $year, $meses[$monTxt], $day);
}


function parseCfePeriodoFecha(?string $raw): ?string
{
    $raw = strtoupper(trim((string) $raw));
    if ($raw === '') return null;

    // Normaliza espacios: "04   SEP  25" => "04 SEP 25"
    $raw = preg_replace('/\s+/', ' ', $raw);

    // Formato esperado: "04 SEP 25" o "04 SEP 2025"
    if (!preg_match('/^(\d{1,2})\s+([A-ZÁÉÍÓÚÑ]{3,4})\s+(\d{2}|\d{4})$/u', $raw, $m)) {
        return null;
    }

    $day = (int) $m[1];
    $mon = $m[2];
    $yy  = $m[3];

    $months = [
        'ENE' => 1, 'FEB' => 2, 'MAR' => 3, 'ABR' => 4, 'MAY' => 5, 'JUN' => 6,
        'JUL' => 7, 'AGO' => 8, 'SEP' => 9, 'SET' => 9, 'OCT' => 10, 'NOV' => 11, 'DIC' => 12,
    ];

    if (!isset($months[$mon])) return null;

    $year = strlen($yy) === 2 ? (2000 + (int) $yy) : (int) $yy;
    $month = $months[$mon];

    return Carbon::create($year, $month, $day)->toDateString(); // "2025-09-04"
}
