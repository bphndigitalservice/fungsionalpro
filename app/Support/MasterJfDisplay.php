<?php

namespace App\Support;

use App\Models\MasterJf;

final class MasterJfDisplay
{
    /** @var list<string> */
    public const JENJANG_LABELS = [
        'Ahli Pertama',
        'Ahli Muda',
        'Ahli Madya',
        'Ahli Utama',
    ];

    /** @var list<string> */
    private const KNOWN_JENJANG = self::JENJANG_LABELS;

    public static function parseJenjangFromJabatan(?string $jabatan): ?string
    {
        if ($jabatan === null || trim($jabatan) === '') {
            return null;
        }

        $parsed = preg_replace('/^(Penyuluh Hukum|Analis Hukum)\s+/i', '', trim($jabatan));

        return self::normalizeJenjangLabel($parsed !== '' ? $parsed : null);
    }

    public static function resolveJenjang(MasterJf $row): ?string
    {
        if ($row->c_role_level_id !== null) {
            $level = $row->relationLoaded('cRoleLevel') ? $row->getRelation('cRoleLevel') : null;
            if ($level?->level) {
                return self::normalizeJenjangLabel($level->level);
            }
        }

        return self::parseJenjangFromJabatan($row->jabatan);
    }

    public static function inferRoleNameFromJabatan(?string $jabatan): ?string
    {
        if ($jabatan === null || trim($jabatan) === '') {
            return null;
        }

        if (stripos($jabatan, 'Analis Hukum') !== false) {
            return 'Analis Hukum';
        }

        if (stripos($jabatan, 'Penyuluh Hukum') !== false) {
            return 'Penyuluh Hukum';
        }

        return null;
    }

    public static function resolveJabatanFungsional(MasterJf $row): ?string
    {
        $role = $row->relationLoaded('cRole') ? $row->getRelation('cRole') : null;

        return $role?->role_name ?? self::inferRoleNameFromJabatan($row->jabatan);
    }

    public static function normalizeJenjangLabel(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $lower = strtolower(trim($value));

        foreach (self::KNOWN_JENJANG as $known) {
            if ($lower === strtolower($known)) {
                return $known;
            }
        }

        return trim($value);
    }

    public static function isPemdaInstansi(?string $instansi): bool
    {
        return $instansi !== null
            && str_contains(strtolower($instansi), 'pemerintah daerah');
    }

    /** @return 'pemda'|'kl' */
    public static function classifyJenisInstansi(?string $instansi): string
    {
        return self::isPemdaInstansi($instansi) ? 'pemda' : 'kl';
    }
}
