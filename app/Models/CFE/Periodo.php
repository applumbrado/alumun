<?php

namespace App\Models\CFE;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;


class Periodo extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'periodos';

    /**
     * Campos asignables masivamente.
     * Nota: normalmente NO se incluye el id en $fillable.
     */
    protected $fillable = [
        'anomes',
        'ano',
        'mes',
        'mes_nombre',
        'tipo',
        'digito',
        'predeterminado',
    ];

    /**
     * Casts nativos de Eloquent.
     */
    protected $casts = [
        'ano'           => 'integer',
        'mes'           => 'integer',
        'tipo'          => 'integer',
        'digito'        => 'integer',
        'predeterminado'=> 'boolean',
        'deleted_at'    => 'datetime',
    ];

    /**
     * Atributos calculados que se devolverán automáticamente.
     */
    protected $appends = [
        'label',
        'anio_mes',
    ];

    /* =========================================================================
     *  ACCESSORS
     * ========================================================================= */

    /**
     * Etiqueta amigable para selects, etc.
     * Ej: "ENERO 2025 (T0)"
     */
    public function getLabelAttribute(): string{

        $mes = $this->mes_nombre ?: static::nombreMes($this->mes);

//        return trim(sprintf('%s %d (T%d)', $mes, $this->ano, $this->tipo));

        return trim(sprintf('%s %d', $mes, $this->ano));

    }

    /**
     * Año-mes formateado.
     * Ej: "2025-01"
     */
    public function getAnioMesAttribute(): string
    {
        return sprintf('%04d-%02d', $this->ano, $this->mes);
    }

    /* =========================================================================
     *  SCOPES
     * ========================================================================= */

    public function scopePredeterminado($query)
    {
        return $query->where('predeterminado', true);
    }

    public function scopeDelAno($query, int $ano)
    {
        return $query->where('ano', $ano);
    }

    public function scopeDelMes($query, int $mes)
    {
        return $query->where('mes', $mes);
    }

    public function scopeDelPeriodo($query, int $ano, int $mes, int $tipo = 0)
    {
        return $query->where([
            'ano'  => $ano,
            'mes'  => $mes,
            'tipo' => $tipo,
        ]);
    }

    /* =========================================================================
     *  HELPERS DE DOMINIO
     * ========================================================================= */

    /**
     * Crea (o recupera) un periodo a partir de año/mes/tipo.
     * Lógica útil para cuando importes OCR_AAAA/OCR_MM, etc.
     */
    public static function fromAnoMesTipo(
        int $ano,
        int $mes,
        int $tipo = 0,
        bool $predeterminado = false
    ): self {
        $anomes = sprintf('%04d%02d', $ano, $mes);

        return static::firstOrCreate(
            [
                'anomes' => $anomes,
                'ano'    => $ano,
                'mes'    => $mes,
                'tipo'   => $tipo,
            ],
            [
                'mes_nombre'    => static::nombreMes($mes),
                'digito'        => 0,
                'predeterminado'=> $predeterminado,
            ]
        );
    }


    /**
     * Devuelve el nombre del mes en texto (puedes ajustar a tu gusto).
     */
    public static function nombreMes(int $mes): string
    {
        $nombres = [
            1  => 'ENERO',
            2  => 'FEBRERO',
            3  => 'MARZO',
            4  => 'ABRIL',
            5  => 'MAYO',
            6  => 'JUNIO',
            7  => 'JULIO',
            8  => 'AGOSTO',
            9  => 'SEPTIEMBRE',
            10 => 'OCTUBRE',
            11 => 'NOVIEMBRE',
            12 => 'DICIEMBRE',
        ];

        return $nombres[$mes] ?? '';
    }

    /* =========================================================================
     *  SCOPES
     * ========================================================================= */

    public function scopeVigente($query)
    {
        return $query->predeterminado();
    }

    /* =========================================================================
     *  MÉTODOS ESTÁTICOS
     * ========================================================================= */

    /**
     * Devuelve el periodo vigente (predeterminado), con caché.
     */
    public static function vigente(): ?self{

        return Cache::remember(
            'periodo_vigente',
            now()->addMinutes(30),
            function () {
                return static::predeterminado()
                    ->orderByDesc('ano')
                    ->orderByDesc('mes')
                    ->orderBy('tipo')
                    ->first();
            }
        );

    }

    /* =========================================================================
     *  HELPERS DE DOMINIO
     * ========================================================================= */

    public function marcarComoPredeterminado(): void
    {
        // Si quieres que solo exista 1 predeterminado en TODO el sistema:
        static::query()->update(['predeterminado' => false]);

        // Si quieres limitar a 1 por tipo, cambia a:
        // static::where('tipo', $this->tipo)->update(['predeterminado' => false]);

        $this->predeterminado = true;
        $this->save();

        // Limpiar caché, para que Periodo::vigente() se actualice
        Cache::forget('periodo_vigente');
    }



}
