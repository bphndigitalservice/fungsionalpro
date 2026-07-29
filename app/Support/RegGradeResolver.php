<?php

namespace App\Support;

use App\Models\RegGrade;
use Illuminate\Support\Facades\DB;

final class RegGradeResolver
{
    public static function resolveId(?string $raw): ?int
    {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return null;
        }

        $grades = once(fn () => RegGrade::query()->get());

        $grade = $grades->first(function (RegGrade $g) use ($raw) {
            $matchCode = ! empty($g->grade_code) && stripos($raw, $g->grade_code) !== false;
            $matchName = ! empty($g->grade_name) && stripos($raw, $g->grade_name) !== false;

            return $matchCode || $matchName;
        });

        return $grade?->id;
    }

    public static function backfillMasterJf(): void
    {
        DB::table('master_jf')
            ->whereNull('reg_grade_id')
            ->whereNotNull('gol_ruang')
            ->where('gol_ruang', '!=', '')
            ->orderBy('id')
            ->chunkById(200, function ($rows) {
                foreach ($rows as $row) {
                    $id = self::resolveId($row->gol_ruang);
                    if ($id === null) {
                        continue;
                    }

                    DB::table('master_jf')
                        ->where('id', $row->id)
                        ->update(['reg_grade_id' => $id]);
                }
            });
    }
}
