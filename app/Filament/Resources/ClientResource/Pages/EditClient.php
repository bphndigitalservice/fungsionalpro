<?php

namespace App\Filament\Resources\ClientResource\Pages;

use App\Enums\ClientCluster;
use App\Filament\Resources\ClientResource;
use App\Filament\Resources\CRoleResource\Concern\CanAccessClientData;
use App\Models\RegDepartment;
use App\Models\RegDepartmentEchelon1;
use App\Models\RegProvince;
use App\Models\RegRegency;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditClient extends EditRecord
{
    use CanAccessClientData;

    protected static string $resource = ClientResource::class;

    protected function authorizeAccess(): void
    {
        $this->canAccessClientData();

        abort_unless(static::getResource()::canEdit($this->getRecord()), 403);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        unset(
            $data['is_verified'],
            $data['verified_at'],
            $data['user_id'],
        );

        $data['agency_type'] = match ($data['type']) {
            ClientCluster::Central->value => RegDepartment::class,
            ClientCluster::LocalProvince->value => RegProvince::class,
            ClientCluster::LocalRegency->value => RegRegency::class,
        };

        $data['echelon_type'] = match ($data['type']) {
            ClientCluster::Central->value => RegDepartmentEchelon1::class,
            ClientCluster::LocalProvince->value => RegProvince::class,
            ClientCluster::LocalRegency->value => RegRegency::class,
        };

        if ($data['type'] === ClientCluster::Central->value) {
            $data['echelon_x_text'] = null;
        } else {
            $data['echelon_id'] = null;
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
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
