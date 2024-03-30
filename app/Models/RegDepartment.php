<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class RegDepartment extends Model
{
    use HasFactory;

    public $timestamps = false;

    public function echelon1s(): HasMany
    {
        return $this->hasMany(RegDepartmentEchelon1::class, 'department_id', 'id');
    }

    public function agency(): MorphOne
    {
        return $this->morphOne(Client::class, 'agency');
    }
}
