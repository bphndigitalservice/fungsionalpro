<?php

namespace App\Policies;

use App\Models\ClientActivity;
use App\Models\User;

class ActivityReportPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['admin', 'super_admin', 'verifier', 'admin-regional', 'admin-pusat']);
    }

    public function view(User $user, ClientActivity $clientActivity): bool
    {
        return $user->hasRole(['admin', 'super_admin', 'verifier', 'admin-regional', 'admin-pusat']);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['client', 'admin', 'super_admin']);
    }

    public function update(User $user, ClientActivity $clientActivity): bool
    {
        if ($user->hasRole(['admin', 'super_admin'])) {
            return true;
        }

        return $user->client?->id === $clientActivity->client_id;
    }

    public function delete(User $user, ClientActivity $clientActivity): bool
    {
        return $user->hasRole(['admin', 'super_admin']);
    }

    public function deleteAny(User $user): bool
    {
        return $user->hasRole(['admin', 'super_admin']);
    }
}