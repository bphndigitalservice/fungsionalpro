<?php

namespace App\Models;

use App\Enums\Acceptance;
use App\Enums\PointSubmissionPeriod;
use App\Enums\PointSubmissionStatus;
use App\Events\PointSubmissionAccepted;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClientPointSubmission extends Model
{
    use HasFactory, HasUlids;

    protected $primaryKey = 'id';

    protected $casts = [
        'submission_type' => PointSubmissionPeriod::class,
        'status' => PointSubmissionStatus::class,
        'is_approved' => Acceptance::class,
    ];

    public function files(): HasMany
    {
        return $this->hasMany(ClientPointSubmissionFile::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function bag(): BelongsTo
    {
        return $this->belongsTo(ClientPointSubmissionBag::class, 'submission_bag_id', 'id');
    }

    protected function unprocessedLabel(): string
    {
        return 'Menunggu Verifikasi';
    }

    private function prepareVerificationData(bool $isApproved): array
    {
        return [
            'is_approved' => $isApproved,
            'verifier_note' => null,
            'verified_at' => now(),
            'status' => PointSubmissionStatus::Verified->value,
        ];
    }

    public function verify(bool $accept, ?string $note): void
    {
        if ($accept) {
            $this->accept($note);

            return;
        }

        $this->reject($note);
    }

    public function accept(?string $note): void
    {
        $data = $this->prepareVerificationData(true);

        if (! is_null($note)) {
            $data['verifier_note'] = $note;
        }

        $this->update($data);

        event(new PointSubmissionAccepted($this));
    }

    public function reject(string $note): void
    {
        $data = $this->prepareVerificationData(false);
        $data['verifier_note'] = $note;
        unset($data['verified_at']);
        $data['status'] = PointSubmissionStatus::ShouldRevise;

        $this->update($data);
    }
}
