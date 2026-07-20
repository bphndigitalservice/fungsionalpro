<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasOne;
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

    protected $guarded = ['id'];


    protected $casts = [
        'category' => TrainingType::class,
        'completion_status' => TrainingCompletionStatus::class
    ];

    public function promotionLevel(): HasOne
    {
        return $this->hasOne(CRoleLevel::class, 'id', 'promotion_training_level_id');
    }
}
