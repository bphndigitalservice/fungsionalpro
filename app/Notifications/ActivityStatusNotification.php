<?php

namespace App\Notifications;

use App\Filament\Resources\ClientActivities\ClientActivityResource;
use Filament\Actions\Action;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ActivityStatusNotification extends Notification
{
    use Queueable;

    protected $record;

    protected $status;

    public function __construct($record, string $status)
    {
        $this->record = $record;
        $this->status = $status;
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        $isAccepted = $this->status === 'accepted';
        $statusText = $isAccepted ? 'diverifikasi' : 'ditolak';

        $bodyText = "Kegiatan '{$this->record->title}' Anda telah {$statusText}.";

        if ($this->record->verification_note) {
            $bodyText .= "\nCatatan: {$this->record->verification_note}";
        }

        return FilamentNotification::make()
            ->title($isAccepted ? 'Kegiatan Diverifikasi' : 'Kegiatan Ditolak')
            ->body($bodyText)
            ->icon($isAccepted ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
            ->iconColor($isAccepted ? 'success' : 'danger')
            ->actions([
                Action::make('view')
                    ->label('Lihat Detail')
                    ->url(ClientActivityResource::getUrl('index'))
                    ->markAsRead(),
            ])
            ->getDatabaseMessage();
    }
}
