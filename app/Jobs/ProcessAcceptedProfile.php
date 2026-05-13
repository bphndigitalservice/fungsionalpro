<?php

namespace App\Jobs;

use App\Mail\MailClientProfileAccepted;
use App\Models\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class ProcessAcceptedProfile implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private readonly Client $client)
    {
        
    }

    public function handle(): void
    {
        Mail::to($this->client->user->email)
            ->send(new MailClientProfileAccepted($this->client->user->email));
    }
}
