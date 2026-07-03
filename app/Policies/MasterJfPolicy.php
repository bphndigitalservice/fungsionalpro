<?php

namespace App\Policies;

use App\Models\MasterJf;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use App\Enums\SystemRole;

class MasterJfPolicy
{

    public function viewAny(User $user): bool
    {
        return $user->hasAnySystemRole(SystemRole::Admin, SystemRole::SuperAdmin, SystemRole::Verifier, SystemRole::AdminRegional, SystemRole::AdminPusat, SystemRole::AdminInstansi);
    }

    public function view(User $user, MasterJf $masterJf): bool
    {
        return $user->hasAnySystemRole(SystemRole::Admin, SystemRole::SuperAdmin, SystemRole::Verifier, SystemRole::AdminRegional, SystemRole::AdminPusat, SystemRole::AdminInstansi);
    }

    public function create(User $user): bool
    {
        return $user->hasAnySystemRole(SystemRole::Client, SystemRole::Admin, SystemRole::SuperAdmin, SystemRole::AdminInstansi);
    }

    public function update(User $user, MasterJf $masterJf): bool
    {
        if ($user->hasAnySystemRole(SystemRole::Admin, SystemRole::SuperAdmin)) {
            return true;
        }

        return $user->client?->id === $masterJf->client_id;
    }
 
    public function delete(User $user, MasterJf $masterJf): bool
    {
        return $user->hasAnySystemRole(SystemRole::Admin, SystemRole::SuperAdmin);
    }
 
    public function deleteAny(User $user): bool
    {
        return $user->hasAnySystemRole(SystemRole::Admin, SystemRole::SuperAdmin);
    }
}
