<?php

namespace Tests\Feature\Filament\Plugins;

use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kenepa\Banner\BannerPlugin;
use Tests\Concerns\InteractsWithFilament;
use Tests\TestCase;

class BannerPluginTest extends TestCase
{
    use InteractsWithFilament;
    use RefreshDatabase;

    public function test_banner_plugin_is_registered_on_admin_panel(): void
    {
        $this->setUpFilamentPanel();

        $plugins = collect(Filament::getCurrentPanel()->getPlugins())
            ->map(fn ($plugin) => $plugin::class);

        $this->assertTrue($plugins->contains(BannerPlugin::class));
    }
}
