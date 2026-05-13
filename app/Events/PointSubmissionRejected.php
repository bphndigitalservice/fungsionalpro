<?php

namespace App\Events;

use App\Models\ClientPointSubmission;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PointSubmissionRejected
{
    use Dispatchable, SerializesModels;

    public function __construct(
        protected $pointSubmission,
        protected string $verifierNotes
    ) {}

    public function getPointSubmission()
    {
        return $this->pointSubmission;
    }

    public function getVerifierNotes(): string
    {
        return $this->verifierNotes;
    }
}