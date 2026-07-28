<?php

namespace App\Filament\Widgets\Concerns;

use App\Filament\Resources\MasterJfResource\Pages\ListMasterJfs;
use Filament\Widgets\Concerns\InteractsWithPageTable;

trait InteractsWithMasterJfPageTable
{
    use InteractsWithPageTable;

    protected function getTablePage(): string
    {
        return ListMasterJfs::class;
    }
}
