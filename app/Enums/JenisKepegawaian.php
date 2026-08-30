<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum JenisKepegawaian: string implements HasLabel
{
    case PNS = 'PNS';
    case PPPK = 'PPPK';

    public function getLabel(): ?string
    {
        return $this->value;
    }
}
