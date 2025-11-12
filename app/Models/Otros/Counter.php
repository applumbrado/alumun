<?php

namespace App\Models\Otros;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Counter extends Model{
    protected $guard_name = 'web';
    protected $table = 'counters';

    protected $fillable = ['key', 'value',];

    static function getOrCreateCounter($key){
        return self::firstOrCreate(['key' => $key]);
    }

    static function incrementCounter($key){
        $counter = static::getOrCreateCounter($key);

        // Usa una transacción para seguridad en concurrencia
        DB::transaction(function () use ($counter) {
            $counter->lockForUpdate();
            ++$counter->value;
            $counter->save();
        });

        return $counter->value;
    }

    public static function generateFolioForModel($modelName){
        $key = strtolower($modelName) . '_counter';
        $counterValue = static::incrementCounter($key);

        // Formatea tu folio usando el valor del contador
        return strtoupper($modelName[0]) . str_pad($counterValue, 6, '0', STR_PAD_LEFT);
    }

    public static function generateFolioForModelAndSerie($modelName, $serie){
        $key = strtolower($modelName) . '_' . strtolower($serie) . '_counter';
        $counterValue = static::incrementCounter($key);

        // Formatea tu folio usando el valor del contador
        return strtoupper($modelName[0]).$serie . str_pad($counterValue, 12, '0', STR_PAD_LEFT);
    }


}
