<?php

namespace App\Enums;

use App\Models\RegDepartment;
use App\Models\RegProvince;
use App\Models\RegRegency;
use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ClientClusterMorphFilter: string implements HasColor, HasLabel
{
    case Central = RegDepartment::class;
    case LocalProvince = RegProvince::class;
    case LocalRegency = RegRegency::class;

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Central => Color::Fuchsia,
            self::LocalProvince => Color::Emerald,
            self::LocalRegency => Color::Violet,
        };
    }

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Central => 'Kementerian Lembaga',
            self::LocalProvince => 'Pemda - Provinsi',
            self::LocalRegency => 'Pemda - Kabupaten/Kota',
        };
    }
}
