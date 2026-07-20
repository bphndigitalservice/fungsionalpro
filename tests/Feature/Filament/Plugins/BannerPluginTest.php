<?php

namespace Tests\Feature\Filament\Plugins;

use App\Enums\SystemRole;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kenepa\Banner\BannerPlugin;
use Kenepa\Banner\Livewire\BannerManagerPage;
use Livewire\Livewire;
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

    public function test_banner_plugin_is_configured_with_banner_manager_permission(): void
    {
        $this->setUpFilamentPanel();

        /** @var BannerPlugin $plugin */
        $plugin = Filament::getCurrentPanel()->getPlugin('banner');

        $this->assertSame('banner-manager', $plugin->getBannerManagerAccessPermission());
    }

    public function test_user_with_banner_manager_permission_can_access_banner_manager(): void
    {
        $user = $this->createUserWithPermissions(['banner-manager']);
        $this->actingAsFilamentUser($user);

        Livewire::test(BannerManagerPage::class)
            ->assertSuccessful();
    }

    public function test_user_without_banner_manager_permission_cannot_access_banner_manager(): void
    {
        $user = $this->createUserWithPermissions([], SystemRole::Admin);
        $this->actingAsFilamentUser($user);

        Livewire::test(BannerManagerPage::class)
            ->assertForbidden();
    }
}
