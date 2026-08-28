<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components;

use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SidebarTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_vault_column_offers_the_destinations_of_that_vault(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id, 'name' => 'Dunder Mifflin']);

        $rendered = $this->actingAs($user)->blade(
            '<x-vault.sidebar :user="$user" :vault="$vault" current="dashboard" />',
            ['user' => $user, 'vault' => $vault],
        );

        $rendered->assertSee(route('vaults.show', $vault), escape: false);
        $rendered->assertSee(route('contacts.index', $vault), escape: false);
        $rendered->assertSee(route('vaults.settings.index', $vault), escape: false);
        $rendered->assertSee('Dashboard');
        $rendered->assertSee('People');
        $rendered->assertSee('Vault settings');
    }

    #[Test]
    public function the_vault_column_says_where_you_are_with_more_than_a_colour(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)->blade(
            '<x-vault.sidebar :user="$user" :vault="$vault" current="contacts" />',
            ['user' => $user, 'vault' => $vault],
        )->assertSee('aria-current="page"', escape: false);
    }

    #[Test]
    public function the_vault_column_draws_what_has_not_been_built_without_offering_it(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $rendered = $this->actingAs($user)->blade(
            '<x-vault.sidebar :user="$user" :vault="$vault" />',
            ['user' => $user, 'vault' => $vault],
        );

        $rendered->assertSee('Journal');
        $rendered->assertSee('Reminders');
        $rendered->assertSee('aria-disabled="true"', escape: false);
    }

    #[Test]
    public function the_switcher_lists_every_vault_and_does_not_link_to_the_one_you_are_in(): void
    {
        $user = User::factory()->create();
        $here = Vault::factory()->create(['account_id' => $user->account_id, 'name' => 'Dunder Mifflin']);
        $other = Vault::factory()->create(['account_id' => $user->account_id, 'name' => 'Vance Refrigeration']);

        $rendered = $this->actingAs($user)->blade(
            '<x-vault.sidebar :user="$user" :vault="$vault" />',
            ['user' => $user, 'vault' => $here],
        );

        $rendered->assertSee('Vance Refrigeration');
        $rendered->assertSee(route('vaults.show', $other), escape: false);
        $rendered->assertSee('You are here');
        $rendered->assertSee('All your vaults');
    }

    #[Test]
    public function the_switcher_leaves_out_the_vaults_of_another_account(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        Vault::factory()->create(['name' => 'Sabre']);

        $this->actingAs($user)->blade(
            '<x-vault.sidebar :user="$user" :vault="$vault" />',
            ['user' => $user, 'vault' => $vault],
        )->assertDontSee('Sabre');
    }

    #[Test]
    public function the_settings_column_offers_the_settings_and_the_way_out_of_them(): void
    {
        $user = User::factory()->create();

        $rendered = $this->actingAs($user)->blade(
            '<x-settings.sidebar :user="$user" current="profile" />',
            ['user' => $user],
        );

        $rendered->assertSee(route('settings.profile.index'), escape: false);
        $rendered->assertSee(route('settings.templates.index'), escape: false);
        $rendered->assertSee(route('vaults.index'), escape: false);
        $rendered->assertSee('Leave the settings');
        $rendered->assertSee('aria-current="page"', escape: false);
    }

    #[Test]
    public function both_columns_carry_who_is_signed_in_and_the_way_out(): void
    {
        $user = User::factory()->create(['first_name' => 'Pam', 'last_name' => 'Beesly']);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        foreach ([
            ['<x-vault.sidebar :user="$user" :vault="$vault" />', ['user' => $user, 'vault' => $vault]],
            ['<x-settings.sidebar :user="$user" />', ['user' => $user]],
        ] as [$template, $data]) {
            $rendered = $this->actingAs($user)->blade($template, $data);

            $rendered->assertSee('Pam Beesly');
            $rendered->assertSee('id="shell-avatar"', escape: false);
            $rendered->assertSee('aria-haspopup="menu"', escape: false);
            $rendered->assertSee(route('settings.index'), escape: false);
            $rendered->assertSee('Account settings');
            $rendered->assertSee('Sign out');
        }
    }

    #[Test]
    public function the_way_into_the_settings_and_the_way_out_are_kept_behind_the_name(): void
    {
        $user = User::factory()->create(['first_name' => 'Pam', 'last_name' => 'Beesly']);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $rendered = $this->actingAs($user)->blade(
            '<x-vault.sidebar :user="$user" :vault="$vault" />',
            ['user' => $user, 'vault' => $vault],
        );

        $rendered->assertSee('role="menu"', escape: false);
        $rendered->assertSee('x-show="open"', escape: false);
        $rendered->assertSee(route('auth.login.destroy'), escape: false);
    }

    #[Test]
    public function both_columns_are_dark_chrome_rather_than_a_surface_of_the_page(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        foreach ([
            ['<x-vault.sidebar :user="$user" :vault="$vault" />', ['user' => $user, 'vault' => $vault]],
            ['<x-settings.sidebar :user="$user" />', ['user' => $user]],
        ] as [$template, $data]) {
            $rendered = $this->actingAs($user)->blade($template, $data);

            $rendered->assertSee('border-r border-shell-hairline bg-shell', escape: false);
            $rendered->assertSee('text-shell-body hover:bg-shell-hover hover:text-shell-ink', escape: false);
            $rendered->assertDontSee('bg-sunken', escape: false);
        }
    }

    #[Test]
    public function the_destination_you_are_on_is_a_light_pill_rather_than_a_bordered_card(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)->blade(
            '<x-vault.sidebar :user="$user" :vault="$vault" current="contacts" />',
            ['user' => $user, 'vault' => $vault],
        )->assertSee('bg-shell-active font-semibold text-shell-active-ink', escape: false);
    }

    #[Test]
    public function both_columns_are_the_drawer_the_bar_opens(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        foreach ([
            ['<x-vault.sidebar :user="$user" :vault="$vault" />', ['user' => $user, 'vault' => $vault]],
            ['<x-settings.sidebar :user="$user" />', ['user' => $user]],
        ] as [$template, $data]) {
            $rendered = $this->actingAs($user)->blade($template, $data);

            $rendered->assertSee('id="main-nav"', escape: false);
            $rendered->assertSee('max-lg:-translate-x-full', escape: false);
            $rendered->assertSee('Close the menu');
        }
    }
}
