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
        $this->update([
            'is_verified' => true,
            'verified_at' => now(),
        ]);
    }
}