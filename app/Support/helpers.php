<?php

use App\Models\CFE\Periodo;

if (! function_exists('periodo_vigente')) {
    function periodo_vigente(): ?Periodo
    {
        return Periodo::vigente();
    }
}
