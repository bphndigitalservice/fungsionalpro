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
}
