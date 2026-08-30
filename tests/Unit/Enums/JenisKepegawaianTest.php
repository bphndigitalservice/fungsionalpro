<?php

namespace Tests\Unit\Enums;

use App\Enums\JenisKepegawaian;
use PHPUnit\Framework\TestCase;

class JenisKepegawaianTest extends TestCase
{
    public function test_cases_and_labels(): void
    {
        $this->assertSame('PNS', JenisKepegawaian::PNS->value);
        $this->assertSame('PPPK', JenisKepegawaian::PPPK->value);
        $this->assertSame('PNS', JenisKepegawaian::PNS->getLabel());
        $this->assertSame('PPPK', JenisKepegawaian::PPPK->getLabel());
    }
}
