<?php

declare(strict_types=1);

namespace Tests\Feature\Views\Components;

use App\Models\Account;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BreadcrumbVaultTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_always_starts_at_the_vault(): void
    {
        $vault = Vault::factory()->create(['name' => 'Dunder Mifflin']);

        $this->blade('<x-breadcrumb.vault :vault="$vault" />', ['vault' => $vault])
            ->assertSee('Dunder Mifflin')
            ->assertSee('aria-current="page"', escape: false);
    }

    #[Test]
    public function the_vault_becomes_a_link_once_something_follows_it(): void
    {
        $vault = Vault::factory()->create(['name' => 'Dunder Mifflin']);

        $this->blade(
            '<x-breadcrumb.vault :vault="$vault" :items="[[\'label\' => \'People\']]" />',
            ['vault' => $vault],
        )
            ->assertSee(route('vaults.show', $vault), escape: false)
            ->assertSee('Dunder Mifflin')
            ->assertSee('People');
    }

    #[Test]
    public function the_switcher_lists_every_vault_of_the_account_and_how_many_people_each_holds(): void
    {
        $account = Account::factory()->create();
        $here = Vault::factory()->create(['account_id' => $account->id, 'name' => 'Dunder Mifflin']);
        $other = Vault::factory()->create(['account_id' => $account->id, 'name' => 'Vance Refrigeration', 'contact_count' => 4]);

        $rendered = $this->blade('<x-breadcrumb.vault :vault="$vault" />', ['vault' => $here]);

        $rendered->assertSee('Vance Refrigeration');
        $rendered->assertSee(route('vaults.show', $other), escape: false);
    }

    #[Test]
    public function the_switcher_leaves_out_the_vaults_of_another_account(): void
    {
        $account = Account::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $account->id]);
        Vault::factory()->create(['name' => 'Sabre']);

        $this->blade('<x-breadcrumb.vault :vault="$vault" />', ['vault' => $vault])
            ->assertDontSee('Sabre');
    }

    #[Test]
    public function the_vault_you_are_in_is_marked_by_more_than_a_tint(): void
    {
        $account = Account::factory()->create();
        $here = Vault::factory()->create(['account_id' => $account->id, 'name' => 'Dunder Mifflin']);
        Vault::factory()->create(['account_id' => $account->id, 'name' => 'Vance Refrigeration']);

        $this->blade('<x-breadcrumb.vault :vault="$vault" />', ['vault' => $here])
            ->assertSee('aria-current="true"', escape: false);
    }

    #[Test]
    public function the_crumb_opens_the_switcher_and_says_so_when_there_is_a_choice(): void
    {
        $account = Account::factory()->create();
        $here = Vault::factory()->create(['account_id' => $account->id, 'name' => 'Dunder Mifflin']);
        Vault::factory()->create(['account_id' => $account->id, 'name' => 'Vance Refrigeration']);

        $rendered = $this->blade('<x-breadcrumb.vault :vault="$vault" />', ['vault' => $here]);

        $rendered->assertSee('aria-expanded', escape: false);
        $rendered->assertSee('aria-controls="vault-switcher-'.$here->id.'"', escape: false);
    }

    #[Test]
    public function the_switcher_offers_a_new_vault(): void
    {
        $account = Account::factory()->create();
        $here = Vault::factory()->create(['account_id' => $account->id]);
        Vault::factory()->create(['account_id' => $account->id]);

        $this->blade('<x-breadcrumb.vault :vault="$vault" />', ['vault' => $here])
            ->assertSee(route('vaults.new'), escape: false);
    }

    #[Test]
    public function a_single_vault_is_not_offered_a_choice(): void
    {
        $account = Account::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $account->id, 'name' => 'Dunder Mifflin']);

        $rendered = $this->blade(
            '<x-breadcrumb.vault :vault="$vault" :items="[[\'label\' => \'People\']]" />',
            ['vault' => $vault],
        );

        $rendered->assertSee('Dunder Mifflin');
        $rendered->assertDontSee('aria-expanded', escape: false);
        $rendered->assertSee(route('vaults.show', $vault), escape: false);
    }

    #[Test]
    public function the_screen_you_are_on_is_named_but_does_not_lead_anywhere(): void
    {
        $vault = Vault::factory()->create(['name' => 'Dunder Mifflin']);

        $rendered = $this->blade(
            '<x-breadcrumb.vault :vault="$vault" :items="[[\'label\' => \'People\', \'url\' => \'/people\'], [\'label\' => \'Michael Scott\', \'url\' => \'/michael\']]" />',
            ['vault' => $vault],
        );

        $rendered->assertSee('href="/people"', escape: false);
        $rendered->assertDontSee('href="/michael"', escape: false);
        $rendered->assertSee('aria-current="page"', escape: false);
    }
}
