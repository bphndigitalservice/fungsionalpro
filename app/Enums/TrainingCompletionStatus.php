<?php

namespace App\Enums;

use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum TrainingCompletionStatus: string implements HasLabel, HasColor, HasIcon
{
    case PASSED = "PASSED";
    case EXCELLENT = "EXCELLENT";
    case VERY_SATISFACTORY = "VERY_SATISFACTORY";
    case SATISFACTORY = "SATISFACTORY";
    case LESS_SATISFACTORY = "LESS_SATISFACTORY";
    case UNSATISFACTORY = "UNSATISFACTORY";

    public function getLabel(): ?string
    {
        return match ($this) {
            self::PASSED => 'Lulus',
            self::EXCELLENT => 'Sangat Memuaskan',
            self::VERY_SATISFACTORY => 'Memuaskan',
            self::SATISFACTORY => 'Cukup Memuaskan',
            self::LESS_SATISFACTORY => 'Kurang Memuaskan',
            self::UNSATISFACTORY => 'Tidak Memuaskan',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::PASSED => Color::Green,
            self::EXCELLENT => Color::Green,
            self::VERY_SATISFACTORY => Color::Emerald,
            self::SATISFACTORY => Color::Blue,
            self::LESS_SATISFACTORY => Color::Orange,
            self::UNSATISFACTORY => Color::Red,
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::PASSED => 'heroicon-o-check-circle',
            self::EXCELLENT => 'heroicon-o-check-badge',
            self::VERY_SATISFACTORY => 'heroicon-o-check-circle',
            self::SATISFACTORY => 'heroicon-o-minus-circle',
            self::LESS_SATISFACTORY => 'heroicon-o-exclamation-circle',
            self::UNSATISFACTORY => 'heroicon-o-x-circle',
        };
    }
}
