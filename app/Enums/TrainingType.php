<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum TrainingType: string implements HasLabel
{
    case PROMOTION_TRAINING = 'PROMOTION_TRAINING';
    case TECHNICAL_TRAINING = 'TECHNICAL_TRAINING';
    case OTHER_TRAINING = 'OTHER_TRAINING';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::PROMOTION_TRAINING => 'Diklat Fungsional',
            self::TECHNICAL_TRAINING => 'Pelatihan Teknis',
            self::OTHER_TRAINING => 'Bimtek/Seminar/Workshop/dll',
        };
    }
}
