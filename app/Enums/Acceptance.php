<?php

namespace App\Enums;

use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum Acceptance: int implements HasColor, HasIcon, HasLabel
{
    case Accept = 1;
    case Reject = 0;

    //
    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Accept => Color::Green,
            self::Reject => Color::Red,
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Accept => 'heroicon-o-check-badge',
            self::Reject => 'heroicon-o-x-mark',
        };
    }

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Accept => 'Accept',
            self::Reject => 'Reject',
        };
    }
}
