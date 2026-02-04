<?php

namespace App\Livewire;

use App\Infolists\Components\MinioFileEntry;
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
                            TextEntry::make('title')->label('Pelatihan'),
                            TextEntry::make('institution')->label('Institusi'),
                            TextEntry::make('start_period')->label('Tanggal Mulai'),
                            TextEntry::make('end_period')->label('Tanggal Selesai'),
                            MinioFileEntry::make('competence_file')->label('Sertifikat'),
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
