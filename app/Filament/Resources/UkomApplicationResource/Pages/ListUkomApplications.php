<?php

namespace App\Filament\Resources\UkomApplicationResource\Pages;

use App\Filament\Resources\UkomApplicationResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListUkomApplications extends ListRecords
{
    protected static string $resource = UkomApplicationResource::class;

    public function getTitle(): string|Htmlable
    {
        return __('labels.page.ukom_verification.nav');
    }
}
