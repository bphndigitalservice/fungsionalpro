<?php

namespace App\Concerns\Filament;

use App\Models\Client;
use Illuminate\Database\Eloquent\Model;

trait ChecksPhotoUpload
{
    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function canAccess(): bool
    {
        $client = Client::current();
        if ($client) {
            return $client->identity?->photo !== null;
        }
        return true;
    }

    public static function canViewAny(): bool
    {
        return static::canAccess();
    }

    public static function canView(Model $record): bool
    {
        return static::canAccess();
    }

    public static function canCreate(): bool
    {
        return static::canAccess();
    }

    public static function canEdit(Model $record): bool
    {
        return static::canAccess();
    }

    public static function canDelete(Model $record): bool
    {
        return static::canAccess();
    }
}
