<?php

namespace App\Models\Enums;

use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum ClientStatus: string implements HasLabel, HasIcon, HasColor
{

    case Active = "active";
    case NonActive = "non_active";

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Active => 'Aktif',
            self::NonActive => 'Tidak Aktif',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Active => 'heroicon-o-check-circle',
            self::NonActive => 'heroicon-o-x-circle',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Active => Color::Blue,
            self::NonActive => Color::Red,
        };
    }
}
