<?php

namespace App\Models;

use App\Models\Concern\BelongsToClient;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\ClientDocumentType;


class ClientDossier extends Model
{
    use HasFactory, HasUlids, BelongsToClient;

    protected $guarded = ['id'];


    public function documentType()
    {
        return $this->belongsTo(ClientDocumentType::class, 'document_type_id');
    }

}
