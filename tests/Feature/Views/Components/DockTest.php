<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components;

use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DockTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_carries_four_destinations_and_the_vault_chip(): void
    {
        $user = User::factory()->create();

        $vault = Vault::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Dunder Mifflin',
        ]);

        $rendered = $this->blade('<x-dock :user="$user" :vault="$vault" />', ['user' => $user, 'vault' => $vault]);

        $rendered->assertSee('aria-label="Main"', escape: false);
        $rendered->assertSee('Dunder Mifflin', escape: false);
        $rendered->assertSee('Dashboard', escape: false);
        $rendered->assertSee('People', escape: false);
        $rendered->assertSee('Journal', escape: false);
        $rendered->assertSee('Reminders', escape: false);
        $rendered->assertSee(route('vaults.show', $vault), escape: false);
        $rendered->assertSee(route('contacts.index', $vault), escape: false);
    }

    #[Test]
    public function it_draws_the_destinations_that_do_not_exist_yet_without_offering_them(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $rendered = $this->blade('<x-dock :user="$user" :vault="$vault" />', ['user' => $user, 'vault' => $vault]);

        $this->assertSame(2, substr_count($rendered->__toString(), 'max-[720px]:flex-1 text-placeholder'));
    }

    #[Test]
    public function it_marks_where_you_are_with_aria_current_and_never_with_colour_alone(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->blade('<x-dock :user="$user" :vault="$vault" current="people" />', ['user' => $user, 'vault' => $vault])
            ->assertSee('aria-current="page"', escape: false);

        $this->blade('<x-dock :user="$user" :vault="$vault" />', ['user' => $user, 'vault' => $vault])
            ->assertDontSee('aria-current="page"', escape: false);
    }

    #[Test]
    public function it_points_at_the_default_vault_when_it_is_given_none(): void
    {
        $user = User::factory()->create();

        Vault::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Threat Level Midnight',
            'position' => 1,
        ]);

        $default = Vault::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Dunder Mifflin',
            'position' => 2,
            'is_default' => true,
        ]);

        $rendered = $this->blade('<x-dock :user="$user" />', ['user' => $user]);

        $rendered->assertSee('Dunder Mifflin', escape: false);
        $rendered->assertSee(route('vaults.show', $default), escape: false);
    }

    #[Test]
    public function it_points_at_the_first_vault_when_none_is_the_default(): void
    {
        $user = User::factory()->create();

        $first = Vault::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Dunder Mifflin',
            'position' => 1,
        ]);

        Vault::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Threat Level Midnight',
            'position' => 2,
        ]);

        $this->blade('<x-dock :user="$user" />', ['user' => $user])
            ->assertSee(route('vaults.show', $first), escape: false);
    }

    #[Test]
    public function it_keeps_only_the_chip_when_there_is_no_vault_to_point_at(): void
    {
        $user = User::factory()->create();

        $rendered = $this->blade('<x-dock :user="$user" />', ['user' => $user]);

        $rendered->assertSee('bg-action-strong', escape: false);
        $rendered->assertSee(config('app.name'), escape: false);
        $rendered->assertDontSee('aria-current="page"', escape: false);
        $rendered->assertDontSee('h-8 w-px bg-hairline', escape: false);
    }

    #[Test]
    public function it_opens_the_menu_from_the_chip_from_the_keyboard_and_closes_it_on_escape(): void
    {
        $user = User::factory()->create();

        $rendered = $this->blade('<x-dock :user="$user" />', ['user' => $user]);

        $rendered->assertSee('@click="open = ! open"', escape: false);
        $rendered->assertSee('keydown.window.meta.k.prevent', escape: false);
        $rendered->assertSee('keydown.window.ctrl.k.prevent', escape: false);
        $rendered->assertSee('keydown.escape.window', escape: false);
        $rendered->assertSee('fixed inset-0 z-49 bg-scrim', escape: false);
        $rendered->assertSee('aria-haspopup="menu"', escape: false);
    }

    #[Test]
    public function it_keeps_the_keyboard_inside_the_menu_while_it_is_open(): void
    {
        $user = User::factory()->create();

        $rendered = $this->blade('<x-dock :user="$user" />', ['user' => $user]);

        $rendered->assertSee('x-trap.noscroll.inert="open"', escape: false);
        $rendered->assertSee('role="dialog"', escape: false);
        $rendered->assertSee('aria-modal="true"', escape: false);
    }

    #[Test]
    public function it_puts_everything_the_dock_does_not_carry_in_the_menu(): void
    {
        $user = User::factory()->create();

        $vault = Vault::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Dunder Mifflin',
            'position' => 1,
        ]);

        $other = Vault::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Threat Level Midnight',
            'position' => 2,
        ]);

        $rendered = $this->blade('<x-dock :user="$user" :vault="$vault" />', ['user' => $user, 'vault' => $vault]);

        $rendered->assertSee('This vault', escape: false);
        $rendered->assertSee('Everything else', escape: false);
        $rendered->assertSee(route('vaults.settings.index', $vault), escape: false);
        $rendered->assertSee(route('settings.index'), escape: false);
        $rendered->assertSee(route('vaults.index'), escape: false);

        $rendered->assertSee(route('vaults.show', $other), escape: false);
        $this->assertSame(1, substr_count($rendered->__toString(), 'Another vault'));
    }

    #[Test]
    public function it_never_offers_the_vault_the_chip_already_carries(): void
    {
        $user = User::factory()->create();

        $vault = Vault::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Dunder Mifflin',
        ]);

        $rendered = $this->blade('<x-dock :user="$user" :vault="$vault" />', ['user' => $user, 'vault' => $vault]);

        $rendered->assertDontSee('Another vault', escape: false);
    }

    #[Test]
    public function it_only_reaches_the_vaults_of_the_account(): void
    {
        $user = User::factory()->create();
        $stranger = User::factory()->create();

        $theirs = Vault::factory()->create([
            'account_id' => $stranger->account_id,
            'name' => 'Sabre',
        ]);

        $rendered = $this->blade('<x-dock :user="$user" />', ['user' => $user]);

        $rendered->assertDontSee('Sabre', escape: false);
        $rendered->assertDontSee(route('vaults.show', $theirs), escape: false);
    }

    #[Test]
    public function it_signs_out_with_a_form_rather_than_a_link(): void
    {
        $user = User::factory()->create();

        $rendered = $this->blade('<x-dock :user="$user" />', ['user' => $user])->__toString();

        $this->assertStringContainsString('action="'.route('auth.login.destroy').'"', $rendered);
        $this->assertStringNotContainsString('href="'.route('auth.login.destroy').'"', $rendered);
        $this->assertStringContainsString('name="_token"', $rendered);
    }

    #[Test]
    public function it_draws_the_search_field_that_has_nothing_behind_it_yet(): void
    {
        $user = User::factory()->create();

        $rendered = $this->blade('<x-dock :user="$user" />', ['user' => $user]);

        $rendered->assertSee('Search people, notes, anything…', escape: false);
        $rendered->assertSee('<input', escape: false);
    }
}
