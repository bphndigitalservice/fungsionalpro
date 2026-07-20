<?php

namespace App\Livewire;

use App\Infolists\Components\MinioFileEntry;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Livewire\Component;

class ClientEducationInfolist extends Component implements HasActions, HasForms, HasInfolists
{
    use InteractsWithActions;
    use InteractsWithForms;
    use InteractsWithInfolists;

    protected ?Model $record;

    public function mount(?Model $record = null): void
    {
        $this->record = $record;
    }

    public function educationInfolist(Schema $schema): Schema
    {
        return $schema
            ->record($this->record)
            ->components([
                Section::make('Riwayat Pendidikan')
                    ->schema([
                        RepeatableEntry::make('educations')
                            ->schema([
                                TextEntry::make('level')->label('Jenjang'),
                                TextEntry::make('program_name')->label('Jurusan'),
                                TextEntry::make('university_name')->label('Sekolah/Universitas'),
                                TextEntry::make('gpa')->label('Nilai/IPK'),

                                TextEntry::make('certificate_date')
                                    ->label('Tanggal Ijazah')
                                    ->date('d F Y'),

                                MinioFileEntry::make('certificate')->label('Ijazah'),

                                MinioFileEntry::make('title_inclusion_file')
                                    ->label('Lembar Pencantuman Gelar')
                                    ->placeholder('Tidak ada file pencantuman gelar')
                                    ->hidden(fn ($record) => empty($record?->title_inclusion_file)),
                            ])
                            ->columns(2),
                    ]),
            ]);
    }

    public function render()
    {
        return view('livewire.client-education-infolist');
    }
}
