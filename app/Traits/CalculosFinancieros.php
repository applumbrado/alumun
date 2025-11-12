<?php

namespace App\Traits;

trait CalculosFinancieros
{
    public function calcularTotal(): float
    {
        return $this->importe - $this->descto + $this->recargo;
    }

}
