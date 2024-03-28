<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClientPointSubmission extends Model
{
    use HasFactory, HasUlids;

    public function files(): HasMany
    {
        return $this->hasMany(ClientPointSubmissionFile::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

}
