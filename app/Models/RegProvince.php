<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class RegProvince extends Model
{
    protected $guarded = ['id'];


    use HasFactory;

    public $timestamps = false;

    public function regencies(): HasMany
    {
        return $this->hasMany(RegRegency::class, 'province_id', 'id');
    }

    public function agency(): MorphOne
    {
        return $this->morphOne(Client::class, 'agency');
    }

    public function echelon1s(): HasMany
    {
        return $this->hasMany(RegProvinceEchelon1::class);
    }

    public function access(): MorphOne
    {
        return $this->morphOne(VerifierAccess::class, 'access');
    }

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class, 'reg_province_id', 'id');
    }
}
