<?php

namespace App\Filament\Pages\Verification\Actions;

use App\Enums\Acceptance;
use App\Filament\Pages\Client\Point\Actions\ViewPointSubmission;
use Closure;
use Filament\Actions\Concerns\CanCustomizeProcess;
use Filament\Actions\StaticAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Get;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class VerifyPointSubmissionAction extends Action
{
    use CanCustomizeProcess;

    protected ?Closure $mutateRecordDataUsing = null;

    public static function getDefaultName(): ?string
    {
        return 'verify_submission';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('labels.table.verification.point.actions.accept'));
        $this->icon('heroicon-o-check');

        $this->modalHeading(__('labels.table.verification.point.modal_heading'));

        $this->modalSubmitAction(fn (StaticAction $action) => $action->label(__('Accept')));
        $this->modalCancelAction(fn (StaticAction $action) => $action->label(__('Close')));

        $this->form([
            ...ViewPointSubmission::getFormSubmissionView(true),
            ToggleButtons::make('is_verified')
                ->live()
                ->required()
                ->options(Acceptance::class)->inline(),
            Textarea::make('verifier_note')->required(fn (Get $get) => $get('is_verified') == Acceptance::Reject->value),
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
            $this->process(function (array $data, Model $record, Table $table) {
                if (auth()->user()->isSuperAdmin() || is_null($record->verified_at) || is_null($record->revised_at)) {
                    $record->verify($data['is_verified'], $data['verifier_note']);
                }
            });
        });

    }

    public function mutateRecordDataUsing(?Closure $callback): static
    {
        $this->mutateRecordDataUsing = $callback;

        return $this;
    }
}
