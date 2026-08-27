<?php

namespace App\Concerns\Filament;

use App\Filament\Pages\Dashboard;
use App\Models\Client;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\RedirectResponse;
use Livewire\Mechanisms\HandleRequests\HandleRequests;

trait GatesAngkaKreditAccess
{
    public static function clientMayAccessAngkaKredit(): bool
    {
        $client = Client::current();

        if ($client === null) {
            return true;
        }

        if (! ClientMenuAccess::clientMayAccessVerifiedClientMenuItem()) {
            return false;
        }

        return $client->identity?->photo !== null;
    }

    public static function canAccess(array $parameters = []): bool
    {
        $user = Filament::auth()->user();

        if ($user === null || ! $user->can(static::getPermissionName())) {
            return false;
        }

        return static::clientMayAccessAngkaKredit();
    }

    public static function shouldRegisterNavigation(array $parameters = []): bool
    {
        return static::canAccess($parameters);
    }

    public function mountCanAuthorizeAccess(): void
    {
        if ($this->redirectIfAngkaKreditLocked(static::canAccess())) {
            return;
        }

        abort_unless(static::canAccess(), 403);
    }

    public function hydrateCanAuthorizeAccess(): void
    {
        $this->mountCanAuthorizeAccess();
    }

    protected function redirectIfAngkaKreditLocked(bool $allowed): bool
    {
        if ($allowed || ! ClientMenuAccess::isClientWithoutSuperAdmin()) {
            return false;
        }

        Notification::make()
            ->warning()
            ->title(__('labels.page.dashboard.verify_identity_required'))
            ->send();

        $url = Dashboard::getUrl();

        $this->redirect($url);

        if (! app(HandleRequests::class)->isLivewireRequest()) {
            throw new HttpResponseException(new RedirectResponse($url));
        }

        return true;
    }

    protected function getShieldRedirectPath(): string
    {
        if (ClientMenuAccess::isClientWithoutSuperAdmin() && ! ClientMenuAccess::clientProfileIsVerified()) {
            return Dashboard::getUrl();
        }

        return Filament::getUrl();
    }

    protected function beforeShieldRedirects(): void
    {
        if (ClientMenuAccess::isClientWithoutSuperAdmin() && ! static::clientMayAccessAngkaKredit()) {
            Notification::make()
                ->warning()
                ->title(__('labels.page.dashboard.verify_identity_required'))
                ->send();
        }
    }
}
