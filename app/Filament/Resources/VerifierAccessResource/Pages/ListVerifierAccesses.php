<?php

namespace App\Filament\Resources\VerifierAccessResource\Pages;

use App\Filament\Resources\VerifierAccessResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListVerifierAccesses extends ListRecords
{
    protected static string $resource = VerifierAccessResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTitle(): string|Htmlable
    {
        return __('labels.page.regional_access.title');
    }


}
