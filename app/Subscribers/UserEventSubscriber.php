<?php

namespace App\Subscribers;

use Filament\Auth\Events\Registered;
use Exception;
use App\Enums\SystemRole;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Log;

class UserEventSubscriber
{
    protected Authenticatable $user;

    protected array $defaultRoles = [
        SystemRole::PanelUser->value,
        SystemRole::Client->value,
    ];

    public function handleRegisteredUser(Registered $event): void
    {
        try {
            $this->user = $event->getUser();
            $this->setDefaultRole();
        } catch (Exception $exception) {
            Log::error($exception->getMessage());

            return;
        }
    }

    public function subscribe(Dispatcher $events): array
    {
        return [
            Registered::class => 'handleRegisteredUser',
        ];
    }

    protected function setDefaultRole(): void
    {
        $this->user->assignRole($this->defaultRoles);
    }
}
