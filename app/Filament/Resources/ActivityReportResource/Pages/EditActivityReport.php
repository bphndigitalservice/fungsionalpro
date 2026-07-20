<?php

namespace App\Filament\Resources\ActivityReportResource\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\ActivityReportResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditActivityReport extends EditRecord
{
    protected static string $resource = ActivityReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
