<?php

namespace App\Filament\Pages\Verification\Actions;

use Filament\Actions\Concerns\CanCustomizeProcess;
use Filament\Support\Colors\Color;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Arr;

class RejectClientIdentityAction extends Action
{
    use CanCustomizeProcess;

    public static function getDefaultName(): ?string
    {
        return "reject_identity";
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->label(__("Reject"));
        $this->icon("heroicon-o-x-circle");
        $this->color(Color::Red);

        $this->requiresConfirmation();

        $this->action(function (): void {
            $this->process(function (array $data, Model $record, Table $table) {
                $record->reject();
            });

            $this->success();
        });

    }
}
