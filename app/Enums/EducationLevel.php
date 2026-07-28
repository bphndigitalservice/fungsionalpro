<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum EducationLevel: string implements HasLabel
{
    case ELEMENTARY = 'elementary';
    case JUNIOR_HIGH = 'junior_high';
    case SENIOR_HIGH = 'senior_high';
    case DIPLOMA = 'diploma';
    case BACHELORS = 'bachelors';
    case MASTERS = 'masters';
    case DOCTORATE = 'doctorate';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::ELEMENTARY => 'Sekolah Dasar',
            self::JUNIOR_HIGH => 'Sekolah Menengah Pertama',
            self::SENIOR_HIGH => 'Sekolah Menengah Atas',
            self::DIPLOMA => 'Diploma 4',
            self::BACHELORS => 'Sarjana',
            self::MASTERS => 'Magister',
            self::DOCTORATE => 'Doktor',
        };
    }
}
