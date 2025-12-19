<?php


/**
 * Busca en un nodo (ej. $cls->Importes) un campo con sufijo numérico descendente:
 *   Importe10..Importe1, Cargo10..Cargo1, etc.
 *
 * @param  SimpleXMLElement|null  $parent   Nodo padre (ej. $cls->Importes)
 * @param  string                 $campo    Prefijo del campo (ej. 'Importe')
 * @param  int                    $nMax     Máximo N (ej. 10)
 * @param  callable|null          $filter   fn(float $value, int $n): bool
 * @param  float                  $default  Valor por defecto si no encuentra
 * @return float
 */
if (!function_exists('xml_num_field_find')) {
    function xml_num_field_find($parent, string $campo, int $nMax = 10, ?callable $filter = null, float $default = 0.0): float
    {
        if (!$parent) return $default;

        for ($n = $nMax; $n >= 1; $n--) {
            $prop = $campo . $n;

            if (isset($parent->{$prop})) {
                $val = (float) $parent->{$prop};

                if ($filter === null || $filter($val, $n) === true) {
                    return $val;
                }
            }
        }

        return $default;
    }
}
