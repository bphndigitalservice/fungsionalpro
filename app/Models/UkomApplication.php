<?php

namespace App\Models;

use App\Enums\ClientCluster;
use App\Enums\UkomApplicationStatus;
use App\Enums\UkomDocumentPack;
use App\Enums\Verified;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class UkomApplication extends Model
{
    use HasFactory;
    use HasUlids;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'status' => UkomApplicationStatus::class,
            'type' => ClientCluster::class,
            'claims_new_degree' => 'boolean',
            'instansi_reviewed_at' => 'datetime',
            'admin_reviewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function targetCRole(): BelongsTo
    {
        return $this->belongsTo(CRole::class, 'target_c_role_id');
    }

    public function instansiReviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instansi_reviewed_by');
    }

    public function adminReviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_reviewed_by');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(UkomApplicationDocument::class);
    }

    public function agenciable(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'agency_type', 'agency_id');
    }

    public function pack(): UkomDocumentPack
    {
        if ($this->user?->client !== null) {
            return UkomDocumentPack::Client;
        }

        return UkomDocumentPack::CalonJf;
    }

    public function isOpen(): bool
    {
        return $this->status->isOpen();
    }

    public function instansiDecisionDisplay(): ?string
    {
        if ($this->instansi_reviewed_at === null) {
            return null;
        }

        $rejectedHere = $this->status === UkomApplicationStatus::Rejected
            && $this->admin_reviewed_at === null;

        $label = $rejectedHere ? 'Ditolak' : 'Diteruskan';

        return $label.' · '.$this->instansi_reviewed_at->format('d/m/Y H:i');
    }

    public function pembinaDecisionDisplay(): ?string
    {
        if ($this->admin_reviewed_at === null) {
            return null;
        }

        $label = $this->status === UkomApplicationStatus::Accepted ? 'Diterima' : 'Ditolak';

        return $label.' · '.$this->admin_reviewed_at->format('d/m/Y H:i');
    }

    /**
     * @return list<array{label: string, state: 'done'|'current'|'upcoming'|'failed'}>
     */
    public function progressSteps(): array
    {
        $status = $this->status;
        $rejectedAtInstansi = $status === UkomApplicationStatus::Rejected
            && ($this->instansi_reviewed_at !== null)
            && $this->admin_reviewed_at === null;
        $rejectedAtPembina = $status === UkomApplicationStatus::Rejected
            && $this->admin_reviewed_at !== null;
        $rejectedWithoutReviewer = $status === UkomApplicationStatus::Rejected
            && $this->instansi_reviewed_at === null
            && $this->admin_reviewed_at === null;

        return [
            [
                'label' => 'Instansi',
                'state' => match (true) {
                    $status === UkomApplicationStatus::PendingInstansi => 'current',
                    $rejectedAtInstansi, $rejectedWithoutReviewer => 'failed',
                    in_array($status, [
                        UkomApplicationStatus::PendingAdmin,
                        UkomApplicationStatus::Accepted,
                    ], true) || $rejectedAtPembina => 'done',
                    default => 'upcoming',
                },
            ],
            [
                'label' => 'Instansi pembina',
                'state' => match (true) {
                    $status === UkomApplicationStatus::PendingAdmin => 'current',
                    $rejectedAtPembina => 'failed',
                    $status === UkomApplicationStatus::Accepted => 'done',
                    default => 'upcoming',
                },
            ],
            [
                'label' => 'Keputusan',
                'state' => match (true) {
                    $status === UkomApplicationStatus::Accepted => 'done',
                    $status === UkomApplicationStatus::Rejected => 'failed',
                    default => 'upcoming',
                },
            ],
        ];
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn(
            'status',
            array_map(fn (UkomApplicationStatus $status) => $status->value, UkomApplicationStatus::openCases()),
        );
    }

    public static function userHasOpen(int $userId, ?string $exceptId = null): bool
    {
        return static::query()
            ->where('user_id', $userId)
            ->open()
            ->when($exceptId, fn (Builder $query) => $query->whereKeyNot($exceptId))
            ->exists();
    }

    public static function snapshotFromUser(User $user): array
    {
        if ($user->client !== null) {
            $client = $user->client;
            $identity = $client->identity;

            return [
                'nip' => $client->nip,
                'nama' => $identity?->name ?? $user->name,
                'type' => $client->type,
                'agency_type' => $client->agency_type,
                'agency_id' => $client->agency_id,
            ];
        }

        $calon = $user->calonJf;

        return [
            'nip' => $calon?->nip,
            'nama' => $calon?->nama ?? $user->name,
            'type' => null,
            'agency_type' => null,
            'agency_id' => null,
        ];
    }

    public static function clientMaySubmit(?User $user = null): bool
    {
        $user ??= auth()->user();

        if ($user === null) {
            return false;
        }

        if ($user->isActiveCalonJf()) {
            return true;
        }

        if (! $user->isActiveClient()) {
            return false;
        }

        $client = $user->client;

        return $client !== null
            && $client->identity()->exists()
            && $client->is_verified === Verified::Verified;
    }
}
