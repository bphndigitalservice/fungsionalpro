<?php

namespace App\Livewire;

use App\Models\Client;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Infolists\Infolist;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Model;

class ProfileCompletionWidget extends Widget implements HasForms, HasInfolists
{
    use InteractsWithInfolists;
    use InteractsWithForms;

    protected static string $view = 'livewire.profile-completion-widget';
    protected ?Client $client = null;

    public function mount(): void
    {
        $client = Client::where('user_id', auth()->user()->id);
        if ($client->exists()) {
            $this->client = $client->first();
        }
    }

    public function completionInfolist(Infolist $infolist)
    {
        return $infolist
            ->record($this->client)
            ->schema([
                IconEntry::make('Pendidikan Terakhir')
                    ->state(fn(Model $record)=>$record->hasLatestEducation())
                    ->icons([
                        true => 'heroicon-o-check-circle',
                        false=> 'heroicon-o-x-mark'
                    ]),
                IconEntry::make('Data Pribadi')
                    ->state(fn(Model $record)=>$record->hasLatestEducation())
                    ->icons([
                        true => 'heroicon-o-check-circle',
                        false=> 'heroicon-o-x-mark'
                    ])
            ]);
    }
}
