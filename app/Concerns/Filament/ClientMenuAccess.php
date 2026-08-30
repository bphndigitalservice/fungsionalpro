<?php

namespace App\Concerns\Filament;

use App\Enums\SystemRole;
use App\Enums\Verified;
use App\Models\Client;
use App\Models\User;

final class ClientMenuAccess
{
    public static function isClientWithoutSuperAdmin(?User $user = null): bool
    {
        $user ??= auth()->user();

        if ($user === null) {
            return false;
        }

        return $user->hasSystemRole(SystemRole::Client) && ! $user->isSuperAdmin();
    }

    public static function clientProfileIsVerified(?Client $client = null): bool
    {
        $client ??= Client::current();

        return $client !== null && $client->is_verified === Verified::Verified;
    }

    public static function clientMayAccessVerifiedClientMenuItem(?User $user = null, ?Client $client = null): bool
    {
        $user ??= auth()->user();

        if ($user === null) {
            return false;
        }

        if (! static::isClientWithoutSuperAdmin($user)) {
            return true;
        }

        return static::clientProfileIsVerified($client ?? $user->client);
    }

    public static function userMayAccessSuperAdminClientMenuItem(?User $user = null): bool
    {
        $user ??= auth()->user();

        return $user !== null && $user->isSuperAdmin();
    }
}
