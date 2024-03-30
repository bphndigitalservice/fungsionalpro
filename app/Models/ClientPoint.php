<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientPoint extends Model
{
    use HasFactory, HasUlids;

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public static function getPoint(string $id): float
    {
        return static::query()->where('client_id', $id)->first()->point ?? 0;
    }
}
