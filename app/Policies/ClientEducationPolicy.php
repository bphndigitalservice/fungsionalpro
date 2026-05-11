<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ClientEducation;
use Illuminate\Auth\Access\HandlesAuthorization;

class ClientEducationPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_client::education');
    }

    public function view(User $user, ClientEducation $clientEducation): bool
    {
        return $user->can('view_client::education');
    }

    public function create(User $user): bool
    {
        return $user->can('create_client::education');
    }

    public function update(User $user, ClientEducation $clientEducation): bool
    {
        return $user->can('update_client::education');
    }

    public function delete(User $user, ClientEducation $clientEducation): bool
    {
        return $user->can('delete_client::education');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_client::education');
    }

    public function forceDelete(User $user, ClientEducation $clientEducation): bool
    {
        return $user->can('force_delete_client::education');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_client::education');
    }

    public function restore(User $user, ClientEducation $clientEducation): bool
    {
        return $user->can('restore_client::education');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_client::education');
    }

    public function replicate(User $user, ClientEducation $clientEducation): bool
    {
        return $user->can('replicate_client::education');
    }

    public function reorder(User $user): bool
    {
        return $user->can('reorder_client::education');
    }
}