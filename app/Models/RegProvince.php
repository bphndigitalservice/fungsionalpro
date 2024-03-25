<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class RegProvince extends Model
{
    use HasFactory;

    public $timestamps = false;

    public function regencies(): HasMany
    {
        return $this->hasMany(RegRegency::class, 'province_id', 'id');
    }

    public function agency(): MorphOne
    {
        return $this->morphOne(Client::class, "agency");
    }

}
