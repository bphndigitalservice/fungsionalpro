<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegDepartmentEchelon1 extends Model
{
    use HasFactory;

    public $timestamps = false;

    public function department(): BelongsTo
    {
        return $this->belongsTo(RegDepartment::class, 'department_id', 'id');
    }
}
