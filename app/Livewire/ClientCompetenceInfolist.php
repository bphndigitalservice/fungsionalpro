<?php

namespace App\Livewire;

use App\Enums\TrainingCompletionStatus;
use App\Enums\TrainingType;
use App\Infolists\Components\MinioFileEntry;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Locked;
use Livewire\Component;

class ClientCompetenceInfolist extends Component implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    #[Locked]
    public ?Model $record = null;

    public function competenceInfolist(Schema $schema): Schema
    {
        return $schema
            ->record($this->record)
            ->components([
                Section::make('Daftar Diklat / Pelatihan')
                    ->schema([
                        RepeatableEntry::make('competences')
                            ->schema([
                                TextEntry::make('title')
                                    ->label('Pelatihan'),

                                TextEntry::make('certificate_number')
                                    ->label('Nomor Sertifikat'),

                                TextEntry::make('institution')
                                    ->label('Institusi'),

                                TextEntry::make('category')
                                    ->label('Kategori')
                                    ->badge()
                                    ->formatStateUsing(fn (?TrainingType $state): string => match ($state?->value) {
                                        'TECHNICAL_TRAINING' => 'Diklat Teknis',
                                        'MANAGERIAL_TRAINING' => 'Diklat Manajerial',
                                        default => $state?->value ?? '-',
                                    }),

                                TextEntry::make('start_period')
                                    ->label('Tanggal Mulai')
                                    ->date('d F Y'),

                                TextEntry::make('end_period')
                                    ->label('Tanggal Selesai')
                                    ->date('d F Y'),

                                TextEntry::make('completion_status')
                                    ->label('Predikat Kelulusan/Kinerja')
                                    ->badge()
                                    ->color(fn (?TrainingCompletionStatus $state): string => match ($state?->value) {
                                        'PASSED', 'EXCELLENT', 'VERY_SATISFACTORY' => 'success',
                                        'SATISFACTORY' => 'info',
                                        'LESS_SATISFACTORY' => 'warning',
                                        'FAILED', 'UNSATISFACTORY' => 'danger',
                                        default => 'gray',
                                    })
                                    ->formatStateUsing(fn (?TrainingCompletionStatus $state): string => match ($state?->value) {
                                        'PASSED' => 'Lulus',
                                        'FAILED' => 'Tidak Lulus',
                                        'EXCELLENT' => 'Sangat Memuaskan (Excellent)',
                                        'VERY_SATISFACTORY' => 'Memuaskan',
                                        'SATISFACTORY' => 'Cukup Memuaskan',
                                        'LESS_SATISFACTORY' => 'Kurang Memuaskan',
                                        'UNSATISFACTORY' => 'Tidak Memuaskan',
                                        default => $state?->value ?? '-',
                                    }),

                                MinioFileEntry::make('competence_file')
                                    ->label('Sertifikat')
                                    ->hidden(fn ($record) => empty($record?->competence_file)),
                            ])
                            ->columns(2),
                    ]),
            ]);
    }

    public function render(): View
    {
        return view('livewire.view-client-competence');
    }
}
