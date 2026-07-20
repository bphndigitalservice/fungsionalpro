<?php

namespace App\Filament\Resources\CRoles\Concern;

use Illuminate\Contracts\Auth\Authenticatable;
use App\Models\User;
use App\Services\ClientAccessService;

trait CanAccessClientData
{
    public function canAccessClientData(): void
    {
        $principal = $this->getPrincipal();

        if (! app(ClientAccessService::class)->canAccess($principal, $this->record)) {
            abort(403, 'Unauthorized');
        }
    }

    public function getPrincipal(): Authenticatable|User
    {
        return auth()->user();
    }
}
