<?php

namespace App\Filament\Resources\ClientActivityResource\Pages;

use App\Concerns\Filament\AuthorizesOwnClientRecord;
use App\Concerns\Filament\RedirectsLockedClientMenuAccess;
use App\Filament\Resources\ClientActivityResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditClientActivity extends EditRecord
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

    protected static string $resource = ClientActivityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        unset($data['client_id']);

        return $data;
    }

    protected function afterSave(): void
    {
        $this->record->forceFill([
            'is_verified' => null,
            'verification_note' => null,
            'verified_by' => null,
            'verified_at' => null,
        ])->save();
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction()
                ->label('Perbarui'),

            $this->getCancelFormAction()
                ->label('Batal'),
        ];
    }
}
