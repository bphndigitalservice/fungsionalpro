<?php

namespace App\Filament\Resources\ClientPointSubmissionBagResource\Pages;

use App\Filament\Resources\ClientPointSubmissionBagResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditClientPointSubmissionBag extends EditRecord
{
    protected static string $resource = ClientPointSubmissionBagResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = auth()->user()->id;

        return $data;
    }
}
