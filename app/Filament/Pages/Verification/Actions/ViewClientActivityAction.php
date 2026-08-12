<?php

namespace App\Filament\Pages\Verification\Actions;

use App\Enums\Acceptance;
use App\Filament\Resources\ClientActivityResource;
use App\Notifications\ActivityStatusNotification;
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
use Closure;

class ViewClientActivityAction extends Action
{
    use CanCustomizeProcess;

    protected ?Closure $mutateRecordDataUsing = null;

    public static function getDefaultName(): ?string
    {
        return 'verify_activity';
    }

    public function setUp(): void
    {
        parent::setUp();

        $this->label(fn (Model $record) => $record->is_verified !== null ? __('Lihat') : __('Periksa'));
        $this->icon(fn (Model $record) => $record->is_verified !== null ? 'heroicon-o-eye' : 'heroicon-o-check');
        $this->color(fn (Model $record) => $record->is_verified !== null ? 'gray' : 'primary');
        $this->link();

        $this->modalHeading(__('Verifikasi Pelaporan Kegiatan'));

        $this->modalSubmitAction(fn (StaticAction $action, Model $record) => $action
            ->label(__('Save'))
            ->hidden($record->is_verified !== null)
        );

        $this->modalCancelAction(fn (StaticAction $action) => $action->label(__('close')));

        $this->form([
            Group::make(
                collect(ClientActivityResource::getFormSchema())
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
                ->disabled(fn (Model $record) => $record->is_verified !== null)
                ->required()
                ->options(Acceptance::class)
                ->inline(),
            Textarea::make('verifier_notes')
                ->label('Catatan Verifikator')
                ->statePath('verification_note')
                ->disabled(fn (Model $record) => $record->is_verified !== null)
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

                    $user = $record->client->user;
                    $user?->notify(
                        new ActivityStatusNotification($record, 'accepted')
                    );
                } else {
                    $record->forceFill([
                        'is_verified' => Acceptance::Reject,
                        'verification_note' => $data['verification_note'],
                        'verified_by' => auth()->id(),
                        'verified_at' => now(),
                    ])->save();

                    $user = $record->client->user;
                    $user?->notify(
                        new ActivityStatusNotification(
                            $record,
                            'rejected',
                            $data['verification_note']
                        )
                    );
                }
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
