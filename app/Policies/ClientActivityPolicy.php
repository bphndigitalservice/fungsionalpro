<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ClientActivity;
use Illuminate\Auth\Access\HandlesAuthorization;

class ClientActivityPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_client::activity');
    }

    public function view(User $user, ClientActivity $clientActivity): bool
    {
        return $user->can('view_client::activity');
    }

    public function create(User $user): bool
    {
        return $user->can('create_client::activity');
    }

    public function update(User $user, ClientActivity $clientActivity): bool
    {
        return $user->can('update_client::activity');
    }

    public function delete(User $user, ClientActivity $clientActivity): bool
    {
        return $user->can('delete_client::activity');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_client::activity');
    }

    public function forceDelete(User $user, ClientActivity $clientActivity): bool
    {
        return $user->can('force_delete_client::activity');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_client::activity');
    }

    public function restore(User $user, ClientActivity $clientActivity): bool
    {
        return $user->can('restore_client::activity');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_client::activity');
    }

    public function replicate(User $user, ClientActivity $clientActivity): bool
    {
        return $user->can('replicate_client::activity');
    }

    public function reorder(User $user): bool
    {
        return $user->can('reorder_client::activity');
    }
}