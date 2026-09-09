<?php

namespace App\Concerns\Filament;

trait GatesVerifiedClientOwnRecords
{
    use ChecksPhotoUpload {
        shouldRegisterNavigation as private checksPhotoShouldRegisterNavigation;
        canAccess as private checksPhotoCanAccess;
        canViewAny as private checksPhotoCanViewAny;
        canCreate as private checksPhotoCanCreate;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return ClientMenuAccess::clientMayAccessVerifiedClientMenuItem()
            && static::checksPhotoShouldRegisterNavigation();
    }

    public static function canAccess(): bool
    {
        return ClientMenuAccess::clientMayAccessVerifiedClientMenuItem()
            && static::checksPhotoCanAccess();
    }

    public static function canViewAny(): bool
    {
        return ClientMenuAccess::clientMayAccessVerifiedClientMenuItem()
            && static::checksPhotoCanViewAny();
    }

    public static function canCreate(): bool
    {
        return ClientMenuAccess::clientMayAccessVerifiedClientMenuItem()
            && static::checksPhotoCanCreate();
    }

    // canView / canEdit / canDelete remain from ChecksPhotoUpload (ownership)
}
