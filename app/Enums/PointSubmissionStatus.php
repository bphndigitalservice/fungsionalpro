<?php

namespace App\Enums;

use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum PointSubmissionStatus: string implements HasColor, HasIcon, HasLabel
{
    case Submitted = 'submitted';
    case Verified = 'verified';
    case ShouldRevise = 'should-revise';

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Verified => Color::Green,
            self::Submitted => Color::Neutral,
            self::ShouldRevise => Color::Orange,
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Verified => 'heroicon-o-check-badge',
            self::Submitted => 'heroicon-o-clock',
            self::ShouldRevise => 'heroicon-o-clock',
        };

    }

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Verified => __('Sudah diverifikasi.'),
            self::Submitted => __('Terkirim'),
            self::ShouldRevise => __('Perlu Perbaikan'),
        };
    }
}
