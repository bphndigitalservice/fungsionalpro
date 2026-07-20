<?php

namespace App\Filament\Resources\ClientEducationResource\Pages;

use Illuminate\Contracts\Support\Htmlable;
use Filament\Actions\CreateAction;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\ClientEducationResource;
use App\Models\Client;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListClientEducation extends ListRecords
{
    protected static string $resource = ClientEducationResource::class;

    /**
     * @return string|Htmlable
     */
    public function getTitle(): string|Htmlable
    {
        return __('labels.page.client_education_list.title');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected function getTableQuery(): ?Builder
    {
        return parent::getTableQuery()->where('client_id', Client::current()?->id ?? 0);
    }
}
