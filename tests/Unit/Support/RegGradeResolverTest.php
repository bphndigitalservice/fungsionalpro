<?php

namespace Tests\Unit\Support;

use App\Models\RegGrade;
use App\Support\RegGradeResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegGradeResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolves_by_grade_code(): void
    {
        $grade = RegGrade::create(['grade_name' => 'Penata', 'grade_code' => 'III/a']);

        $this->assertSame($grade->id, RegGradeResolver::resolveId('III/a'));
    }

    public function test_resolves_formatted_label(): void
    {
        $grade = RegGrade::create(['grade_name' => 'Penata', 'grade_code' => 'III/a']);

        $this->assertSame($grade->id, RegGradeResolver::resolveId('Penata (III/a)'));
    }

    public function test_returns_null_for_unknown(): void
    {
        RegGrade::create(['grade_name' => 'Penata', 'grade_code' => 'III/a']);

        $this->assertNull(RegGradeResolver::resolveId('ZZ/z'));
        $this->assertNull(RegGradeResolver::resolveId(null));
        $this->assertNull(RegGradeResolver::resolveId(''));
    }

    public function test_prefers_longest_code_over_shorter_substring(): void
    {
        RegGrade::create(['grade_name' => 'Juru Muda', 'grade_code' => 'I/a']);
        RegGrade::create(['grade_name' => 'Pengatur Muda', 'grade_code' => 'II/a']);
        $gradeIii = RegGrade::create(['grade_name' => 'Penata Muda', 'grade_code' => 'III/a']);

        $this->assertSame($gradeIii->id, RegGradeResolver::resolveId('III/a'));
    }

    public function test_prefers_longest_code_without_slash(): void
    {
        RegGrade::create(['grade_name' => 'Juru Muda', 'grade_code' => 'Ia']);
        RegGrade::create(['grade_name' => 'Pengatur Muda', 'grade_code' => 'IIa']);
        $gradeIii = RegGrade::create(['grade_name' => 'Penata Muda', 'grade_code' => 'IIIa']);

        $this->assertSame($gradeIii->id, RegGradeResolver::resolveId('IIIa'));
    }
}
