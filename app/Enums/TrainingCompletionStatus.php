<?php

namespace App\Enums;

use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum TrainingCompletionStatus: string implements HasLabel, HasColor, HasIcon
{
    case VERY_SATISFACTORY = "VERY_SATISFACTORY";
    case SATISFACTORY = "SATISFACTORY";
    case FAIR = "FAIR";
    case LESS_SATISFACTORY = "LESS_SATISFACTORY";
    case UNSATISFACTORY = "UNSATISFACTORY";

    public function getLabel(): ?string
    {
        return match ($this) {
            self::VERY_SATISFACTORY => 'Sangat Memuaskan',
            self::SATISFACTORY => 'Memuaskan',
            self::FAIR => 'Cukup Memuaskan',
            self::LESS_SATISFACTORY => 'Kurang Memuaskan',
            self::UNSATISFACTORY => 'Tidak Memuaskan',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::VERY_SATISFACTORY => Color::Green,
            self::SATISFACTORY => Color::Emerald,
            self::FAIR => Color::Blue,
            self::LESS_SATISFACTORY => Color::Orange,
            self::UNSATISFACTORY => Color::Red,
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::VERY_SATISFACTORY => 'heroicon-o-check-badge',
            self::SATISFACTORY => 'heroicon-o-check-circle',
            self::FAIR => 'heroicon-o-minus-circle',
            self::LESS_SATISFACTORY => 'heroicon-o-exclamation-circle',
            self::UNSATISFACTORY => 'heroicon-o-x-circle',
        };
    }
}