<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PointSubmissionPeriod: string implements HasLabel
{
    case Before2023 = 'before2023';
    case StartFrom2023 = 'after2023';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Before2023 => 'Sebelum Tahun 2023',
            self::StartFrom2023 => 'Tahun 2023 dan seterusnya'
        };
    }
}
