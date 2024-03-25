<?php

namespace App\Models\Enums;

use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;


enum ClientCluster: string implements HasLabel, HasColor
{

    case Central = "central";
    case LocalProvince = "local_province";
    case LocalRegency = "local_regency";

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
            self::Central => 'Pusat',
            self::LocalProvince => 'Pemda - Provinsi',
            self::LocalRegency => 'Pemda - Kabupaten/Kota',
        };
    }
}
