<?php

namespace App\Filament\Resources\ClientResource\Pages;

use App\Enums\ClientCluster;
use App\Filament\Resources\ClientResource;
use App\Models\RegDepartment;
use App\Models\RegDepartmentEchelon1;
use App\Models\RegProvince;
use App\Models\RegRegency;
use Filament\Resources\Pages\CreateRecord;

class CreateClient extends CreateRecord
{
    protected static string $resource = ClientResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
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

        return parent::mutateFormDataBeforeCreate($data);
    }
}
