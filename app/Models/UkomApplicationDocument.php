<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UkomApplicationDocument extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function application(): BelongsTo
    {
        return $this->belongsTo(UkomApplication::class, 'ukom_application_id');
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(UkomDocumentType::class, 'ukom_document_type_id');
    }
}
