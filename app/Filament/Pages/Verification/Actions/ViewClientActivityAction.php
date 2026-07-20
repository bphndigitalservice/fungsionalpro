<?php

namespace App\Filament\Pages\Verification\Actions;

use Filament\Actions\Action;
use App\Filament\Resources\ClientActivityResource;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Closure;

class ViewClientActivityAction extends Action
{
    protected ?Closure $mutateRecordDataUsing = null;

    public static function getDefaultName(): ?string
    {
        return 'view_activity';
    }

    public function setUp(): void
    {
        parent::setUp();

        $this->label(__('Lihat'));
        $this->icon('heroicon-o-eye');
        $this->color('gray');

        $this->disabledForm();
        $this->modalHeading(__('Verifikasi Pelaporan Kegiatan'));
        $this->modalSubmitAction(false);

        $this->modalCancelAction(fn (Action $action)=> $action->label(__('close')));

        $this->form(ClientActivityResource::getFormSchema());

        $this->fillForm(function (Model $record, Table $table): array {
            if (
                $translatableContentDriver =
                    $table->makeTranslatableContentDriver()
            ) {
                $data =
                    $translatableContentDriver
                        ->getRecordAttributesToArray($record);
            } else {
                $data =
                    $record->attributesToArray();
            }
            if ($this->mutateRecordDataUsing) {
                $data =
                    $this->evaluate(
                        $this->mutateRecordDataUsing,
                        ['data'=>$data]
                    );
            }
            return $data;
        });

        $this->action(static function (): void {
        });
    }
}