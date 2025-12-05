<?php

namespace App\Models\CFE;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expediente extends Model
{
    use SoftDeletes;

    /**
     * Los atributos que son asignables en masa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'archivo_de_cuadre_1',
        'archivo_de_cuadre_2',
        'archivo_de_cuadre_3',
        'archivo_de_factura_1',
        'archivo_de_factura_2',
        'archivo_de_factura_3',
        'ruta_recibos',
        'periodo_id',
        'servicio_id',
        'recibo_id', // Nota: Veo que en la migración tienes una FK a recibo_id
    ];

    /**
     * Los atributos que deben tener valores por defecto.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'archivo_de_cuadre_1' => '',
        'archivo_de_cuadre_2' => '',
        'archivo_de_cuadre_3' => '',
        'archivo_de_factura_1' => '',
        'archivo_de_factura_2' => '',
        'archivo_de_factura_3' => '',
        'ruta_recibos' => '',
    ];

    /**
     * Los atributos que deben ser transformados.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'periodo_id' => 'integer',
        'servicio_id' => 'integer',
        'recibo_id' => 'integer',
        'deleted_at' => 'datetime',
    ];

    /**
     * Obtener el período asociado al expediente.
     */
    public function periodo(): BelongsTo
    {
        return $this->belongsTo(Periodo::class, 'periodo_id');
    }

    /**
     * Obtener el servicio asociado al expediente.
     */
    public function servicio(): BelongsTo
    {
        return $this->belongsTo(Servicio::class, 'servicio_id');
    }

    /**
     * Obtener el recibo asociado al expediente.
     */
    public function recibo(): BelongsTo
    {
        return $this->belongsTo(Recibo::class, 'recibo_id');
    }

    /**
     * Accesor para obtener la URL completa de archivos.
     */
    public function getArchivoCuadre1UrlAttribute(): string
    {
        return $this->archivo_de_cuadre_1 ? asset('storage/' . $this->archivo_de_cuadre_1) : '';
    }

    public function getArchivoCuadre2UrlAttribute(): string
    {
        return $this->archivo_de_cuadre_2 ? asset('storage/' . $this->archivo_de_cuadre_2) : '';
    }

    public function getArchivoCuadre3UrlAttribute(): string
    {
        return $this->archivo_de_cuadre_3 ? asset('storage/' . $this->archivo_de_cuadre_3) : '';
    }

    public function getArchivoFactura1UrlAttribute(): string
    {
        return $this->archivo_de_factura_1 ? asset('storage/' . $this->archivo_de_factura_1) : '';
    }

    public function getArchivoFactura2UrlAttribute(): string
    {
        return $this->archivo_de_factura_2 ? asset('storage/' . $this->archivo_de_factura_2) : '';
    }

    public function getArchivoFactura3UrlAttribute(): string
    {
        return $this->archivo_de_factura_3 ? asset('storage/' . $this->archivo_de_factura_3) : '';
    }

    public function getRutaRecibosUrlAttribute(): string
    {
        return $this->ruta_recibos ? asset('storage/' . $this->ruta_recibos) : '';
    }

    /**
     * Verificar si el expediente tiene archivos de cuadre.
     */
    public function tieneArchivosCuadre(): bool
    {
        return !empty($this->archivo_de_cuadre_1) ||
            !empty($this->archivo_de_cuadre_2) ||
            !empty($this->archivo_de_cuadre_3);
    }

    /**
     * Verificar si el expediente tiene archivos de factura.
     */
    public function tieneArchivosFactura(): bool
    {
        return !empty($this->archivo_de_factura_1) ||
            !empty($this->archivo_de_factura_2) ||
            !empty($this->archivo_de_factura_3);
    }

    /**
     * Obtener todos los archivos de cuadre como array.
     */
    public function getArchivosCuadreAttribute(): array
    {
        return array_filter([
            $this->archivo_de_cuadre_1,
            $this->archivo_de_cuadre_2,
            $this->archivo_de_cuadre_3,
        ]);
    }

    /**
     * Obtener todos los archivos de factura como array.
     */
    public function getArchivosFacturaAttribute(): array
    {
        return array_filter([
            $this->archivo_de_factura_1,
            $this->archivo_de_factura_2,
            $this->archivo_de_factura_3,
        ]);
    }

    /**
     * Scope para expedientes con archivos de cuadre.
     */
    public function scopeConArchivosCuadre($query)
    {
        return $query->where(function ($q) {
            $q->whereNotNull('archivo_de_cuadre_1')
                ->orWhereNotNull('archivo_de_cuadre_2')
                ->orWhereNotNull('archivo_de_cuadre_3');
        });
    }

    /**
     * Scope para expedientes con archivos de factura.
     */
    public function scopeConArchivosFactura($query)
    {
        return $query->where(function ($q) {
            $q->whereNotNull('archivo_de_factura_1')
                ->orWhereNotNull('archivo_de_factura_2')
                ->orWhereNotNull('archivo_de_factura_3');
        });
    }

    /**
     * Scope para expedientes por período.
     */
    public function scopePorPeriodo($query, $periodoId)
    {
        return $query->where('periodo_id', $periodoId);
    }

    /**
     * Scope para expedientes por servicio.
     */
    public function scopePorServicio($query, $servicioId)
    {
        return $query->where('servicio_id', $servicioId);
    }
}
