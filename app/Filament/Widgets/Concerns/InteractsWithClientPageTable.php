<?php

namespace App\Filament\Widgets\Concerns;

use App\Filament\Resources\ClientResource\Pages\ListClients;
use Filament\Widgets\Concerns\InteractsWithPageTable;

trait InteractsWithClientPageTable
{
    use InteractsWithPageTable;

    protected function getTablePage(): string
    {
        return ListClients::class;
    }
}
