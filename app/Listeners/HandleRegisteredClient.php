<?php

namespace App\Listeners;

use App\Concerns\Point\PointRule;
use App\Models\ClientPoint;
use Filament\Events\Auth\Registered;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HandleRegisteredClient
{
    protected Authenticatable $user;

    protected array $defaultRoles = [
        'client', 'panel_user',
    ];

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(Registered $event): void
    {
        $this->user = $event->getUser();
        $this->handleAfterClientCreation();
    }

    protected function handleAfterClientCreation(): void
    {
        try {
            DB::transaction(function () {
                $this->setDefaultRole();
                $this->createClientPointBag();
            });
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());

            return;
        }

    }

    protected function setDefaultRole(): void
    {
        $this->user->assignRole($this->defaultRoles);
    }

    public function createClientPointBag(): void
    {
        ClientPoint::create([
            'client_id' => $this->user->id,
            'point' => PointRule::getDefaultPoint(),
        ]);
    }
}
