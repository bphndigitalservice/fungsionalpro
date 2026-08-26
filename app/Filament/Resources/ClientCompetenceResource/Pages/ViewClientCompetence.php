<?php

namespace App\Filament\Resources\ClientCompetenceResource\Pages;

use App\Concerns\Filament\AuthorizesOwnClientRecord;
use App\Concerns\Filament\RedirectsLockedClientMenuAccess;
use App\Filament\Resources\ClientCompetenceResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewClientCompetence extends ViewRecord
{
    use AuthorizesOwnClientRecord {
        RedirectsLockedClientMenuAccess::authorizeAccess insteadof AuthorizesOwnClientRecord;
        AuthorizesOwnClientRecord::authorizeAccess as authorizeOwnClientRecord;
        RedirectsLockedClientMenuAccess::authorizeAccess as redirectLockedAuthorizeAccess;
    }
    use RedirectsLockedClientMenuAccess;

    public function authorizeAccess(): void
    {
        $this->redirectLockedAuthorizeAccess();
        $this->authorizeOwnClientRecord();
    }

    protected static string $resource = ClientCompetenceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
