<?php

namespace App\Filament\Resources\ClientEducationResource\Pages;

use App\Concerns\Filament\RedirectsLockedClientMenuAccess;
use App\Filament\Resources\ClientEducationResource;
use App\Models\Client;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;

class ListClientEducation extends ListRecords
{
    use RedirectsLockedClientMenuAccess;

    protected static string $resource = ClientEducationResource::class;

    public function getTitle(): string|Htmlable
    {
        return __('labels.page.client_education_list.title');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getTableQuery(): ?Builder
    {
        return parent::getTableQuery()->where('client_id', Client::current()?->id ?? 0);
    }
}
