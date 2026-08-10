<?php

namespace App\Filament\Pages\Verification\Actions;

use App\Enums\Acceptance;
use App\Events\ClientProfileAccepted;
use App\Events\ClientProfileRejected;
use App\Filament\Pages\Client\ClientProfilePage;
use Closure;
use Filament\Actions\Concerns\CanCustomizeProcess;
use Filament\Actions\StaticAction;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Get;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ViewClientIdentityAction extends Action
{
    use CanCustomizeProcess;

    protected ?Closure $mutateRecordDataUsing = null;

    public static function getDefaultName(): ?string
    {
        return 'verify_identity';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(fn (Model $record) => $record->is_verified !== \App\Enums\Verified::Unverified ? __('Lihat') : __('Periksa'));
        $this->icon(fn (Model $record) => $record->is_verified !== \App\Enums\Verified::Unverified ? 'heroicon-o-eye' : 'heroicon-o-check');
        $this->color(fn (Model $record) => $record->is_verified !== \App\Enums\Verified::Unverified ? 'gray' : 'primary');
        $this->link();

        $this->modalHeading(__('Verifikasi Identitas JF'));

        $this->modalSubmitAction(fn (StaticAction $action, Model $record) => $action
            ->label(__('Save'))
            ->hidden($record->is_verified !== \App\Enums\Verified::Unverified)
        );

        $this->modalCancelAction(fn (StaticAction $action) => $action->label(__('close')));

        $this->form([
            Group::make(ClientProfilePage::getClientIdentityForm())
                ->disabled(),
            ToggleButtons::make('is_verified')
                ->live()
                ->disabled(fn (Model $record) => $record->is_verified !== \App\Enums\Verified::Unverified)
                ->required()
                ->options(Acceptance::class)
                ->inline(),
            Textarea::make('verifier_note')
                ->disabled(fn (Model $record) => $record->is_verified !== \App\Enums\Verified::Unverified)
                ->required(fn (Get $get) => $get('is_verified') == Acceptance::Reject->value),
        ]);

        $this->fillForm(function (Model $record, Table $table): array {
            if ($translatableContentDriver = $table->makeTranslatableContentDriver()) {
                $data = $translatableContentDriver->getRecordAttributesToArray($record);
            } else {
                $data = $record->attributesToArray();
            }

            if ($this->mutateRecordDataUsing) {
                $data = $this->evaluate($this->mutateRecordDataUsing, ['data' => $data]);
            }

            return $data;
        });

        $this->action(function (): void {
            $this->process(function (array $data, Model $record) {

                $status = $data['is_verified'] instanceof Acceptance
                    ? $data['is_verified']
                    : Acceptance::from($data['is_verified']);

                if ($status === Acceptance::Accept) {
                    $record->verified();
                    event(new ClientProfileAccepted($record));
                } else {
                    $record->reject();
                    event(new ClientProfileRejected($record, $data['verifier_note']));
                }
            });

            $this->success();
        });
    }

    public function mutateRecordDataUsing(?Closure $callback): static
    {
        $this->mutateRecordDataUsing = $callback;

        return $this;
    }
}
