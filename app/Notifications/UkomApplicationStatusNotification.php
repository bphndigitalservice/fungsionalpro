<?php

namespace App\Notifications;

use App\Enums\UkomApplicationStatus;
use App\Models\UkomApplication;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class UkomApplicationStatusNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected UkomApplication $application,
        protected UkomApplicationStatus $status,
        protected ?string $reason = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        [$title, $body, $icon, $color] = match ($this->status) {
            UkomApplicationStatus::PendingAdmin => [
                'Pengajuan Ukom diteruskan',
                'Pengajuan Ukom Anda telah diteruskan ke Instansi Pembina untuk verifikasi akhir.',
                'heroicon-o-arrow-right-circle',
                'info',
            ],
            UkomApplicationStatus::Accepted => [
                'Pengajuan Ukom diterima',
                'Pengajuan Ukom Anda telah diterima.',
                'heroicon-o-check-circle',
                'success',
            ],
            UkomApplicationStatus::Rejected => [
                'Pengajuan Ukom ditolak',
                $this->reason
                    ? 'Pengajuan Ukom Anda ditolak. Alasan: '.$this->reason
                    : 'Pengajuan Ukom Anda ditolak.',
                'heroicon-o-x-circle',
                'danger',
            ],
            default => [
                'Status Pengajuan Ukom',
                'Status pengajuan Ukom Anda diperbarui.',
                'heroicon-o-information-circle',
                'gray',
            ],
        };

        return FilamentNotification::make()
            ->title($title)
            ->body($body)
            ->icon($icon)
            ->iconColor($color)
            ->getDatabaseMessage();
    }
}
