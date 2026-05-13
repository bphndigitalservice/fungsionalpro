<?php

namespace App\Filament\Pages\Verification\Actions;

use Filament\Actions\Concerns\CanCustomizeProcess;
use Filament\Support\Colors\Color;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use App\Events\ClientActivityAccepted;
use App\Notifications\ActivityStatusNotification;
use App\Models\Client;

class AcceptClientActivityAction extends Action
{
    use CanCustomizeProcess;

    public static function getDefaultName(): ?string
    {
        return 'accept_activity';
    }

    
    public function setUp(): void
    {
        parent::setUp();
        $this->label(__('Terima'));
        $this->icon('heroicon-o-check-badge');
        $this->color(Color::Green);

        $this->requiresConfirmation();

        $this->action(function (): void {
            $this->process(function (array $data, Model $record, Table $table) {

                $record->verified();

                $client = $record->client;

                $user = $client->user;
                $user?->notify(
                    new ActivityStatusNotification($record, 'accepted')
                );

            });

            $this->success();
        });
    }

    
}