<?php

namespace App\Filament\Pages\Verification\Actions;

use Filament\Actions\Concerns\CanCustomizeProcess;
use Filament\Support\Colors\Color;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use App\Events\ClientActivityAccepted;

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
                    event(new ClientActivityAccepted($record));
                });

            $this->success();
        });
    }
}