<?php

namespace App\Filament\Pages\Verification\Actions;

use App\Notifications\ActivityStatusNotification;
use Filament\Actions\Concerns\CanCustomizeProcess;
use Filament\Forms\Components\Textarea;
use Filament\Support\Colors\Color;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class RejectClientActivityAction extends Action
{
    use CanCustomizeProcess;

    public static function getDefaultName(): ?string
    {
        return 'reject_activity';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('Tolak'));

        $this->icon('heroicon-o-x-circle');

        $this->color(Color::Red);

        $this->requiresConfirmation();

        $this->form([
            Textarea::make('verifier_notes')
                ->label('Catatan Verifikator')
                ->required(),
        ]);

        $this->action(function (): void {

            $this->process(function (
                array $data,
                Model $record,
                Table $table
            ) {

                // 1. Update record
                $record->update([
                    'is_verified' => false,
                    'verification_note' => $data['verifier_notes'],
                    'verified_by' => auth()->id(),
                    'verified_at' => now(),
                ]);

                // 2. Send notification
                $user = $record->client->user; // adjust if relation differs

                $user?->notify(
                    new ActivityStatusNotification(
                        $record,
                        'rejected',
                        $data['verifier_notes']
                    )
                );

                // 3. (optional) event - you can re-enable later if needed
                // event(new ClientActivityRejected($record, $data['verifier_notes']));
            });

            $this->success();
        });
    }
}