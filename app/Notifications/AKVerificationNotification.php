<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Notifications\Actions\Action;

class AKVerificationNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected $record, 
        protected string $status, 
        protected ?string $reason = null
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        $isAccepted = $this->status === 'accepted';
        
        $statusTitle = $isAccepted ? 'Poin Diverifikasi' : 'Poin Gagal Diverifikasi';
        $statusText = $isAccepted ? 'telah berhasil diverifikasi' : 'gagal diverifikasi';
        
        $bodyText = "Pengajuan AK Anda sebesar {$this->record->point} poin {$statusText}.";
        
        if (!$isAccepted && $this->reason) {
            $bodyText .= " Alasan: {$this->reason}";
        }

        return FilamentNotification::make()
            ->title($statusTitle)
            ->body($bodyText)
            ->icon($isAccepted ? 'heroicon-o-sparkles' : 'heroicon-o-x-circle')
            ->iconColor($isAccepted ? 'success' : 'danger')
            ->actions([
                Action::make('view')
                    ->label('Lihat Riwayat Poin')
                    ->url(fn() => route('filament.admin.pages.client-point-list')) 
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }
}