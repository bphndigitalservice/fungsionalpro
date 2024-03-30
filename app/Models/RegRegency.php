<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class RegRegency extends Model
{
    use HasFactory;

    public $timestamps = false;

    public function province(): BelongsTo
    {
        return $this->belongsTo(RegProvince::class, 'province_id', 'id');
    }

    public function agency(): MorphOne
    {
        return $this->morphOne(Client::class, 'agency');
    }

    public function echelon1s(): HasMany
    {
        return $this->hasMany(RegRegencyEchelon1::class);
    }
}
