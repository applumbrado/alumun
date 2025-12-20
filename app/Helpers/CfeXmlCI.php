<?php

namespace App\Helpers;

use SimpleXMLElement;

class CfeXmlCI
{
    private static function cleanText($value): string
    {
        $s = trim((string) $value);
        if ($s === '') return $s;

        // Decodificar entidades XML/HTML (por si acaso)
        $s = html_entity_decode($s, ENT_QUOTES | ENT_XML1, 'UTF-8');

        // Garantizar UTF-8 si por alguna razón llega mal
        if (!mb_check_encoding($s, 'UTF-8')) {
            $s = mb_convert_encoding($s, 'UTF-8', 'UTF-8, Windows-1252, ISO-8859-1');
        }

        // Quitar/controlar caracteres invisibles raros
        $s = preg_replace('/\p{C}+/u', '', $s);

        $s = strtr($s, [
            'Energ?a' => 'Energía',
            'Facturaci?n del Periodo' => 'Facturación del Periodo',
            '2% Baja Tensi?n' => '2% Baja Tensión',
            'Bonificaci?n Factor de Potencia' => 'Bonificación Factor de Potencia',
        ]);

        $s = preg_replace('/\?{2,}$/', '', $s); // elimina ??? al final

        return $s;
    }

    private static function cleanNumber($value): ?float
    {
        $s = self::cleanText($value);
        if ($s === '') return null;

        // Quitar moneda/espacios y normalizar separadores
        // Ej: "$ 1,234.56" -> "1234.56"
        $s = preg_replace('/[^\d,.\-]/', '', $s);

        // Si trae comas como separador de miles, las removemos
        // (y dejamos el punto como decimal)
        if (substr_count($s, ',') > 0 && substr_count($s, '.') > 0) {
            $s = str_replace(',', '', $s);
        } else {
            // Si solo trae coma, asumimos coma decimal -> punto
            if (substr_count($s, ',') > 0 && substr_count($s, '.') === 0) {
                $s = str_replace(',', '.', $s);
            }
        }

        if ($s === '' || $s === '-' || $s === '.') return null;

        return is_numeric($s) ? (float) $s : null;
    }

    /**
     * Extrae una "serie" TipoN (ConceptoN, ImporteN, etc.) dentro de un nodo padre.
     *
     * @param string $xpathParent Ej: '//Conceptos' o '//Importes'
     * @param string $prefix      Ej: 'Concepto' o 'Importe'
     * @param callable|null $transform Transformación por valor (ej cleanText/cleanNumber)
     * @return array<int, mixed> Mapa idx=>valor ordenado
     */
    private static function extraerSerieN(SimpleXMLElement $xml, string $xpathParent, string $prefix, ?callable $transform = null): array
    {
        $nodes = $xml->xpath($xpathParent);
        if (!$nodes || !isset($nodes[0])) return [];

        $out = [];
        foreach ($nodes[0]->children() as $tag => $value) {
            if (preg_match('/^' . preg_quote($prefix, '/') . '(\d+)$/', $tag, $m)) {
                $idx = (int) $m[1];
                $out[$idx] = $transform ? $transform($value) : self::cleanText($value);
            }
        }

        ksort($out);
        // quitar vacíos / nulls
        return array_filter($out, fn($v) => $v !== '' && $v !== null);
    }

    // =========================
    // CONCEPTOS
    // =========================

    /** ["Cargo Fijo", "Energía", ...] */
    public static function conceptos(SimpleXMLElement $xml): array
    {
        $map = self::extraerSerieN($xml, '//Conceptos', 'Concepto', [self::class, 'cleanText']);
        return array_values($map);
    }

    /** [1=>"Cargo Fijo", 2=>"Energía", ...] */
    public static function conceptosMap(SimpleXMLElement $xml): array
    {
        return self::extraerSerieN($xml, '//Conceptos', 'Concepto', [self::class, 'cleanText']);
    }

    // =========================
    // IMPORTES
    // =========================

    /** [123.45, 567.89, ...] */
    public static function importes(SimpleXMLElement $xml): array
    {
        $map = self::extraerSerieN($xml, '//Importes', 'Importe', [self::class, 'cleanNumber']);
        return array_values($map);
    }

    /** [1=>123.45, 2=>567.89, ...] */
    public static function importesMap(SimpleXMLElement $xml): array
    {
        return self::extraerSerieN($xml, '//Importes', 'Importe', [self::class, 'cleanNumber']);
    }

    /**
     * Por si quieres TODO junto en una sola llamada:
     * ['conceptos'=>..., 'importes'=>...]
     */
    public static function conceptosEImportes(SimpleXMLElement $xml): array
    {
        return [
            'conceptos' => self::conceptosMap($xml),
            'importes'  => self::importesMap($xml),
        ];
    }

    public static function conceptosConImportes(SimpleXMLElement $xml): array
    {
        $conceptos = self::conceptosMap($xml); // [1=>"Cargo Fijo", 2=>"Energía", ...]
        $importes  = self::importesMap($xml);  // [1=>123.45, 2=>999.00, ...]

        $out = [];

        // une por índice
        $indices = array_unique(array_merge(array_keys($conceptos), array_keys($importes)));
        sort($indices);

        foreach ($indices as $i) {
            $concepto = $conceptos[$i] ?? null;
            $importe  = $importes[$i]  ?? null;

            // si falta concepto o importe, lo puedes saltar o guardar null
            if ($concepto === null) continue;

            // ✅ clave “bonita” (slug) o “concepto{i}”
            // Opción A: "concepto1", "concepto2"...
            $key = "concepto{$i}";

            // Opción B (recomendada): clave basada en texto (limpia)
            // $key = self::slugKey($concepto);

            $out[$key] = $importe;
        }

        return $out;
    }



}
