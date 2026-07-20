<?php

namespace App\Filament\Clusters\Reference;

use Filament\Clusters\Cluster;

class ReferenceCluster extends Cluster
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