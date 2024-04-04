<?php

namespace App\Enums;

use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum Verified: int implements HasLabel, HasIcon
{
    case Verified = 1;
    case Unverified = 0;


    public function getLabel(): ?string
    {
        return match ($this) {
            self::Verified => 'Verified',
            self::Unverified => 'Not Verified',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Verified => 'heroicon-o-check-badge',
            self::Unverified => 'heroicon-o-x-circle',
        };
    }
}
