<?php

namespace App\Concerns\Point;

use App\Models\ClientPointSubmission;
use App\Models\ClientPointSubmissionBag;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class SubmissionRule
{
    public static function hasSubmissionActive(): bool
    {
        $submissionBags = Cache::remember('active_submission_bags', 60 * 60, function () {
            return ClientPointSubmissionBag::where('is_enabled', true)->get();
        });
        $activeSubmissionBag = $submissionBags->filter(fn (ClientPointSubmissionBag $value) => Carbon::now()->betweenIncluded(Carbon::make($value->date_start), Carbon::make($value->date_end)));

        return $activeSubmissionBag->count() > 0;
    }

    public static function isExceededMaxSubmission(string $bagId, string $pointSubmissionType, string $client): bool
    {
        return ClientPointSubmission::query()
            ->where('submission_bag_id', $bagId)
            ->where('client_id', $client)
            ->where('submission_type', $pointSubmissionType)
            ->count() > static::getMaximumSubmission();
    }

    protected static function getMaximumSubmission(): int
    {
        return 1;
    }
}
