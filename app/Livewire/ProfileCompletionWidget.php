<?php

namespace App\Livewire;

use App\Models\Client;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Infolists\Components\IconEntry;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Locked;

class ProfileCompletionWidget extends Widget implements HasActions, HasSchemas
{
    use HasWidgetShield;
    use InteractsWithActions;
    use InteractsWithSchemas;

    protected string $view = 'livewire.profile-completion-widget';

    #[Locked]
    public ?Client $client = null;

    public function mount(): void
    {
        $this->client = Client::query()
            ->where('user_id', auth()->id())
            ->first();
    }

    public function completionInfolist(Schema $schema): Schema
    {
        return $schema
            ->record($this->client)
            ->components([
                IconEntry::make('Pendidikan Terakhir')
                    ->state(fn (Model $record) => $record->hasLatestEducation())
                    ->icons([
                        true => 'heroicon-o-check-circle',
                        false => 'heroicon-o-x-mark',
                    ]),
                IconEntry::make('Data Pribadi')
                    ->state(fn (Model $record) => $record->hasLatestEducation())
                    ->icons([
                        true => 'heroicon-o-check-circle',
                        false => 'heroicon-o-x-mark',
                    ]),
            ]);
    }
}
