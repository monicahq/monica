<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Models\User;
use App\Models\Vault;
use App\ViewModels\Vaults\VaultsViewModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VaultsViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_puts_the_vault_you_were_last_in_first(): void
    {
        $user = User::factory()->create();

        Vault::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Travail',
            'position' => 1,
            'last_visited_at' => now()->subWeek(),
        ]);

        Vault::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Famille',
            'position' => 2,
            'last_visited_at' => now()->subHour(),
        ]);

        $vaults = new VaultsViewModel(user: $user)->vaults();

        $this->assertSame(['Famille', 'Travail'], array_column($vaults, 'name'));
        $this->assertTrue($vaults[0]['isLastOpened']);
        $this->assertFalse($vaults[1]['isLastOpened']);
    }

    #[Test]
    public function a_vault_never_opened_falls_in_behind_the_ones_that_were(): void
    {
        $user = User::factory()->create();

        Vault::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Le club',
            'position' => 1,
            'last_visited_at' => null,
        ]);

        Vault::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Famille',
            'position' => 2,
            'last_visited_at' => now()->subDay(),
        ]);

        $this->assertSame(
            ['Famille', 'Le club'],
            array_column(new VaultsViewModel(user: $user)->vaults(), 'name'),
        );
    }

    #[Test]
    public function nothing_is_marked_as_last_opened_when_none_of_them_ever_was(): void
    {
        $user = User::factory()->create();

        Vault::factory()->count(2)->create([
            'account_id' => $user->account_id,
            'last_visited_at' => null,
        ]);

        $vaults = new VaultsViewModel(user: $user)->vaults();

        $this->assertSame([false, false], array_column($vaults, 'isLastOpened'));
    }

    #[Test]
    public function the_ones_never_opened_keep_the_order_they_were_given(): void
    {
        $user = User::factory()->create();

        Vault::factory()->create(['account_id' => $user->account_id, 'name' => 'First', 'position' => 1, 'last_visited_at' => null]);
        Vault::factory()->create(['account_id' => $user->account_id, 'name' => 'Second', 'position' => 2, 'last_visited_at' => null]);

        $this->assertSame(
            ['First', 'Second'],
            array_column(new VaultsViewModel(user: $user)->vaults(), 'name'),
        );
    }

    #[Test]
    public function it_counts_the_people_in_each_vault_in_words(): void
    {
        $user = User::factory()->create();

        Vault::factory()->create(['account_id' => $user->account_id, 'name' => 'Empty', 'position' => 1, 'contact_count' => 0]);
        Vault::factory()->create(['account_id' => $user->account_id, 'name' => 'One', 'position' => 2, 'contact_count' => 1]);
        Vault::factory()->create(['account_id' => $user->account_id, 'name' => 'Many', 'position' => 3, 'contact_count' => 12]);

        $counts = array_combine(
            array_column($vaults = new VaultsViewModel(user: $user)->vaults(), 'name'),
            array_column($vaults, 'contactCount'),
        );

        $this->assertSame('Nobody in it yet', $counts['Empty']);
        $this->assertSame('1 person', $counts['One']);
        $this->assertSame('12 people', $counts['Many']);
    }

    #[Test]
    public function the_mark_on_a_card_is_the_colour_somebody_chose_for_the_vault(): void
    {
        $user = User::factory()->create();

        Vault::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Famille',
            'colour' => '#2f7350',
        ]);

        $vault = new VaultsViewModel(user: $user)->vaults()[0];

        $this->assertSame('#2f7350', $vault['colour']);
        $this->assertSame('color-mix(in srgb, #2f7350 12%, transparent)', $vault['tint']);
        $this->assertSame('F', $vault['initial']);
    }

    #[Test]
    public function it_only_reaches_the_vaults_of_the_account(): void
    {
        $user = User::factory()->create();
        $stranger = User::factory()->create();

        Vault::factory()->create(['account_id' => $user->account_id, 'name' => 'Famille']);
        Vault::factory()->create(['account_id' => $stranger->account_id, 'name' => 'Sabre']);

        $this->assertSame(
            ['Famille'],
            array_column(new VaultsViewModel(user: $user)->vaults(), 'name'),
        );
    }
}
