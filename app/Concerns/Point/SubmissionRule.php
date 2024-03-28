<?php

namespace App\Concerns\Point;

use App\Models\ClientPointSubmission;
use App\Models\ClientPointSubmissionBag;
use Carbon\Carbon;

class SubmissionRule
{
    public static function hasSubmissionActive(): bool
    {
        $submissionBags = ClientPointSubmissionBag::where('is_active', true)->get();
        $activeSubmissionBag = $submissionBags->filter(fn(ClientPointSubmission $value) => Carbon::now()->between($value->date_start, $value->date_end));

        return count($activeSubmissionBag);
    }
}
