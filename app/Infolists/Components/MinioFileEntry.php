<?php

namespace App\Infolists\Components;

use Filament\Infolists\Components\Entry;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Storage;

class MinioFileEntry extends Entry
{
    protected string $view = 'infolists.components.minio-file-entry';

    public function downloadUrl(): ?string
    {
        return Storage::temporaryUrl($this->getState(), now()->addMinutes(5));
    }

}
