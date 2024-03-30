<?php

namespace App\Enums;

use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum Gender: string implements HasIcon, HasLabel
{
    case Male = 'male';
    case Female = 'female';

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Male => 'phosphor-gender-male-light',
            self::Female => 'phosphor-gender-female-light',
        };
    }

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Male => 'Laki-laki',
            self::Female => 'Perempuan',
        };
    }
}
