<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum TrainingType: string implements HasLabel
{
    case PROMOTION_TRAINING = 'PROMOTION_TRAINING';
    case TECHNICAL_TRAINING = 'TECHNICAL_TRAINING';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::PROMOTION_TRAINING => 'Kenaikan Jenjang',
            self::TECHNICAL_TRAINING => 'Pelatihan Teknis',
        };
    }
}
