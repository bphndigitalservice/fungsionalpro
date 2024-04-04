<?php

namespace App\Filament\Pages\Verification\Actions;

use Filament\Actions\Concerns\CanCustomizeProcess;
use Filament\Support\Colors\Color;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class AcceptClientIdentityAction extends Action
{

    use CanCustomizeProcess;

    public static function getDefaultName(): ?string
    {
        return "accept_identity";
    }

    public function setUp(): void
    {
        parent::setUp();
        $this->label(__("Accept"));
        $this->icon("heroicon-o-check-badge");
        $this->color(Color::Green);

        $this->requiresConfirmation();

        $this->action(function (): void {
            $this->process(function (array $data, Model $record, Table $table) {
                $record->verified();
            });

            $this->success();
        });

    }
}
