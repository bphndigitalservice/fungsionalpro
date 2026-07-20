<?php

namespace App\Policies;

use App\Enums\SystemRole;
use App\Models\Client;
use App\Models\User;
use App\Services\ClientAccessService;
use Illuminate\Auth\Access\HandlesAuthorization;

class ClientPolicy
{
    use HandlesAuthorization;

    public function __construct(
        private readonly ClientAccessService $clientAccess,
    ) {}

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_client');
    }

    public function view(User $user, Client $client): bool
    {
        return $user->can('view_client') && $this->clientAccess->canAccess($user, $client);
    }

    public function create(User $user): bool
    {
        return $user->can('create_client');
    }

    public function update(User $user, Client $client): bool
    {
        if (! $user->can('update_client')) {
            return false;
        }

        // Clients may only update their own record.
        if ($user->hasSystemRole(SystemRole::Client)) {
            return $user->client?->is($client) ?? false;
        }

        return $this->clientAccess->canAccess($user, $client);
    }

    public function delete(User $user, Client $client): bool
    {
        return $user->can('delete_client') && $this->clientAccess->canAccess($user, $client);
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_client');
    }

    public function forceDelete(User $user, Client $client): bool
    {
        return $user->can('force_delete_client') && $this->clientAccess->canAccess($user, $client);
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_client');
    }

    public function restore(User $user, Client $client): bool
    {
        return $user->can('restore_client') && $this->clientAccess->canAccess($user, $client);
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_client');
    }

    public function replicate(User $user, Client $client): bool
    {
        return $user->can('replicate_client') && $this->clientAccess->canAccess($user, $client);
    }

    public function reorder(User $user): bool
    {
        return $user->can('reorder_client');
    }
}
