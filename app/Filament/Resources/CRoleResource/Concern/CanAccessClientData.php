<?php

namespace App\Filament\Resources\CRoleResource\Concern;

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

    public function getPrincipal(): \Illuminate\Contracts\Auth\Authenticatable|User
    {
        return auth()->user();
    }
}
