<?php

namespace App\Enums;

use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum TrainingCompletionStatus: string implements HasLabel, HasColor, HasIcon
{

    case PASSED = "PASSED";
    case FAILED = "FAILED";
    case SATISFACTORY = "SATISFACTORY";

    public function getLabel(): ?string
    {
        return match ($this) {
            self::PASSED => 'Lulus',
            self::FAILED => 'Tidak Lulus',
            self::SATISFACTORY => 'Memuaskan',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::PASSED => Color::Green,
            self::FAILED => Color::Red,
            self::SATISFACTORY => Color::Blue,
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::PASSED => 'heroicon-o-check-circle',
            self::FAILED => 'heroicon-o-x-circle',
            self::SATISFACTORY => 'heroicon-o-minus-circle',
        };
    }
}