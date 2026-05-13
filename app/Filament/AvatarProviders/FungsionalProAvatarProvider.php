<?php

namespace App\Filament\AvatarProviders;

use Filament\AvatarProviders\Contracts\AvatarProvider;
use Filament\Facades\Filament;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class FungsionalProAvatarProvider implements AvatarProvider
{

    public function get(Model|Authenticatable $record): string
    {
        if ($record->isActiveClient() && !is_null($record->client)) {
            $url = $record->client->identity->photo_url;
            return $url ?? $this->getFallbackAvatarUrl($record);
        }

        return $this->getFallbackAvatarUrl($record);
    }

    public function getFallbackAvatarUrl(Model|Authenticatable $record): string
    {
        $name = str(Filament::getNameForDefaultAvatar($record))
            ->trim()
            ->explode(' ')
            ->map(fn(string $segment): string => filled($segment) ? mb_substr($segment, 0, 1) : '')
            ->join(' ');

        return 'https://ui-avatars.com/api/?background=4e46e5&color=fff&name=' . urlencode($name);
    }

}
