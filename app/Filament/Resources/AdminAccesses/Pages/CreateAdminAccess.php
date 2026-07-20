<?php

namespace App\Filament\Resources\AdminAccesses\Pages;

use App\Filament\Resources\AdminAccesses\AdminAccessResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateAdminAccess extends CreateRecord
{
    protected static string $resource = AdminAccessResource::class;
}
