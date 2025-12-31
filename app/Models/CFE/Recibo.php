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

    protected $fillable = [
        'rpu',
        'medidor',
        'cuenta',
        'tarifa',
        'periodo',
        'periodo_extend',
        'direccion',
        'desde',
        'hasta',

        'consumo',
        'demanda',
        'reactivos',
        'factor_potencia',
        'factor_carga',

        'energia',
        'subtotal',
        'iva',
        'dap',
        'cargos_y_depositos',
        'creditos_y_redondeos',
        'total',
        'validacion_total',
        'total_recibo',
        'diferencia',

        'periodo_id',
        'servicio_id',

        'xml_file',
        'pdf_file',

        'rpu_ok',
        'periodo_ok',
        'total_ok',
        'consumo_ok',
        'desde_ok',
        'hasta_ok',
        'consumo_prom_ok',
        'total_prom_ok',

        'validado',

        'activo',
        'bloqueado',

        'conciliado_at',

        'observaciones',
    ];

    protected $casts = [
        'desde' => 'date:Y-m-d',
        'hasta' => 'date:Y-m-d',

        'consumo'          => 'decimal:2',
        'demanda'          => 'decimal:2',
        'reactivos'        => 'decimal:2',
        'factor_potencia'  => 'decimal:2',
        'factor_carga'     => 'decimal:2',

        'energia'              => 'decimal:2',
        'subtotal'             => 'decimal:2',
        'iva'                  => 'decimal:2',
        'dap'                  => 'decimal:2',
        'cargos_y_depositos'   => 'decimal:2',
        'creditos_y_redondeos' => 'decimal:2',
        'total'                => 'decimal:2',
        'validacion_total'     => 'decimal:2',
        'total_recibo'         => 'decimal:2',
        'diferencia'           => 'decimal:2',

        'periodo_id'  => 'integer',
        'servicio_id' => 'integer',

        'rpu_ok'      => 'boolean',
        'periodo_ok'  => 'boolean',
        'total_ok'    => 'boolean',
        'consumo_ok'  => 'boolean',
        'desde_ok'    => 'boolean',
        'hasta_ok'    => 'boolean',
        'consumo_prom_ok' => 'boolean',
        'total_prom_ok' => 'boolean',

        'validado'    => 'boolean',

        'activo'      => 'boolean',
        'bloqueado'   => 'boolean',

        'deleted_at'  => 'datetime',
        'conciliado_at' => 'datetime',
    ];

    protected $appends = [
        'xml_url',
        'pdf_url',
        // 'expediente_id', // opcional
    ];

    protected static function booted()
    {
        static::saving(function (Recibo $recibo) {
            $recibo->validacion_total =
                (float) $recibo->energia +
                (float) $recibo->iva +
                (float) $recibo->dap +
                (float) $recibo->cargos_y_depositos +
                (float) $recibo->creditos_y_redondeos;

            // diferencia = total - validacion_total
            $recibo->diferencia = (float) $recibo->total - (float) $recibo->validacion_total;
        });
    }

    /* =========================================================================
     *  RELACIONES
     * ========================================================================= */

    public function periodo()
    {
        return $this->belongsTo(Periodo::class);
    }

    public function servicio()
    {
        return $this->belongsTo(Servicio::class);
    }

    /**
     * 1-1: Conceptos asociados al recibo (tabla conceptos tiene recibo_id).
     */
    public function concepto()
    {
        return $this->hasOne(Concepto::class, 'recibo_id', 'id');
    }

    /**
     * 1-1: Expediente asociado al recibo (tabla expedientes tiene recibo_id).
     */
    public function expediente()
    {
        return $this->hasOne(\App\Models\CFE\Expediente::class, 'recibo_id', 'id');
    }

    /* =========================================================================
     *  ACCESSORS PARA PDF/XML
     * ========================================================================= */

    public function getXmlUrlAttribute(): ?string
    {
        return $this->xml_file ? Storage::disk('cfe')->url($this->xml_file) : null;
    }

    public function getPdfUrlAttribute(): ?string
    {
        return $this->pdf_file ? Storage::disk('cfe')->url($this->pdf_file) : null;
    }

    /**
     * (Opcional) para usarlo rápido en front sin cargar toda la relación:
     */
    public function getExpedienteIdAttribute(): ?int
    {
        if ($this->relationLoaded('expediente')) {
            return $this->expediente?->id;
        }

        // si no está eager loaded, evita query extra:
        return null;
    }

    /* =========================================================================
     *  SCOPES
     * ========================================================================= */

    public function scopeDeRpu($query, string $rpu)
    {
        return $query->where('rpu', $rpu);
    }

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

    public function scopeDelPeriodoVigente($query)
    {
        $p = Periodo::vigente();
        if ($p) {
            $query->where('periodo_id', $p->id);
        }
        return $query;
    }

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

    /**
     * Filtrar recibos que ya tienen expediente.
     */
    public function scopeConExpediente($query)
    {
        return $query->whereHas('expediente');
    }
}
