<?php

namespace App\Rules;

use App\Models\CalonJf;
use App\Models\Client;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UniqueNip implements ValidationRule
{
    public function __construct(
        private readonly ?int $ignoreUserId = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return;
        }

        $nip = (string) $value;

        $clientExists = Client::query()
            ->where('nip', $nip)
            ->when($this->ignoreUserId, fn ($query) => $query->where('user_id', '!=', $this->ignoreUserId))
            ->exists();

        $calonExists = CalonJf::query()
            ->where('nip', $nip)
            ->when($this->ignoreUserId, fn ($query) => $query->where('user_id', '!=', $this->ignoreUserId))
            ->exists();

        if ($clientExists || $calonExists) {
            $fail('NIP sudah terdaftar.');
        }
    }
}
