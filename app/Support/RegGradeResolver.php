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

        // Try extracting potential code from format like (III/d)
        $extractedCode = null;
        if (preg_match('/\((.*?)\)/', $raw, $matches)) {
            $extractedCode = str_replace('/', '', $matches[1]);
        }

        $rawNormalized = str_replace(['/', '-'], '', $raw);
        $rawLower = strtolower($rawNormalized);

        $grades = once(fn () => RegGrade::query()->get());

        // First attempt: match by extracted code
        if ($extractedCode) {
            $grade = $grades->first(function (RegGrade $g) use ($extractedCode) {
                return strtolower(str_replace(['/', '-'], '', trim((string) $g->grade_code))) === strtolower($extractedCode);
            });
            if ($grade !== null) {
                return $grade->id;
            }
        }

        $grade = $grades->first(function (RegGrade $g) use ($rawLower) {
            $code = str_replace(['/', '-'], '', trim((string) $g->grade_code));

            return $code !== '' && strtolower($code) === $rawLower;
        });
        if ($grade !== null) {
            return $grade->id;
        }

        $grade = $grades->first(function (RegGrade $g) use ($rawLower) {
            $name = str_replace(['/', '-'], '', trim((string) $g->grade_name));

            return $name !== '' && strtolower($name) === $rawLower;
        });
        if ($grade !== null) {
            return $grade->id;
        }
        // ... rest of the function

        $grade = $grades
            ->filter(function (RegGrade $g) use ($rawNormalized) {
                $code = str_replace(['/', '-'], '', trim((string) $g->grade_code));

                return $code !== '' && stripos($rawNormalized, $code) !== false;
            })
            ->sortByDesc(fn (RegGrade $g) => strlen(str_replace(['/', '-'], '', trim((string) $g->grade_code))))
            ->first();
        if ($grade !== null) {
            return $grade->id;
        }

        $grade = $grades
            ->filter(function (RegGrade $g) use ($rawNormalized) {
                $name = str_replace(['/', '-'], '', trim((string) $g->grade_name));

                return $name !== '' && stripos($rawNormalized, $name) !== false;
            })
            ->sortByDesc(fn (RegGrade $g) => strlen(str_replace(['/', '-'], '', trim((string) $g->grade_name))))
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
