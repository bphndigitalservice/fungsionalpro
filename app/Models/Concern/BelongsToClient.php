<?php

namespace App\Models\Concern;

use App\Models\Client;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToClient
{
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
