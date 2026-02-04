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
use Hugomyb\FilamentMediaAction\Infolists\Components\Actions\MediaAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Filament\Infolists\Components\Section;

class ClientEducationInfolist extends Component implements HasForms, HasInfolists
{
    use InteractsWithInfolists;
    use InteractsWithForms;

    protected ?Model $record;

    public function mount(?Model $record = null): void
    {
        $this->record = $record;
    }

public function educationInfolist(Infolist $infolist): Infolist
{
    return $infolist
        ->record($this->record)
        ->schema([
            Section::make('Riwayat Pendidikan')
                ->schema([
                    RepeatableEntry::make('educations')
                        ->schema([
                            TextEntry::make('level')->label('Jenjang'),
                            TextEntry::make('program_name')->label('Jurusan'),
                            TextEntry::make('university_name')->label('Sekolah/Universitas'),
                            TextEntry::make('gpa')->label('Nilai/IPK'),
                            MinioFileEntry::make('certificate')->label('Ijazah'),
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
