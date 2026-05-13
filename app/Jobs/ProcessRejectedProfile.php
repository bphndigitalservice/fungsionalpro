<?php

namespace App\Jobs;

use App\Mail\MailClientProfileRejected;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class ProcessRejectedProfile implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    public function __construct(private readonly string $email, private readonly string $croleName, private readonly string $verifierNotes)
    {
        
    }

    public function handle(): void
    {
        Mail::to($this->email)
            ->send(new MailClientProfileRejected($this->croleName, $this->verifierNotes));
    }
}
