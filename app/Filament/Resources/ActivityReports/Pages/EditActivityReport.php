<?php

namespace App\Filament\Resources\ActivityReports\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\ActivityReports\ActivityReportResource;
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
