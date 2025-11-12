<?php

namespace App\Traits;

trait GrupoAttributes{

    public static function getSelectItemsAttribute(): array{
        return ['id',
            'grupo_id','clave_grupo','grupo',
            'grupo_oficial', 'grupo_periodo', 'grupo_periodo_ciclo',
            'nivel','grado','grado_pai','is_visible','is_bloqueado','num',
            'is_activo_en_caja', 'is_ver_boleta_interna', 'is_ver_boleta_oficial','is_grupo_pai',
            'status_grupo', 'grupo_siguiente_id',
            'nivel_id', 'nivel', 'ciclo_id', 'ciclo'];
    }

}
