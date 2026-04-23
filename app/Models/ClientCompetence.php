<?php

namespace App\Models;

use App\Enums\TrainingType;
use App\Enums\TrainingCompletionStatus;
use App\Models\Concern\BelongsToClient;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientCompetence extends Model
{
    use HasFactory;
    use BelongsToClient;

    protected $casts = [
        'category' => TrainingType::class,
        'completion_status' => TrainingCompletionStatus::class
    ];

    public function promotionLevel(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(CRoleLevel::class, 'id', 'promotion_training_level_id');
    }
}
