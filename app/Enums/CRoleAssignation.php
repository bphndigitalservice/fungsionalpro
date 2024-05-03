<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum CRoleAssignation: string implements HasLabel
{
    //CPNS, Inpassing, PDJL, Penyetaraan
    case CPNS = 'cpns';
    case Inpassing = 'inpassing';
    case PDJL = 'pdjl';
    case Penyetaraan = 'penyetaraan';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::CPNS => 'CPNS/PPPK',
            self::Inpassing => 'Inpassing',
            self::PDJL => 'PDJL',
            self::Penyetaraan => 'Penyetaraan',
        };
    }
}
