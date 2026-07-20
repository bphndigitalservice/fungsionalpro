<?php

namespace App\Livewire;

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

class ClientEducationInfolist extends Component implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    #[Locked]
    public ?Model $record = null;

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

    public function render(): View
    {
        return view('livewire.client-education-infolist');
    }
}
