<?php

namespace App\Filament\Pages\Verification\Actions;

use App\Filament\Pages\Client\Point\Actions\ViewPointSubmission;
use Closure;
use Filament\Actions\StaticAction;
use Filament\Forms\Components\Textarea;
use Filament\Pages\Concerns\CanUseDatabaseTransactions;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class VerifyPointSubmissionAction extends Action
{
    // use CanUseDatabaseTransactions;

    protected ?Closure $mutateRecordDataUsing = null;

    public static function getDefaultName(): ?string
    {
        return 'verify_submission';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('Verifikasi'));
        $this->icon('phosphor-checks-duotone');

        $this->modalHeading(__('Verifikasi Pengajuan AK'));

        $this->modalCancelAction(fn (StaticAction $action) => $action->label(__('close')));

        $this->form([
            ...ViewPointSubmission::getFormSubmissionView(true),
            Textarea::make('verifier_note'),
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

    }

    public function mutateRecordDataUsing(?Closure $callback): static
    {
        $this->mutateRecordDataUsing = $callback;

        return $this;
    }
}
