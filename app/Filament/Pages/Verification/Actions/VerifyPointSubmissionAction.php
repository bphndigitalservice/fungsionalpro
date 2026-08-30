<?php

namespace App\Filament\Pages\Verification\Actions;

use App\Enums\Acceptance;
use App\Events\PointSubmissionAccepted;
use App\Events\PointSubmissionRejected;
use App\Filament\Pages\Client\Point\Actions\ViewPointSubmission;
use Closure;
use Filament\Actions\Concerns\CanCustomizeProcess;
use Filament\Actions\StaticAction;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Group;
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

        $this->label(fn (Model $record) => in_array($record->status, [\App\Enums\PointSubmissionStatus::Verified, \App\Enums\PointSubmissionStatus::ShouldRevise]) ? __('Lihat') : __('Periksa'));
        $this->icon(fn (Model $record) => in_array($record->status, [\App\Enums\PointSubmissionStatus::Verified, \App\Enums\PointSubmissionStatus::ShouldRevise]) ? 'heroicon-o-eye' : 'heroicon-o-check');
        $this->color(fn (Model $record) => in_array($record->status, [\App\Enums\PointSubmissionStatus::Verified, \App\Enums\PointSubmissionStatus::ShouldRevise]) ? 'gray' : 'primary');
        $this->link();

        $this->modalHeading(__('labels.table.verification.point.modal_heading'));

        $this->modalSubmitAction(fn (StaticAction $action, Model $record) => $action
            ->label(__('Save'))
            ->hidden(in_array($record->status, [\App\Enums\PointSubmissionStatus::Verified, \App\Enums\PointSubmissionStatus::ShouldRevise]))
        );
        $this->modalCancelAction(fn (StaticAction $action) => $action->label(__('Close')));

        $this->form([
            Group::make(
                collect(ViewPointSubmission::getFormSubmissionView(true))
                    ->map(function (Component $component) {
                        if ($component instanceof Field) {
                            $component->required(false);
                        }

                        if ($component instanceof \Filament\Forms\Components\FileUpload) {
                            $component->openable()->previewable()->extraAttributes([
                                'onclick' => "const links = this.querySelectorAll('a'); links.forEach(link => link.setAttribute('target', '_blank'));",
                                'onmouseover' => "const links = this.querySelectorAll('a'); links.forEach(link => link.setAttribute('target', '_blank'));"
                            ]);
                        }

                        return $component;
                    })
                    ->toArray()
            )
                ->dehydrated(false)
                ->disabled(),
            ToggleButtons::make('is_verified')
                ->live()
                ->disabled(fn (Model $record) => in_array($record->status, [\App\Enums\PointSubmissionStatus::Verified, \App\Enums\PointSubmissionStatus::ShouldRevise]))
                ->required()
                ->options(Acceptance::class)
                ->inline(),
            Textarea::make('verifier_note')
                ->disabled(fn (Model $record) => in_array($record->status, [\App\Enums\PointSubmissionStatus::Verified, \App\Enums\PointSubmissionStatus::ShouldRevise]))
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

                $record->verify(
                    $status === Acceptance::Accept,
                    $data['verifier_note']
                );
            });

            $this->success();
        });

        $this->extraModalFooterActions([]);
    }

    public function mutateRecordDataUsing(?Closure $callback): static
    {
        $this->mutateRecordDataUsing = $callback;

        return $this;
    }
}
