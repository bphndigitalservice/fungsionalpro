<?php

namespace App\Models;

use App\Enums\UkomDocumentPack;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UkomDocumentType extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'pack' => UkomDocumentPack::class,
            'is_required' => 'boolean',
            'required_if_claims_new_degree' => 'boolean',
        ];
    }

    public function documents(): HasMany
    {
        return $this->hasMany(UkomApplicationDocument::class);
    }

    public function isRequiredFor(bool $claimsNewDegree): bool
    {
        if ($this->required_if_claims_new_degree) {
            return $claimsNewDegree;
        }

        return $this->is_required;
    }
}
