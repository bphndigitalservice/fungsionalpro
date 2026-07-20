<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CRoleLevel extends Model
{
    use HasFactory;

    protected $guarded = ['id'];


    public function role(): BelongsTo
    {
        return $this->belongsTo(CRole::class, 'c_role_id');
    }
}
