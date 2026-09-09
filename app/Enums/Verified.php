<?php

namespace App\Enums;

use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum Verified: int implements HasColor, HasIcon, HasLabel
{
    case Verified = 1;
    case Unverified = 0;
    case Rejected = 2;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Verified => 'Verified',
            self::Unverified => 'Not Verified',
            self::Rejected => 'Rejected',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Verified => 'heroicon-o-check-badge',
            self::Unverified => 'heroicon-o-clock',
            self::Rejected => 'heroicon-o-x-circle',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Verified => Color::Green,
            self::Unverified => Color::Gray,
            self::Rejected => Color::Red,
        };
    }
}
