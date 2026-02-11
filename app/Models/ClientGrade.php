<?php

namespace App\Models;

use App\Models\Concern\BelongsToClient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientGrade extends Model
{
    use BelongsToClient;

    public function grade(): BelongsTo
    {
        return $this->belongsTo(RegGrade::class, 'reg_grade_id', 'id')->orderBy('id', 'asc');
    }
}
