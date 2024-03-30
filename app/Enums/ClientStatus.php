<?php

namespace App\Enums;

use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum ClientStatus: string implements HasColor, HasIcon, HasLabel
{
    case Active = 'active';
    case NonActive = 'non_active';
    case TemporarilyNonActive = 'temporarily_nonactive';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Active => 'Aktif',
            self::NonActive => 'Tidak Aktif',
            self::TemporarilyNonActive => 'Berhenti Sementara'
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Active => 'heroicon-o-check-circle',
            self::NonActive => 'heroicon-o-x-circle',
            self::TemporarilyNonActive => 'heroicon-o-clock'
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Active => Color::Blue,
            self::NonActive => Color::Red,
            self::TemporarilyNonActive => Color::Orange
        };
    }
}
