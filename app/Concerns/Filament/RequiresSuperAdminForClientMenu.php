<?php

namespace App\Concerns\Filament;

trait RequiresSuperAdminForClientMenu
{
    public static function shouldRegisterNavigation(array $parameters = []): bool
    {
        return ClientMenuAccess::userMayAccessSuperAdminClientMenuItem();
    }

    public static function canAccess(array $parameters = []): bool
    {
        return ClientMenuAccess::userMayAccessSuperAdminClientMenuItem();
    }

    public static function canViewAny(): bool
    {
        return ClientMenuAccess::userMayAccessSuperAdminClientMenuItem();
    }
}
