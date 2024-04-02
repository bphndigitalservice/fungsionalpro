<?php

namespace App\Concerns\Verifier;

interface Verifiable
{
    public function getScopeModel(): string;
}
