<?php

namespace App\Concerns\Filament;

use App\Models\Client;
use Illuminate\Database\Eloquent\Model;

trait ChecksPhotoUpload
{
    public static function shouldRegisterNavigation(): bool
    {
        return static::clientHasPhoto();
    }

    public static function canAccess(): bool
    {
        return static::clientHasPhoto();
    }

    public static function canViewAny(): bool
    {
        return static::clientHasPhoto();
    }

    public static function canView(Model $record): bool
    {
        return static::ownsClientRecord($record);
    }

    public static function canCreate(): bool
    {
        return static::clientHasPhoto();
    }

    public static function canEdit(Model $record): bool
    {
        return static::ownsClientRecord($record);
    }

    public static function canDelete(Model $record): bool
    {
        return static::ownsClientRecord($record);
    }

    protected static function clientHasPhoto(): bool
    {
        $client = Client::current();

        return $client !== null && $client->identity?->photo !== null;
    }

    protected static function ownsClientRecord(Model $record): bool
    {
        $client = Client::current();

        if ($client === null || ! isset($record->client_id)) {
            return false;
        }

        return (string) $record->client_id === (string) $client->id;
    }
}
