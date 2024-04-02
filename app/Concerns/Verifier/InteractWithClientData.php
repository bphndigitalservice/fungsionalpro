<?php

namespace App\Concerns\Verifier;

use App\Models\VerifierAccess;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @method hasMany(string $class, string|null $getForeignKey, string|null $getOwnerKey)
 */
trait InteractWithClientData
{
    public function hasAccessTo(Verifiable $scope): bool
    {
        return $this->verificationScopes->contains($scope);
    }

    public function verificationScopes(): HasMany
    {
        return $this->hasMany(VerifierAccess::class, $this->getForeignKey(), $this->getOwnerKey());
    }

    public function getForeignKey(): ?string
    {
        return 'verifier_access_id';
    }

    public function getOwnerKey(): ?string
    {
        return 'id';
    }
}
