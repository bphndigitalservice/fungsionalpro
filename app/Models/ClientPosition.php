<?php

namespace App\Models;

use App\Models\Concern\BelongsToClient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientPosition extends Model
{
    use BelongsToClient;

    protected $fillable = [
        'c_role_level_id',
        'type',
        'client_id',
        'effective_date',
        'decree_number',
        'decree_file',
    ];

    protected $casts = [
        'effective_date' => 'date',
    ];

    public function crole(): BelongsTo
    {
        return $this->belongsTo(CRole::class, 'c_role_id', 'id');
    }

    public function croleLevel(): BelongsTo
    {
        return $this->belongsTo(CRoleLevel::class, 'c_role_level_id', 'id')->orderBy('id', 'asc');
    }

}