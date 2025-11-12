<?php

namespace App\Traits;

use App\Models\Otros\RelationAudit;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait Auditable{

    public static function bootAuditable(): void{

        static::creating(function ($model) {
            $model->setCreatedBy();
            $model->setUpdatedBy();
        });

        static::updating(function ($model) {
            $model->setUpdatedBy();
        });

        static::deleting(function ($model) {
            $model->setDeletedBy();
        });

        static::created(function ($model) {
            $model->insertLog('created');
        });

        static::updated(function ($model) {
            $model->insertLog('updated');
        });

        static::deleted(function ($model) {
            $model->insertLog('deleted');
        });

    }

    /**
     * Override the attach method for BelongsToMany relations
     */
    public function attach($id, array $attributes = [], $touch = true)
    {
        $result = parent::attach($id, $attributes, $touch);
        $this->handleRelationChange('attach');
        return $result;
    }

    /**
     * Override the detach method for BelongsToMany relations
     */
    public function detach($ids = null, $touch = true)
    {
        $result = parent::detach($ids, $touch);
        $this->handleRelationChange('detach');
        return $result;
    }

    /**
     * Override the sync method for BelongsToMany relations
     */
    public function sync($ids, $detaching = true)
    {
        $result = parent::sync($ids, $detaching);
        $this->handleRelationChange('sync');
        return $result;
    }

    /**
     * Override the updateExistingPivot method for BelongsToMany relations
     */
    public function updateExistingPivot($id, array $attributes, $touch = true)
    {
        $result = parent::updateExistingPivot($id, $attributes, $touch);
        $this->handleRelationChange('updateExistingPivot');
        return $result;
    }

    /**
     * Handle relation changes by updating the modifier.
     */
    protected function handleRelationChange(string $operation): void
    {
        $userId = $this->getCurrentUserId();

        if ($userId && $this->isFillable('modificadopor_id')) {
            $this->modificadopor_id = $userId;

            // Usar saveQuietly para evitar bucles de eventos
            if (method_exists($this, 'saveQuietly')) {
                $this->saveQuietly();
            } else {
                $this->save();
            }
            $modelName = class_basename($this);

            RelationAudit::create([
                'auditable_type' => get_class($this),
                'auditable_id' => $this->getKey(),
                'relation_name' => $modelName,
                'action' => $operation,
                'related_ids' => array_map(function($model) {
                    return $model->getKey();
                }, $this->getKey()),
                'user_id' => $userId,
                'created_at' => now(),
            ]);


            Log::info("Relation {$operation} detected", [
                'model' => get_class($this),
                'model_id' => $this->getKey(),
                'operation' => $operation,
                'user_id' => $userId
            ]);
        }
    }

    public function setCreatedBy(): void {
        if (Auth::check() && $this->isFillable('creadopor_id')) {
            if (is_null($this->creadopor_id)) {
                $this->creadopor_id = Auth::id();
            }
        }
    }

    public function setUpdatedBy(): void{
        if (Auth::check() && $this->isFillable('modificadopor_id')) {
            $this->modificadopor_id = Auth::id();
        }
    }

    public function setDeletedBy(): void{
        if (method_exists($this, 'bootSoftDeletes') &&
            Auth::check() &&
            $this->isFillable('modificadopor_id')) {

            $this->modificadopor_id = Auth::id();

            if ($this->exists) {
                $this->saveQuietly();
            }
        }
    }

    /**
     * Get the user who created the model.
     */
    public function creadoPor(){
        return $this->belongsTo(User::class, 'creadopor_id');
    }

    public function modificadoPor(){
        return $this->belongsTo(User::class, 'modificadopor_id');
    }

    public function eliminadoPor(){
        return $this->belongsTo(User::class, 'modificadopor_id');
    }

    protected function getCurrentUserId(){
        if (Auth::check()) {
            return Auth::id();
        }

        if (request()->user()) {
            return request()->user()->id;
        }

        return $this->currentUserId ?? null;
    }




    private function insertLog(string $triggerStatus): void{
        $messages = [
            'created' => 'Ha Creado',
            'updated' => 'Ha Actualizado',
            'deleted' => 'Ha Eliminado',
        ];

        $icons = [
            'created' => 'info',
            'updated' => 'warning',
            'deleted' => 'Danger',
        ];

        $statuses = [
            'created' => 0,
            'updated' => 1,
            'deleted' => 2,
        ];

        $modelName = class_basename($this);

        if (Auth::check()) {
            $user = Auth::user();
            $log = DB::table('logs')->insert([
                'model_name'     => $modelName,
                'model_id'       => $this->getKey(),
                'trigger_status' => $triggerStatus,
                'trigger_type'   => $statuses[$triggerStatus] ?? 1,
                'message'        => $user?->username ?? ' ' .$messages[$triggerStatus] ?? 'ha realizado una acción',
                'icon'           => $icons[$triggerStatus] ?? 'info',
                'status'         => $statuses[$triggerStatus] ?? 1,
                'ip'             => request()->ip(),
                'host'           => request()->getHost(),
                'fecha'          => now(),
                'user_id'        => $user?->id ?? 1,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        }
    }


}
