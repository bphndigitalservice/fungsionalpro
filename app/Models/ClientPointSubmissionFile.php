<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientPointSubmissionFile extends Model
{
    use HasFactory;

    public function submission(): BelongsTo
    {
        return $this->belongsTo(ClientPointSubmission::class, 'requisite_spec_id', 'id');
    }
}
