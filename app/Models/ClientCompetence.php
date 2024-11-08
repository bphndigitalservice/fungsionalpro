<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientCompetence extends Model
{
    use HasFactory;

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function promotionLevel(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(CRoleLevel::class, 'id','promotion_training_level_id');
    }
}
