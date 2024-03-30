<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CRole extends Model
{
    use HasFactory;

    public function levels(): HasMany
    {
        return $this->hasMany(CRoleLevel::class);
    }
}
