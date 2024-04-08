<?php

namespace App\Concerns\Components;

use Filament\Resources\Concerns\HasTabs;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

trait HasCustomPageTab
{
    use HasTabs;
    use InteractsWithTable {
        makeTable as makeBaseTable;
    }

    protected function makeTable(): Table
    {
        return $this->makeBaseTable()
            ->query(fn(): Builder => $this->getTableQuery())
            ->modifyQueryUsing($this->modifyQueryWithActiveTab(...));
    }

}
