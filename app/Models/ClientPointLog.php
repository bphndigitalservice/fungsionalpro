<?php

namespace App\Models;

use App\Models\Concern\BelongsToClient;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientPointLog extends Model
{
    use HasFactory;
    use BelongsToClient;

    public function bag(): BelongsTo
    {
        return $this->belongsTo(ClientPointSubmissionBag::class);
    }
}
