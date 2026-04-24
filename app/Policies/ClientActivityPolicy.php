<?php

namespace App\Policies;

use App\Enums\SystemRole;
use App\Models\ClientActivity;
use App\Models\User;

class ClientActivityPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnySystemRole(SystemRole::Admin, SystemRole::SuperAdmin, SystemRole::Verifier, SystemRole::AdminRegional, SystemRole::AdminPusat, SystemRole::AdminInstansi);
    }

    public function view(User $user, ClientActivity $clientActivity): bool
    {
        if ($user->hasAnySystemRole(SystemRole::Admin, SystemRole::SuperAdmin, SystemRole::AdminInstansi)) {
            return true;
        }

        if ($user->hasAnySystemRole(SystemRole::Verifier, SystemRole::AdminRegional, SystemRole::AdminPusat)) {
            return true;
        }

        return $user->client?->id === $clientActivity->client_id;
    }

    public function create(User $user): bool
    {
        return $user->hasAnySystemRole(SystemRole::Client, SystemRole::Admin, SystemRole::SuperAdmin, SystemRole::AdminInstansi);
    }

    public function update(User $user, ClientActivity $clientActivity): bool
    {
        if ($user->hasAnySystemRole(SystemRole::Admin, SystemRole::SuperAdmin)) {
            return true;
        }

        return $user->client?->id === $clientActivity->client_id;
    }

    public function delete(User $user, ClientActivity $clientActivity): bool
    {
        return $user->hasAnySystemRole(SystemRole::Admin, SystemRole::SuperAdmin);
    }

    public function deleteAny(User $user): bool
    {
        return $user->hasAnySystemRole(SystemRole::Admin, SystemRole::SuperAdmin);
    }
}