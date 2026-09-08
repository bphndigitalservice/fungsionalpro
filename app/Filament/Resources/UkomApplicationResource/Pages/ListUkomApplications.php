<?php

namespace App\Filament\Resources\UkomApplicationResource\Pages;

use App\Filament\Resources\UkomApplicationResource;
use App\Services\UkomApplicationAccess;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ListUkomApplications extends ListRecords
{
    protected static string $resource = UkomApplicationResource::class;

    public function getTitle(): string|Htmlable
    {
        return __('labels.page.ukom_verification.nav');
    }

    public function getTabs(): array
    {
        $access = app(UkomApplicationAccess::class);
        $user = Auth::user();

        return [
            'all' => Tab::make('All'),

            'new' => Tab::make('New')
                ->badge(function () use ($access, $user): int {
                    if ($user === null) {
                        return 0;
                    }

                    return $access->applyNewTabFilter(
                        UkomApplicationResource::getEloquentQuery(),
                        $user,
                    )->count();
                })
                ->modifyQueryUsing(function (Builder $query) use ($access, $user): Builder {
                    if ($user === null) {
                        return $query->whereRaw('1 = 0');
                    }

                    return $access->applyNewTabFilter($query, $user);
                }),

            'processed' => Tab::make('Processed')
                ->modifyQueryUsing(function (Builder $query) use ($access, $user): Builder {
                    if ($user === null) {
                        return $query->whereRaw('1 = 0');
                    }

                    return $access->applyProcessedTabFilter($query, $user);
                }),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'new';
    }
}
