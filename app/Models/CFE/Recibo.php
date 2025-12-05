<?php

namespace App\Models\CFE;

use App\Models\Catalogos\Servicio;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Recibo extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'recibos';

    /**
     * Campos asignables masivamente.
     * (No incluimos id, created_at, updated_at, deleted_at)
     */
    protected $fillable = [
        'rpu',
        'medidor',
        'cuenta',
        'tarifa',
        'periodo',
        'direccion',
        'desde',
        'hasta',

        'consumo',
        'demanda',
        'reactivos',
        'factor_potencia',
        'factor_carga',

        'energia',
        'iva',
        'dap',
        'cargos_y_depositos',
        'creditos_y_redondeos',
        'total',
        'validacion_total',
        'diferencia',

        'periodo_id',
        'servicio_id',

        'xml_file',
        'pdf_file',

        'activo',
        'bloqueado',
    ];

    /**
     * Casts nativos de Eloquent.
     */
    protected $casts = [
        'desde'    => 'date:Y-m-d',
        'hasta'    => 'date:Y-m-d',

        'consumo'             => 'decimal:2',
        'demanda'             => 'decimal:2',
        'reactivos'           => 'decimal:2',
        'factor_potencia'     => 'decimal:2',
        'factor_carga'        => 'decimal:2',

        'energia'             => 'decimal:2',
        'iva'                 => 'decimal:2',
        'dap'                 => 'decimal:2',
        'cargos_y_depositos'  => 'decimal:2',
        'creditos_y_redondeos'=> 'decimal:2',
        'total'               => 'decimal:2',
        'validacion_total'    => 'decimal:2',
        'diferencia'          => 'decimal:2',

        'periodo_id'          => 'integer',
        'servicio_id'         => 'integer',

        'activo'              => 'boolean',
        'bloqueado'           => 'boolean',

        'deleted_at'          => 'datetime',
    ];

    /**
     * Atributos calculados que se deben incluir en toArray() / toJson().
     */
    protected $appends = [
        'xml_url',
        'pdf_url',
    ];

    /* =========================================================================
     *  BOOT / EVENTOS
     * ========================================================================= */

    protected static function booted()
    {
        static::saving(function (Recibo $recibo) {
            // validacion_total = energia + iva + dap + cargos_y_depositos + creditos_y_redondeos
            $recibo->validacion_total =
                (float) $recibo->energia +
                (float) $recibo->iva +
                (float) $recibo->dap +
                (float) $recibo->cargos_y_depositos +
                (float) $recibo->creditos_y_redondeos;

            // diferencia = validacion_total - total
            $recibo->diferencia = (float) $recibo->validacion_total - (float) $recibo->total;
        });
    }

    /* =========================================================================
     *  RELACIONES
     * ========================================================================= */

    /**
     * Periodo al que pertenece este recibo.
     */
    public function periodo()
    {
        return $this->belongsTo(Periodo::class);
    }

    /**
     * Servicio (RPU) al que pertenece este recibo.
     */
    public function servicio()
    {
        return $this->belongsTo(Servicio::class);
    }

    /* =========================================================================
     *  ACCESSORS PARA LEER PDF/XML DESDE EL WEB
     * ========================================================================= */


    public function getXmlUrlAttribute(): ?string
    {
        return $this->xml_file
            ? Storage::disk('cfe')->url($this->xml_file)
            : null;
    }

    public function getPdfUrlAttribute(): ?string
    {
        return $this->pdf_file
            ? Storage::disk('cfe')->url($this->pdf_file)
            : null;
    }

    /* =========================================================================
     *  SCOPES
     * ========================================================================= */

    /**
     * Filtrar por RPU.
     */
    public function scopeDeRpu($query, string $rpu)
    {
        return $query->where('rpu', $rpu);
    }

    /**
     * Filtrar por un periodo específico (objeto o id).
     */
    public function scopeDelPeriodo($query, $periodo)
    {
        if ($periodo instanceof Periodo) {
            return $query->where('periodo_id', $periodo->id);
        }

        if (is_numeric($periodo)) {
            return $query->where('periodo_id', (int) $periodo);
        }

        return $query;
    }

    /**
     * Filtrar por el periodo vigente (predeterminado).
     */
    public function scopeDelPeriodoVigente($query)
    {
        $p = Periodo::vigente();

        if ($p) {
            $query->where('periodo_id', $p->id);
        }

        return $query;
    }

    /**
     * Filtrar por servicio.
     */
    public function scopeDelServicio($query, $servicio)
    {
        if ($servicio instanceof Servicio) {
            return $query->where('servicio_id', $servicio->id);
        }

        if (is_numeric($servicio)) {
            return $query->where('servicio_id', (int) $servicio);
        }

        return $query;
    }
}
