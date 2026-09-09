<?php

namespace App\Infolists\Components;

use Closure;
use Filament\Infolists\Components\Entry;
use Illuminate\Support\Facades\Storage;

class MinioFileEntry extends Entry
{
    protected string $view = 'infolists.components.minio-file-entry';

    protected string | Closure | null $disk = null;

    public function disk(string | Closure | null $disk): static
    {
        $this->disk = $disk;

        return $this;
    }

    public function downloadUrl(): ?string
    {
        $path = $this->getState();

        if (blank($path)) {
            return null;
        }

        $disk = $this->evaluate($this->disk);
        $storage = $disk ? Storage::disk($disk) : Storage::disk();

        return $storage->temporaryUrl($path, now()->addMinutes(10));
    }
}
