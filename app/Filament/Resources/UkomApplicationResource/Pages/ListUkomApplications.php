<?php

namespace App\Filament\Resources\UkomApplicationResource\Pages;

use App\Filament\Exports\UkomApplicationExporter;
use App\Filament\Resources\UkomApplicationResource;
use App\Services\UkomApplicationAccess;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Actions\ExportAction;
use Filament\Tables\Table;
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

    public function table(Table $table): Table
    {
        return UkomApplicationResource::table($table)
            ->headerActions($this->getTableHeaderActions());
    }

    protected function getTableHeaderActions(): array
    {
        return [
            ExportAction::make()
                ->label('Ekspor')
                ->exporter(UkomApplicationExporter::class)
                ->color('success')
                ->button()
                ->icon('heroicon-m-arrow-down-tray')
                ->modifyQueryUsing(function (Builder $query): Builder {
                    $query = UkomApplicationResource::applyVerificationFilters(
                        UkomApplicationResource::getEloquentQuery(),
                        $this->tableFilters ?? [],
                    );

                    if (filled($this->tableSearch)) {
                        $search = '%'.$this->tableSearch.'%';

                        $query->where(function (Builder $q) use ($search): void {
                            $q->where('nama', 'like', $search)
                                ->orWhere('nip', 'like', $search);
                        });
                    }

                    return $query;
                }),
        ];
    }
}
