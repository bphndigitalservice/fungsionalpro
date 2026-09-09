<?php

namespace App\Enums;

use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum UkomApplicationStatus: string implements HasColor, HasLabel
{
    case Draft = 'draft';
    case PendingInstansi = 'pending_instansi';
    case PendingAdmin = 'pending_admin';
    case Accepted = 'accepted';
    case Rejected = 'rejected';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::PendingInstansi => 'Menunggu verifikasi instansi',
            self::PendingAdmin => 'Menunggu verifikasi instansi pembina',
            self::Accepted => 'Diterima',
            self::Rejected => 'Ditolak',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Draft => Color::Gray,
            self::PendingInstansi => Color::Amber,
            self::PendingAdmin => Color::Blue,
            self::Accepted => Color::Green,
            self::Rejected => Color::Red,
        };
    }

    public function getBadgeColor(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::PendingInstansi => 'warning',
            self::PendingAdmin => 'info',
            self::Accepted => 'success',
            self::Rejected => 'danger',
        };
    }

    public function isOpen(): bool
    {
        return in_array($this, [self::Draft, self::PendingInstansi, self::PendingAdmin], true);
    }

    /**
     * @return list<self>
     */
    public static function openCases(): array
    {
        return [self::Draft, self::PendingInstansi, self::PendingAdmin];
    }
}
