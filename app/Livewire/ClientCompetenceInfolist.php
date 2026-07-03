<?php

namespace App\Livewire;

use App\Infolists\Components\MinioFileEntry;
use App\Enums\TrainingType;
use App\Enums\TrainingCompletionStatus;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Infolists\Infolist;
use Illuminate\Database\Eloquent\Model;
use Livewire\Component;
use Filament\Infolists\Components\Section;

class ClientCompetenceInfolist extends Component implements HasForms, HasInfolists
{
    use InteractsWithInfolists;
    use InteractsWithForms;

    protected ?Model $record;

    public function mount(?Model $record = null): void
    {
        $this->record = $record;
    }

    public function competenceInfolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->record($this->record)
            ->schema([
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

                                // FIXED HERE: Changed typehint to TrainingCompletionStatus and matched against ->value
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

    public function render()
    {
        return view('livewire.view-client-competence');
    }
}
