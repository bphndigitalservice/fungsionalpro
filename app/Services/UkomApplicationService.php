<?php

namespace App\Services;

use App\Enums\UkomApplicationStatus;
use App\Models\RegDepartment;
use App\Models\RegProvince;
use App\Models\RegRegency;
use App\Models\UkomApplication;
use App\Models\UkomApplicationDocument;
use App\Models\UkomDocumentType;
use App\Models\User;
use App\Notifications\UkomApplicationStatusNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class UkomApplicationService
{
    public function __construct(
        private readonly UkomApplicationAccess $access,
    ) {}

    public function saveDraft(User $user, array $data, array $documentsByTypeId): UkomApplication
    {
        $this->assertCanFill($user);

        return DB::transaction(function () use ($user, $data, $documentsByTypeId) {
            $application = $this->persistApplication($user, $data, UkomApplicationStatus::Draft);
            $this->syncDocuments($application, $documentsByTypeId);

            return $application->fresh(['documents']);
        });
    }

    public function submit(User $user, array $data, array $documentsByTypeId): UkomApplication
    {
        $this->assertCanFill($user);

        $application = DB::transaction(function () use ($user, $data, $documentsByTypeId) {
            $application = $this->persistApplication($user, $data, UkomApplicationStatus::PendingInstansi);
            $this->syncDocuments($application, $documentsByTypeId);
            $this->assertRequiredDocuments($application);

            return $application->fresh(['documents', 'targetCRole', 'user']);
        });

        return $application;
    }

    public function forward(User $actor, UkomApplication $application): UkomApplication
    {
        abort_unless($this->access->canForward($actor, $application), 403);

        $application->forceFill([
            'status' => UkomApplicationStatus::PendingAdmin,
            'instansi_reviewed_by' => $actor->id,
            'instansi_reviewed_at' => now(),
            'rejection_reason' => null,
        ])->save();

        $application->user->notify(new UkomApplicationStatusNotification(
            $application,
            UkomApplicationStatus::PendingAdmin,
        ));

        return $application->fresh();
    }

    public function reject(User $actor, UkomApplication $application, string $reason): UkomApplication
    {
        $reason = trim($reason);

        if ($reason === '') {
            throw ValidationException::withMessages([
                'rejection_reason' => 'Alasan penolakan wajib diisi.',
            ]);
        }

        $asInstansi = $this->access->canForward($actor, $application);
        $asAdmin = $this->access->canFinalDecide($actor, $application);

        abort_unless($asInstansi || $asAdmin, 403);

        $application->forceFill([
            'status' => UkomApplicationStatus::Rejected,
            'rejection_reason' => $reason,
            ...($asInstansi && $application->status === UkomApplicationStatus::PendingInstansi
                ? ['instansi_reviewed_by' => $actor->id, 'instansi_reviewed_at' => now()]
                : ['admin_reviewed_by' => $actor->id, 'admin_reviewed_at' => now()]),
        ])->save();

        $application->user->notify(new UkomApplicationStatusNotification(
            $application,
            UkomApplicationStatus::Rejected,
            $reason,
        ));

        return $application->fresh();
    }

    public function accept(User $actor, UkomApplication $application): UkomApplication
    {
        abort_unless($this->access->canFinalDecide($actor, $application), 403);

        $application->forceFill([
            'status' => UkomApplicationStatus::Accepted,
            'admin_reviewed_by' => $actor->id,
            'admin_reviewed_at' => now(),
            'rejection_reason' => null,
        ])->save();

        $application->user->notify(new UkomApplicationStatusNotification(
            $application,
            UkomApplicationStatus::Accepted,
        ));

        return $application->fresh();
    }

    private function persistApplication(User $user, array $data, UkomApplicationStatus $status): UkomApplication
    {
        $open = UkomApplication::query()
            ->where('user_id', $user->id)
            ->open()
            ->first();

        if ($open && $open->status !== UkomApplicationStatus::Draft) {
            throw ValidationException::withMessages([
                'status' => 'Pengajuan Ukom sedang diproses dan tidak dapat diubah.',
            ]);
        }

        $snapshot = UkomApplication::snapshotFromUser($user);
        $isCalon = $user->isActiveCalonJf();

        $cluster = $isCalon
            ? ($data['type'] ?? $open?->type?->value ?? $open?->type)
            : ($snapshot['type'] instanceof \BackedEnum ? $snapshot['type']->value : $snapshot['type']);

        $payload = [
            'user_id' => $user->id,
            'target_c_role_id' => $data['target_c_role_id'] ?? $open?->target_c_role_id,
            'status' => $status,
            'nip' => $snapshot['nip'],
            'nama' => $snapshot['nama'],
            'current_jabatan' => $isCalon
                ? ($data['current_jabatan'] ?? $open?->current_jabatan)
                : ($user->client?->crole?->role_name ?? $open?->current_jabatan),
            'type' => $cluster,
            'agency_type' => $isCalon
                ? ($cluster ? $this->agencyModel((string) $cluster) : null)
                : $snapshot['agency_type'],
            'agency_id' => $isCalon
                ? ($data['agency_id'] ?? $open?->agency_id)
                : $snapshot['agency_id'],
            'claims_new_degree' => (bool) ($data['claims_new_degree'] ?? false),
        ];

        if ($status === UkomApplicationStatus::PendingInstansi) {
            if (empty($payload['target_c_role_id'])) {
                throw ValidationException::withMessages([
                    'target_c_role_id' => 'Daftar sebagai wajib diisi.',
                ]);
            }

            if (blank($payload['current_jabatan'])) {
                throw ValidationException::withMessages([
                    'current_jabatan' => 'Jabatan saat ini wajib diisi.',
                ]);
            }

            if ($isCalon && (empty($payload['agency_type']) || empty($payload['agency_id']))) {
                throw ValidationException::withMessages([
                    'agency_id' => 'Instansi wajib diisi.',
                ]);
            }
        }

        if ($open) {
            $open->fill($payload)->save();

            return $open;
        }

        if (UkomApplication::userHasOpen($user->id)) {
            throw ValidationException::withMessages([
                'status' => 'Anda masih memiliki pengajuan Ukom yang belum selesai.',
            ]);
        }

        return UkomApplication::create($payload);
    }

    /**
     * @param  array<int|string, string|null>  $documentsByTypeId
     */
    private function syncDocuments(UkomApplication $application, array $documentsByTypeId): void
    {
        foreach ($documentsByTypeId as $typeId => $path) {
            if (is_array($path)) {
                $path = $path[0] ?? null;
            }
            if (! $path) {
                UkomApplicationDocument::query()
                    ->where('ukom_application_id', $application->id)
                    ->where('ukom_document_type_id', $typeId)
                    ->delete();

                continue;
            }

            UkomApplicationDocument::query()->updateOrCreate(
                [
                    'ukom_application_id' => $application->id,
                    'ukom_document_type_id' => $typeId,
                ],
                ['file_path' => $path],
            );
        }
    }

    private function assertRequiredDocuments(UkomApplication $application): void
    {
        $types = UkomDocumentType::query()
            ->where('pack', $application->pack())
            ->orderBy('sort')
            ->get();

        $uploaded = $application->documents()->pluck('file_path', 'ukom_document_type_id');
        $missing = [];

        foreach ($types as $type) {
            if (! $type->isRequiredFor((bool) $application->claims_new_degree)) {
                continue;
            }

            if (blank($uploaded[$type->id] ?? null)) {
                $missing[] = $type->label;
            }
        }

        if ($missing !== []) {
            throw ValidationException::withMessages([
                'documents' => 'Lengkapi dokumen wajib: '.implode('; ', $missing),
            ]);
        }
    }

    private function agencyModel(string $type): string
    {
        return match ($type) {
            'central' => RegDepartment::class,
            'local_province' => RegProvince::class,
            'local_regency' => RegRegency::class,
            default => RegDepartment::class,
        };
    }

    private function assertCanFill(User $user): void
    {
        if (! UkomApplication::clientMaySubmit($user)) {
            throw ValidationException::withMessages([
                'status' => __('labels.page.pengajuan_ukom.verify_required'),
            ]);
        }
    }
}
