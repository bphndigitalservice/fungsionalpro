<?php

namespace App\Filament\Resources\ClientEducationResource\Pages;

use App\Filament\Resources\ClientEducationResource;
use App\Models\Client;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListClientEducation extends ListRecords
{
    protected static string $resource = ClientEducationResource::class;

    /**
     * @return string|\Illuminate\Contracts\Support\Htmlable
     */
    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return __('labels.page.client_education_list.title');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getTableQuery(): ?\Illuminate\Database\Eloquent\Builder
    {
        return parent::getTableQuery()->where('client_id', Client::current()?->id ?? 0);
    }
}
