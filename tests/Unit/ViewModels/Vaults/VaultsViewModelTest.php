<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Models\Contact;
use App\Models\Reminder;
use App\Models\ReminderOccurrence;
use App\Models\User;
use App\Models\Vault;
use App\ViewModels\Vaults\VaultsViewModel;
use Carbon\CarbonImmutable;
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
    public function it_shows_four_faces_and_says_how_many_it_left_out(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id, 'contact_count' => 18]);

        Contact::factory()->count(6)->create(['vault_id' => $vault->id]);

        $card = new VaultsViewModel(user: $user)->vaults()[0];

        $this->assertCount(4, $card['faces']);
    }

    #[Test]
    public function it_leaves_nobody_out_when_the_vault_holds_no_more_than_four(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id, 'contact_count' => 2]);

        Contact::factory()->count(2)->create(['vault_id' => $vault->id]);

        $card = new VaultsViewModel(user: $user)->vaults()[0];

        $this->assertCount(2, $card['faces']);
        $this->assertNull($card['moreFaces']);
    }

    #[Test]
    public function it_counts_the_dates_the_vault_owes_before_the_month_is_out(): void
    {
        $user = User::factory()->create(['timezone' => 'UTC']);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create(['vault_id' => $vault->id]);
        $reminder = Reminder::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'remindable_type' => $contact->getMorphClass(),
            'remindable_id' => $contact->id,
            'is_active' => true,
        ]);

        $thisMonth = CarbonImmutable::now()->startOfMonth()->addDay();

        foreach ([$thisMonth, $thisMonth->addDay(), CarbonImmutable::now()->endOfMonth()->addMonth()] as $when) {
            ReminderOccurrence::factory()->create([
                'account_id' => $user->account_id,
                'vault_id' => $vault->id,
                'reminder_id' => $reminder->id,
                'generation_key' => "1:{$when->toDateString()}",
                'scheduled_for' => $when,
                'local_date' => $when->toDateString(),
            ]);
        }

        $this->assertSame(2, new VaultsViewModel(user: $user)->vaults()[0]['dateCount']);
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
