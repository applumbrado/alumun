<?php

namespace App\Models\CFE;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Str;

class Concepto extends Model
{
    use SoftDeletes;

    protected $table = 'conceptos';

    protected $fillable = [
        'id',
        'concepto1','importe1',
        'concepto2','importe2',
        'concepto3','importe3',
        'concepto4','importe4',
        'concepto5','importe5',
        'concepto6','importe6',
        'concepto7','importe7',
        'concepto8','importe8',
        'concepto9','importe9',
        'concepto10','importe10',
        'recibo_id',
        'periodo_id',
    ];

    protected $casts = [
        'importe1'  => 'float',
        'importe2'  => 'float',
        'importe3'  => 'float',
        'importe4'  => 'float',
        'importe5'  => 'float',
        'importe6'  => 'float',
        'importe7'  => 'float',
        'importe8'  => 'float',
        'importe9'  => 'float',
        'importe10' => 'float',
        'recibo_id' => 'integer',
        'periodo_id'=> 'integer',
    ];

    // Relaciones (ajusta namespaces si tus modelos están en otro lugar)
    public function recibo()
    {
        return $this->belongsTo(\App\Models\CFE\Recibo::class, 'recibo_id');
    }

    public function periodo()
    {
        return $this->belongsTo(\App\Models\CFE\Periodo::class, 'periodo_id');
    }

    // Ejemplo
    /**
     * Normaliza texto para búsquedas:
     * - trim
     * - lowercase
     * - sin acentos (ASCII)
     * - colapsa espacios
     */
    private function normalizeSearchText(?string $text): string
    {
        return Str::of((string) $text)
            ->trim()
            ->lower()
            ->ascii()                      // quita acentos: "Tensión" => "tension"
            ->replaceMatches('/\s+/', ' ') // colapsa espacios
            ->toString();
    }

    private function matchScore(string $haystack, string $needle): int
    {
        if ($needle === '' || $haystack === '') return 0;

        $len = mb_strlen($needle);

        // 1) Exacto
        if ($haystack === $needle) return 400;

        // 2) Empieza con
        if (Str::startsWith($haystack, $needle)) return 300;

        // Si es muy corto (1-2 chars), NO permitir contains para evitar falsos positivos
        if ($len < 3) return 0;

        // 3) Palabra completa
        $pattern = '/\b' . preg_quote($needle, '/') . '\b/u';
        if (preg_match($pattern, $haystack)) return 200;

        // 4) Contiene
        if (Str::contains($haystack, $needle)) return 100;

        return 0;
    }


    /**
     * ✅ Devuelve TODOS los matches ordenados por relevancia.
     * Cada item:
     *  [
     *    'n' => 7,
     *    'concepto' => 'Total',
     *    'importe' => 8095.23,
     *    'score' => 400
     *  ]
     */
    public function findConceptosImportes(string $busqueda): array
    {
        $needle = $this->normalizeSearchText($busqueda);
        if ($needle === '') return [];

        $matches = [];

        for ($i = 1; $i <= 10; $i++) {
            $conceptoRaw = $this->{"concepto{$i}"} ?? null;
            if (!$conceptoRaw) continue;

            $haystack = $this->normalizeSearchText($conceptoRaw);
            $score = $this->matchScore($haystack, $needle);

            if ($score > 0) {
                $matches[] = [
                    'n'        => $i,
                    'concepto' => $this->{"concepto{$i}"},
                    'importe'  => $this->{"importe{$i}"} ?? null, // casteado float
                    'score'    => $score,
                    '_len'     => mb_strlen((string)$this->{"concepto{$i}"}), // para desempate
                ];
            }
        }

        usort($matches, function ($a, $b) {
            // score desc
            if ($a['score'] !== $b['score']) return $b['score'] <=> $a['score'];
            // concepto más corto primero
            if ($a['_len'] !== $b['_len']) return $a['_len'] <=> $b['_len'];
            // n más bajo primero
            return $a['n'] <=> $b['n'];
        });

        // limpia campo interno
        return array_map(function ($m) {
            unset($m['_len']);
            return $m;
        }, $matches);
    }

    /**
     * ✅ Devuelve SOLO el mejor match (o null)
     */
    public function findConceptoImporte(string $busqueda): ?array
    {
        $all = $this->findConceptosImportes($busqueda);
        return $all[0] ?? null;
    }

    /**
     * ✅ Devuelve SOLO el importe del mejor match (o null)
    /**
     * * Devuelve el importeN dado un texto de búsqueda.
     * * Ej: "total" => importe7 (o el que corresponda)
     * * $importe = $concepto->importeDe('total');      // match parcial
     * * $importe = $concepto->importeDe('tot', true);  // parcial
     * * $importe = $concepto->importeDe('Total', false); // exacto
     * *
     * * @param string $busqueda Texto a buscar (parcial o exacto)
     * * @param bool $partial true=contiene, false=igual exacto
     * * @return float|null
     * */
    public function importeDe(string $busqueda): ?float
    {
        $best = $this->findConceptoImporte($busqueda);
        return $best['importe'] ?? null;
    }
    /**
     * ✅ Accessor: mapa "Concepto original" => importe
     * (sin parámetros, útil para debug/mostrar)
     */
    protected function conceptosImportes(): Attribute
    {
        return Attribute::make(
            get: function () {
                $map = [];

                for ($i = 1; $i <= 10; $i++) {
                    $c = trim((string)($this->{"concepto{$i}"} ?? ''));
                    if ($c === '') continue;

                    $map[$c] = $this->{"importe{$i}"} ?? null;
                }

                return $map;
            }
        );
    }




}
