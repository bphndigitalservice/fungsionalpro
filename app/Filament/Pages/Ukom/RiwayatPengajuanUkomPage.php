<?php

namespace App\Filament\Pages\Ukom;

use App\Models\UkomApplication;
use App\Services\UkomApplicationAccess;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;

class RiwayatPengajuanUkomPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $view = 'filament.pages.riwayat-pengajuan-ukom';

    protected static ?int $navigationSort = 6;

    public static function getNavigationLabel(): string
    {
        return __('labels.page.riwayat_pengajuan_ukom.nav');
    }

    public function getTitle(): string|Htmlable
    {
        return __('labels.page.riwayat_pengajuan_ukom.title');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('labels.nav.ukom');
    }

    public static function getRoutePath(): string
    {
        return '/riwayat-pengajuan-ukom';
    }

    public static function shouldRegisterNavigation(): bool
    {
        return app(UkomApplicationAccess::class)->canSubmit();
    }

    public static function canAccess(): bool
    {
        return app(UkomApplicationAccess::class)->canSubmit();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge(),
                Tables\Columns\TextColumn::make('progress')
                    ->label('Progres')
                    ->html()
                    ->getStateUsing(function (UkomApplication $record): string {
                        $parts = [];

                        foreach ($record->progressSteps() as $index => $step) {
                            if ($index > 0) {
                                $parts[] = '<span class="text-gray-300 dark:text-gray-600">→</span>';
                            }

                            $class = match ($step['state']) {
                                'current' => 'text-primary-600 dark:text-primary-400 font-medium',
                                'done' => 'text-success-600 dark:text-success-400 font-medium',
                                'failed' => 'text-danger-600 dark:text-danger-400 font-medium',
                                default => 'text-gray-400 dark:text-gray-500',
                            };

                            $parts[] = '<span class="'.$class.'">'.e($step['label']).'</span>';
                        }

                        return '<div class="flex flex-wrap items-center gap-1 text-xs">'.implode('', $parts).'</div>';
                    }),
                Tables\Columns\TextColumn::make('targetCRole.role_name')
                    ->label('Daftar sebagai')
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Diajukan Pada')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('instansi_decision')
                    ->label('Diterima/Ditolak pada (Instansi)')
                    ->getStateUsing(fn (UkomApplication $record) => $record->instansiDecisionDisplay())
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('pembina_decision')
                    ->label('Diterima/Ditolak pada (Instansi pembina)')
                    ->getStateUsing(fn (UkomApplication $record) => $record->pembinaDecisionDisplay())
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('rejection_reason')
                    ->label('Alasan')
                    ->placeholder('-')
                    ->wrap(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50])
            ->emptyStateHeading(__('labels.page.riwayat_pengajuan_ukom.empty'));
    }

    protected function getTableQuery(): Builder
    {
        return UkomApplication::query()
            ->with(['targetCRole'])
            ->where('user_id', auth()->id());
    }
}
