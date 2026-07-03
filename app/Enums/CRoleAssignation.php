<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum CRoleAssignation: string implements HasLabel
{
    case CPNS = 'cpns';
    case Inpassing = 'inpassing';
    case PDJL = 'pdjl';
    case Penyetaraan = 'penyetaraan';
    case Promosi = 'promosi';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::CPNS => 'Pengangkatan Pertama',
            self::Inpassing => 'Inpassing',
            self::PDJL => 'PDJL',
            self::Penyetaraan => 'Penyetaraan',
            self::Promosi => 'Promosi',
        };
    }
}
