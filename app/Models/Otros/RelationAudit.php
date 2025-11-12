<?php

namespace App\Models\Otros;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class RelationAudit extends Model{

    protected $table = 'relation_audits';

    protected $fillable = [
        'auditable_type',
        'auditable_id',
        'relation_name',
        'action',
        'related_ids',
        'user_id',
        'created_at'
    ];

    protected $casts = [
        'related_ids' => 'array',
        'created_at' => 'datetime'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function auditable()
    {
        return $this->morphTo();
    }

}
