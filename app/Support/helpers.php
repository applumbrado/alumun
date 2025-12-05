<?php

use App\Models\CFE\Periodo;

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
