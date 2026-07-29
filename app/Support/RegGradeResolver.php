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
        $rawLower = strtolower($raw);

        $grade = $grades->first(function (RegGrade $g) use ($rawLower) {
            $code = trim((string) $g->grade_code);

            return $code !== '' && strtolower($code) === $rawLower;
        });
        if ($grade !== null) {
            return $grade->id;
        }

        $grade = $grades->first(function (RegGrade $g) use ($rawLower) {
            $name = trim((string) $g->grade_name);

            return $name !== '' && strtolower($name) === $rawLower;
        });
        if ($grade !== null) {
            return $grade->id;
        }

        $grade = $grades
            ->filter(function (RegGrade $g) use ($raw) {
                $code = trim((string) $g->grade_code);

                return $code !== '' && stripos($raw, $code) !== false;
            })
            ->sortByDesc(fn (RegGrade $g) => strlen(trim((string) $g->grade_code)))
            ->first();
        if ($grade !== null) {
            return $grade->id;
        }

        $grade = $grades
            ->filter(function (RegGrade $g) use ($raw) {
                $name = trim((string) $g->grade_name);

                return $name !== '' && stripos($raw, $name) !== false;
            })
            ->sortByDesc(fn (RegGrade $g) => strlen(trim((string) $g->grade_name)))
            ->first();

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
