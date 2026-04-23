<?php

namespace App\Policies;

use App\Enums\SystemRole;
use App\Models\ClientActivity;
use App\Models\User;

class ActivityReportPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnySystemRole(SystemRole::Admin, SystemRole::SuperAdmin, SystemRole::Verifier, SystemRole::AdminRegional, SystemRole::AdminPusat);
    }

    public function view(User $user, ClientActivity $clientActivity): bool
    {
        return $user->hasAnySystemRole(SystemRole::Admin, SystemRole::SuperAdmin, SystemRole::Verifier, SystemRole::AdminRegional, SystemRole::AdminPusat);
    }

    public function create(User $user): bool
    {
        return $user->hasAnySystemRole(SystemRole::Client, SystemRole::Admin, SystemRole::SuperAdmin);
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