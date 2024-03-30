<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PointSubmissionType: string implements HasLabel
{
    case Inpassing = 'inpassing';
    case Penyetaraan = 'penyetaraan';
    case Permenkumham1Tahun2023 = 'permenkumham_1_2023';
    case SKP = 'skp';

    //
    public function getLabel(): ?string
    {
        return match ($this) {
            self::Inpassing => 'Inpassing',
            self::Penyetaraan => 'Penyetaraan',
            self::Permenkumham1Tahun2023 => 'Permenkumham No. 1 Tahun 2023',
            self::SKP => 'Konversi SKP'
        };
    }
}
