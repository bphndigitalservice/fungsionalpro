<?php

namespace App\Models;

use App\Models\Concern\BelongsToClient;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientActivity extends Model
{
    use BelongsToClient;
    use HasFactory;

    protected $guarded = [
        'id',
        'is_verified',
        'verified_at',
        'verified_by',
        'verification_note',
    ];


    protected $casts = [
        'activity_details' => 'array',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function regProvince(): BelongsTo
    {
        return $this->belongsTo(RegProvince::class, 'reg_province_id', 'id');
    }

    public function verified(): void
    {
        $this->forceFill([
            'is_verified' => true,
            'verified_at' => now(),
        ])->save();
    }
}