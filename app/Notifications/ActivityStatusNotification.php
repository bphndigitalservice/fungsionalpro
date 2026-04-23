<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;


class ActivityStatusNotification extends Notification
{
    protected $record;
    protected $status;
    protected $message;

    public function __construct($record, string $status, ?string $message = null)
    {
        $this->record = $record;
        $this->status = $status;
        $this->message = $message;
    }

    public function via($notifiable)
    {
        return ['database']; 
    }

    public function toDatabase($notifiable)
{
    return [
        'title' => $this->status === 'accepted'
            ? 'Aktivitas Diterima'
            : 'Aktivitas Ditolak',

        'body' => $this->message ?? (
            $this->status === 'accepted'
                ? 'Aktivitas kamu disetujui.'
                : 'Aktivitas kamu ditolak.'
        ),

        'icon' => $this->status === 'accepted'
            ? 'heroicon-o-check-circle'
            : 'heroicon-o-x-circle',

        'color' => $this->status === 'accepted'
            ? 'success'
            : 'danger',

        // 'url' => \App\Filament\Resources\ClientActivityResource::getUrl('view', [
        //     'record' => $this->record,
        // ]),
    ];
}
}