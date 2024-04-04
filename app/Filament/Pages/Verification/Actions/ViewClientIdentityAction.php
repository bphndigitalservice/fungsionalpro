<?php

namespace App\Filament\Pages\Verification\Actions;

use App\Filament\Pages\Client\ClientProfilePage;
use App\Filament\Pages\Client\Point\Actions\ViewPointSubmission;
use App\Filament\Resources\ClientResource;
use Filament\Actions\StaticAction;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ViewClientIdentityAction extends Action
{

    protected ?Closure $mutateRecordDataUsing = null;

    public static function getDefaultName(): ?string
    {
        return 'verify_identity';
    }

    public function setUp(): void
    {
        parent::setUp();

        $this->label(__('Lihat'));
        $this->icon('heroicon-o-eye');
        $this->color("gray");

        $this->disabledForm();
        $this->modalHeading(__('Verifikasi Identitas JF'));

        $this->modalSubmitAction(false);

        $this->modalCancelAction(fn(StaticAction $action) => $action->label(__('close')));

        $this->form(ClientProfilePage::getClientIdentityForm());

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

        $this->action(static function (): void {
        });
    }
}
