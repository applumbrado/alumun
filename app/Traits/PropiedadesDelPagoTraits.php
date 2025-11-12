<?php

namespace App\Traits;

trait PropiedadesDelPagoTraits{

    public function getDiaLimiteAttribute(): int {
        return $this->pago->concepto->dia_limite;
    }

    public function getTieneDescuentoBecaAttribute(): bool {
        return $this->pago->concepto->tiene_descuento_beca;
    }

    public function getAceptaPagosDiversosAttribute(): bool {
        return $this->pago->concepto->acepta_pagos_diversos;
    }

    public function getTieneDescuentoAttribute(): bool {
        return $this->pago->concepto->tiene_descuento;
    }

    public function getAplicaAAttribute(): int {
        return $this->pago->concepto->aplica_a;
    }

    public function getTieneDescuentoPorPromocionAttribute(): bool {
        return $this->pago->concepto->tiene_descuento_por_promocion;
    }

    public function getEsFacturableAttribute(): bool {
        return $this->pago->concepto->es_facturable;
    }

    public function getSePuedeVerEnPantallaAttribute(): bool {
        return $this->pago->concepto->se_puede_ver_en_pantalla;
    }

    public function getSePuedeRepetirAttribute(): bool {
        return $this->pago->concepto->se_puede_repetir;
    }

    public function getOrdenPrioridadAttribute(): int {
        return $this->pago->concepto->orden_prioridad;
    }

    public function getAplicaAlSiguienteNivelAttribute(): bool {
        return $this->pago->concepto->aplica_al_siguiente_nivel;
    }





}
