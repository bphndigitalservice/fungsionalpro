<?php

namespace App\Support;

use App\Enums\ClientCluster;
use App\Enums\ClientStatus;
use App\Enums\JenisKepegawaian;

final class MasterJfEnumMapper
{
    /** @return array<string, string> label/value → enum value */
    private static function statusMap(): array
    {
        $map = [];
        foreach (ClientStatus::cases() as $case) {
            $map[$case->value] = $case->value;
            $map[$case->getLabel()] = $case->value;
        }

        return $map;
    }

    /** @return array<string, string> synonym/value → enum value */
    private static function typeMap(): array
    {
        return [
            ClientCluster::Central->value => ClientCluster::Central->value,
            ClientCluster::Central->getLabel() => ClientCluster::Central->value,
            'Pusat' => ClientCluster::Central->value,
            ClientCluster::LocalProvince->value => ClientCluster::LocalProvince->value,
            ClientCluster::LocalProvince->getLabel() => ClientCluster::LocalProvince->value,
            'Provinsi' => ClientCluster::LocalProvince->value,
            ClientCluster::LocalRegency->value => ClientCluster::LocalRegency->value,
            ClientCluster::LocalRegency->getLabel() => ClientCluster::LocalRegency->value,
            'Kab/Kota' => ClientCluster::LocalRegency->value,
        ];
    }

    public static function status(?string $raw): ?string
    {
        if ($raw === null || trim($raw) === '') {
            return null;
        }

        return self::statusMap()[trim($raw)] ?? null;
    }

    public static function type(?string $raw): ?string
    {
        if ($raw === null || trim($raw) === '') {
            return null;
        }

        return self::typeMap()[trim($raw)] ?? null;
    }

    public static function statusKepegawaian(?string $raw): ?string
    {
        if ($raw === null || trim($raw) === '') {
            return null;
        }

        $trimmed = trim($raw);

        return JenisKepegawaian::tryFrom($trimmed)?->value;
    }
}
