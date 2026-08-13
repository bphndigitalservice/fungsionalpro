<?php

namespace App\Filament\Resources\VerifierAccessResource\Pages;

use App\Filament\Resources\VerifierAccessResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVerifierAccess extends EditRecord
{
    protected static string $resource = VerifierAccessResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        return VerifierAccessResource::formDataBeforeFill($data);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return VerifierAccessResource::formDataForRegionalScope($data);
    }
}
