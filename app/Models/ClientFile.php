<?php

namespace App\Models;

use App\Models\Concern\BelongsToClient;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientFile extends Model
{
    use HasFactory;
    use BelongsToClient;

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
