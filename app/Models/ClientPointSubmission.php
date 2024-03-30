<?php

namespace App\Models;

use App\Enums\PointSubmissionStatus;
use App\Enums\PointSubmissionType;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClientPointSubmission extends Model
{
    use HasFactory, HasUlids;

    protected $casts = [
        'submission_type' => PointSubmissionType::class,
        'status' => PointSubmissionStatus::class,
    ];

    public function files(): HasMany
    {
        return $this->hasMany(ClientPointSubmissionFile::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function bag(): BelongsTo
    {
        return $this->belongsTo(ClientPointSubmissionBag::class, 'submission_bag_id', 'id');
    }
}
