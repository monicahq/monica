<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Layouts;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SettingsPageTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_leads_back_to_the_settings_unless_it_is_told_otherwise(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->blade('<x-settings.page title="Logs" current="logs" :user="$user">Anything</x-settings.page>', ['user' => $user])
            ->assertSee(route('settings.index'), escape: false)
            ->assertSee(__('Back to settings'))
            ->assertSee('Anything');
    }

    #[Test]
    public function a_screen_reached_from_elsewhere_says_where_it_goes_back_to(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->blade(
                '<x-settings.page title="Two-factor" current="security" :user="$user" :back-href="route(\'settings.security.index\')" :back-label="__(\'Back to security\')">Anything</x-settings.page>',
                ['user' => $user],
            )
            ->assertSee(route('settings.security.index'), escape: false)
            ->assertSee(__('Back to security'))
            ->assertDontSee(__('Back to settings'));
    }

    #[Test]
    public function a_screen_buried_deeper_may_show_a_trail_instead_of_one_way_back(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->blade(
                '<x-settings.page title="Share" current="templates" :user="$user" :crumbs="[[\'label\' => \'Templates\', \'url\' => \'/t\'], [\'label\' => \'Share\']]">Anything</x-settings.page>',
                ['user' => $user],
            )
            ->assertSee('aria-label="'.__('Breadcrumb').'"', escape: false)
            ->assertSee('Templates')
            ->assertDontSee(__('Back to settings'));
    }

    #[Test]
    public function it_hands_a_footer_on_to_the_shell_rather_than_swallowing_it(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->blade(
                '<x-settings.page title="Add a field" current="templates" :user="$user">Anything<x-slot:footer>Pick a type to carry on.</x-slot:footer></x-settings.page>',
                ['user' => $user],
            )
            ->assertSee('Pick a type to carry on.');
    }
}
