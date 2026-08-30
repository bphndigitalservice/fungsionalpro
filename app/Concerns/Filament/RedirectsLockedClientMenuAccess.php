<?php

namespace App\Concerns\Filament;

use App\Filament\Pages\Client\ClientProfilePage;
use Filament\Notifications\Notification;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\RedirectResponse;
use Livewire\Mechanisms\HandleRequests\HandleRequests;

trait RedirectsLockedClientMenuAccess
{
    public function mountCanAuthorizeResourceAccess(): void
    {
        if ($this->redirectIfClientMenuLocked(static::getResource()::canAccess())) {
            return;
        }

        abort_unless(static::getResource()::canAccess(), 403);
    }

    public function hydrateCanAuthorizeResourceAccess(): void
    {
        $this->mountCanAuthorizeResourceAccess();
    }

    public function authorizeAccess(): void
    {
        if (method_exists(static::class, 'getResource')) {
            $allowed = static::getResource()::canCreate();
        } else {
            $allowed = static::canAccess();
        }

        if ($this->redirectIfClientMenuLocked($allowed)) {
            return;
        }

        abort_unless($allowed, 403);
    }

    public function mountCanAuthorizeAccess(): void
    {
        $allowed = method_exists(static::class, 'getResource')
            ? static::canAccess(method_exists($this, 'getRecord') ? ['record' => $this->getRecord()] : [])
            : static::canAccess();

        if ($this->redirectIfClientMenuLocked($allowed)) {
            return;
        }

        abort_unless($allowed, 403);
    }

    public function hydrateCanAuthorizeAccess(): void
    {
        $this->mountCanAuthorizeAccess();
    }

    protected function redirectIfClientMenuLocked(bool $allowed): bool
    {
        if ($allowed || ! ClientMenuAccess::isClientWithoutSuperAdmin()) {
            return false;
        }

        Notification::make()
            ->warning()
            ->title(__('labels.page.client_profile.verify_required'))
            ->send();

        $url = ClientProfilePage::getUrl();

        $this->redirect($url);

        if (! app(HandleRequests::class)->isLivewireRequest()) {
            throw new HttpResponseException(new RedirectResponse($url));
        }

        return true;
    }
}
