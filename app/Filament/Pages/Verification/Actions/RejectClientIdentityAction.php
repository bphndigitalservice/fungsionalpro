<?php

namespace App\Filament\Pages\Verification\Actions;

use Filament\Actions\Action;
use App\Events\ClientProfileRejected;
use Filament\Actions\Concerns\CanCustomizeProcess;
use Filament\Forms\Components\Textarea;
use Filament\Support\Colors\Color;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class RejectClientIdentityAction extends Action
{
    use CanCustomizeProcess;

    public static function getDefaultName(): ?string
    {
        return 'reject_identity';
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->label(__('labels.table.verification.identity.actions.reject'));
        $this->icon('heroicon-o-x-circle');
        $this->color(Color::Red);

        $this->requiresConfirmation();

        $this->form([
            Textarea::make('verifier_notes')->required(),
        ]);

        $this->action(function (): void {
            $this->process(function (array $data, Model $record, Table $table) {
                $record->reject();
                event(new ClientProfileRejected($record, $data['verifier_notes']));
            });

            $this->success();
        });

    }
}
