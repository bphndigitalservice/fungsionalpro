<?php

namespace App\Filament\Pages\Verification\Actions;

use App\Enums\Acceptance;
use App\Events\ClientProfileAccepted;
use App\Events\ClientProfileRejected;
use App\Filament\Pages\Client\ClientProfilePage;
use Closure;
use Filament\Actions\StaticAction;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Get;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ViewClientIdentityAction extends Action
{
    // Removed `use CanCustomizeProcess;` to stop Filament from automatically binding form state to model update queries

    protected ?Closure $mutateRecordDataUsing = null;

    public static function getDefaultName(): ?string
    {
        return 'verify_identity';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(fn (Model $record) => ($record->is_verified !== \App\Enums\Verified::Unverified || ($record->note && !empty($record->note->verifier_notes))) ? __('Lihat') : __('Periksa'));
        $this->icon(fn (Model $record) => ($record->is_verified !== \App\Enums\Verified::Unverified || ($record->note && !empty($record->note->verifier_notes))) ? 'heroicon-o-eye' : 'heroicon-o-check');
        $this->color(fn (Model $record) => ($record->is_verified !== \App\Enums\Verified::Unverified || ($record->note && !empty($record->note->verifier_notes))) ? 'gray' : 'primary');
        $this->link();

        $this->modalHeading(__('Verifikasi Identitas JF'));

        $this->modalSubmitAction(fn (StaticAction $action, Model $record) => $action
            ->label(__('Save'))
            ->hidden($record->is_verified !== \App\Enums\Verified::Unverified || ($record->note && !empty($record->note->verifier_notes)))
        );

        $this->modalCancelAction(fn (StaticAction $action) => $action->label(__('close')));

        $this->form([
            Group::make(
                collect(ClientProfilePage::getClientIdentityForm())
                    ->map(fn (Component $component) => $this->disableValidationRecursively($component))
                    ->toArray()
            )
                ->dehydrated(false)
                ->disabled(),

            ToggleButtons::make('verification_status')
                ->live()
                ->disabled(fn (Model $record) => $record->is_verified !== \App\Enums\Verified::Unverified || ($record->note && !empty($record->note->verifier_notes)))
                ->required()
                ->options(Acceptance::class)
                ->inline(),

            Textarea::make('verifier_note')
                ->disabled(fn (Model $record) => $record->is_verified !== \App\Enums\Verified::Unverified || ($record->note && !empty($record->note->verifier_notes)))
                ->required(fn (Get $get) => $get('verification_status') == Acceptance::Reject->value),
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

            unset($data['is_verified']);

            if ($record->is_verified !== \App\Enums\Verified::Unverified) {
                $data['verification_status'] = Acceptance::Accept->value;
            } elseif ($record->note && !empty($record->note->verifier_notes)) {
                $data['verification_status'] = Acceptance::Reject->value;
                $data['verifier_note'] = $record->note->verifier_notes;
            }

            return $data;
        });

        // Use direct closure execution without Filament process pipeline
        $this->action(function (array $data, Model $record): void {
            $status = $data['verification_status'] instanceof Acceptance
                ? $data['verification_status']
                : Acceptance::from($data['verification_status']);

            if ($status === Acceptance::Accept) {
                $record->verified();
                event(new ClientProfileAccepted($record));
            } else {
                $record->reject();
                event(new ClientProfileRejected($record, $data['verifier_note'] ?? null));
            }

            $this->success();
        });

        $this->extraModalFooterActions([]);
    }

    public function mutateRecordDataUsing(?Closure $callback): static
    {
        $this->mutateRecordDataUsing = $callback;

        return $this;
    }

    protected function disableValidationRecursively(Component $component): Component
    {
        if ($component instanceof Field) {
            $component
                ->required(false)
                ->dehydrated(false)
                ->rules([]);

            if (method_exists($component, 'unique')) {
                $component->unique(ignoreRecord: true);
            }

            try {
                $reflection = new \ReflectionProperty($component, 'rules');
                $reflection->setAccessible(true);
                $reflection->setValue($component, []);
            } catch (\Throwable $e) {
                // Ignore if property does not exist on target component class
            }
        }

        if (method_exists($component, 'getChildComponents')) {
            $childComponents = collect($component->getChildComponents())
                ->map(fn (Component $child) => $this->disableValidationRecursively($child))
                ->toArray();

            $component->schema($childComponents);
        }

        return $component;
    }
}
