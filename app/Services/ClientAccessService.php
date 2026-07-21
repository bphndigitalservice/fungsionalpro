<?php

namespace App\Services;

use App\Enums\SystemRole;
use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

final class ClientAccessService
{
    public function canAccess(User $user, Client $client): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->hasSystemRole(SystemRole::Client)) {
            return $user->client?->is($client) ?? false;
        }

        return $this->scopedQuery($user)
            ->whereKey($client->getKey())
            ->exists();
    }

    public function scopedQuery(User $user): Builder
    {
        if ($user->isSuperAdmin()) {
            return Client::query();
        }

        if ($user->hasAnySystemRole(SystemRole::Admin, SystemRole::AdminInstansi)) {
            return Client::query()->whereExists(function ($query) use ($user): void {
                $query->selectRaw('1')
                    ->from('admin_accesses as aa')
                    ->whereColumn('aa.c_role_id', 'clients.c_role_id')
                    ->where('aa.user_id', $user->id)
                    ->where(function ($scope): void {
                        $scope->whereNull('aa.entity_id')
                            ->orWhere(function ($entity): void {
                                $entity->whereColumn('aa.entity_type', 'clients.agency_type')
                                    ->whereColumn('aa.entity_id', 'clients.agency_id');
                            });
                    });
            });
        }

        if ($user->hasAnySystemRole(
            SystemRole::AdminRegional,
            SystemRole::Verifier,
            SystemRole::AdminPusat,
            SystemRole::AdminSdmBphn,
        )) {
            return Client::query()->whereExists(function ($query) use ($user): void {
                $query->selectRaw('1')
                    ->from('verifier_accesses as va')
                    ->whereColumn('va.c_role_id', 'clients.c_role_id')
                    ->whereColumn('va.entity_type', 'clients.agency_type')
                    ->whereColumn('va.entity_id', 'clients.agency_id')
                    ->where('va.user_id', $user->id);
            });
        }

        return Client::query()->whereRaw('1 = 0');
    }
}
