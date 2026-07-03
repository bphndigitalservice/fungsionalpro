<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum EducationLevel: string implements HasLabel
{
    case DIPLOMA = 'diploma';
    case BACHELORS = 'bachelors';
    case MASTERS = 'masters';
    case DOCTORATE = 'doctorate';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::DIPLOMA => 'Diploma 4',
            self::BACHELORS => 'Sarjana',
            self::MASTERS => 'Magister',
            self::DOCTORATE => 'Doktor',
        };
    }
}