<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum UkomDocumentPack: string implements HasLabel
{
    case CalonJf = 'calon_jf';
    case Client = 'client';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::CalonJf => 'Calon JF',
            self::Client => 'Client',
        };
    }
}
