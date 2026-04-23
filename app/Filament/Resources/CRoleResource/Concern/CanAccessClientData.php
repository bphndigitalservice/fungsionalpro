<?php

namespace App\Filament\Resources\CRoleResource\Concern;

use App\Enums\SystemRole;
use App\Models\Client;
use App\Models\User;
use App\Models\VerifierAccess;
use Illuminate\Database\Query\JoinClause;

trait CanAccessClientData
{

    public function canAccessClientData()
    {
        if ($this->getPrincipal()->isSuperAdmin()) {
            return;
        }

        if($this->getPrincipal()->hasSystemRole(SystemRole::Admin)) {
            $adminAccess = \App\Models\AdminAccess::query()->where('user_id', $this->getPrincipal()->id)->limit(1);

            return Client::joinSub($adminAccess, 'aa', function (JoinClause $join) {
                $join->on('clients.c_role_id', '=', 'aa.c_role_id');
            })->select('clients.*');
        }

        if ($this->getPrincipal()->hasAnySystemRole(SystemRole::AdminRegional, SystemRole::Verifier, SystemRole::AdminPusat)) {
            $verifierAccess = VerifierAccess::query()->where('user_id', $this->getPrincipal()->id)->limit(1);
            $client = Client::joinSub($verifierAccess, 'va', function (JoinClause $join) {
                $join->on('clients.c_role_id', '=', 'va.c_role_id');
                $join->on('va.entity_type', '=', 'clients.agency_type');
                $join->on('va.entity_id', '=', 'clients.agency_id');
            })->select('clients.*')->where('clients.id', $this->record->id)->first();

            if ($client) {
                return;
            }

            abort(403, 'Unauthorized');
        }

        abort(403, 'Unauthorized');
    }

    public function getPrincipal(): \Illuminate\Contracts\Auth\Authenticatable|User
    {
        return auth()->user();
    }

}
