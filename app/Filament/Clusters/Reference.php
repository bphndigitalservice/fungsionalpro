<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class Reference extends Cluster
{
    public static function getNavigationLabel(): string
    {
        return __('labels.nav.references');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('labels.nav.system');
    }
}