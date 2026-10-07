<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Enums\DatePrecisionEnum;
use App\Models\Contact;
use App\Models\LifeEvent;
use App\Models\LifeEventParticipant;
use App\Models\User;
use App\Models\Vault;
use App\ViewModels\Vaults\VaultDashboardViewModel;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VaultDashboardViewModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_says_what_the_vault_is_called_and_what_it_is_for(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create([
            'account_id' => $user->account_id,
            'name' => 'Dunder Mifflin',
            'description' => 'Everybody at the Scranton branch',
        ]);

        $viewModel = new VaultDashboardViewModel(user: $user, vault: $vault);

        $this->assertEquals('Dunder Mifflin', $viewModel->title());
        $this->assertEquals('Everybody at the Scranton branch', $viewModel->description());
        $this->assertTrue($vault->is($viewModel->vault()));
    }

    #[Test]
    public function it_marks_the_vault_with_its_initial_and_its_colour(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id, 'name' => 'élan', 'colour' => '#3b82f6']);

        $tile = new VaultDashboardViewModel(user: $user, vault: $vault)->tile();

        $this->assertSame('É', $tile['initial']);
        $this->assertSame('#3b82f6', $tile['colour']);
    }

    #[Test]
    public function it_counts_what_the_vault_holds_the_way_somebody_would_say_it(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-02 12:00:00', 'UTC'));
        $user = User::factory()->create(['timezone' => 'UTC']);
        $vault = Vault::factory()->create(['account_id' => $user->account_id, 'contact_count' => 1]);

        Contact::factory()->create(['vault_id' => $vault->id, 'first_name' => 'Jim', 'last_name' => 'Halpert', 'is_favourite' => true]);
        LifeEvent::factory()->create(['vault_id' => $vault->id, 'happened_on' => '2026-07-03']);
        LifeEvent::factory()->create(['vault_id' => $vault->id, 'happened_on' => '2026-09-30']);
        LifeEvent::factory()->create(['vault_id' => $vault->id, 'happened_on' => '2026-07-01']);

        $this->assertSame(
            ['1 person', '0 reminders this week', '1 favourite', '2 life events in the last three months'],
            new VaultDashboardViewModel(user: $user, vault: $vault)->figures(),
        );
    }

    #[Test]
    public function it_counts_a_life_event_from_today_but_not_one_from_tomorrow(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-02 12:00:00', 'UTC'));
        $user = User::factory()->create(['timezone' => 'UTC']);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        LifeEvent::factory()->create(['vault_id' => $vault->id, 'happened_on' => CarbonImmutable::parse('2026-10-02 09:00:00')]);
        LifeEvent::factory()->create(['vault_id' => $vault->id, 'happened_on' => CarbonImmutable::parse('2026-10-03 09:00:00')]);

        $this->assertSame(
            '1 life event in the last three months',
            new VaultDashboardViewModel(user: $user, vault: $vault)->figures()[3],
        );
    }

    #[Test]
    public function it_lists_the_favourites_in_name_order_and_points_at_the_rest_beyond_ten(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        Contact::factory()->create(['vault_id' => $vault->id, 'first_name' => 'Pam', 'last_name' => 'Beesly', 'is_favourite' => true]);
        Contact::factory()->create(['vault_id' => $vault->id, 'first_name' => 'Angela', 'last_name' => 'Martin', 'is_favourite' => true]);
        Contact::factory()->create(['vault_id' => $vault->id, 'first_name' => 'Creed', 'last_name' => 'Bratton']);
        Contact::factory()->archived()->create(['vault_id' => $vault->id, 'first_name' => 'Roy', 'last_name' => 'Anderson', 'is_favourite' => true]);
        Contact::factory()->partial()->create(['vault_id' => $vault->id, 'first_name' => 'Todd', 'is_favourite' => true]);

        $viewModel = new VaultDashboardViewModel(user: $user, vault: $vault);

        $this->assertSame(['Angela Martin', 'Pam Beesly'], array_column($viewModel->favourites(), 'name'));
        $this->assertNull($viewModel->moreFavouritesUrl());

        Contact::factory()->count(9)->create(['vault_id' => $vault->id, 'is_favourite' => true]);

        $viewModel = new VaultDashboardViewModel(user: $user, vault: $vault);

        $this->assertCount(10, $viewModel->favourites());
        $this->assertSame(
            route('contacts.index', ['vault' => $vault, 'filter' => 'favourites']),
            $viewModel->moreFavouritesUrl(),
        );
    }

    #[Test]
    public function it_lists_the_people_last_opened_newest_first_and_says_when(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-02 12:00:00', 'UTC'));
        $user = User::factory()->create(['timezone' => 'UTC']);
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $other = Vault::factory()->create(['account_id' => $user->account_id]);

        Contact::factory()->create(['vault_id' => $vault->id, 'first_name' => 'Oscar', 'last_name' => 'Martinez', 'last_consulted_at' => '2026-09-29 10:00:00']);
        Contact::factory()->create(['vault_id' => $vault->id, 'first_name' => 'Kelly', 'last_name' => 'Kapoor', 'last_consulted_at' => '2026-10-02 08:00:00']);
        Contact::factory()->create(['vault_id' => $vault->id, 'first_name' => 'Ryan', 'last_name' => 'Howard', 'last_consulted_at' => '2026-10-01 18:00:00']);
        Contact::factory()->create(['vault_id' => $vault->id, 'first_name' => 'Toby', 'last_name' => 'Flenderson', 'last_consulted_at' => null]);
        Contact::factory()->create(['vault_id' => $other->id, 'first_name' => 'Holly', 'last_name' => 'Flax', 'last_consulted_at' => '2026-10-02 09:00:00']);

        $people = new VaultDashboardViewModel(user: $user, vault: $vault)->recentlyViewed();

        $this->assertSame(['Kelly Kapoor', 'Ryan Howard', 'Oscar Martinez'], array_column($people, 'name'));
        $this->assertSame('Today', $people[0]['when']);
        $this->assertSame('Yesterday', $people[1]['when']);
        $this->assertNotContains($people[2]['when'], ['Today', 'Yesterday']);
        $this->assertStringContainsString('29', $people[2]['when']);
    }

    #[Test]
    public function it_lists_the_latest_life_events_with_everybody_who_was_there(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $wedding = LifeEvent::factory()->create(['vault_id' => $vault->id, 'title' => 'Wedding', 'happened_on' => '2026-09-02']);
        $jim = Contact::factory()->create(['vault_id' => $vault->id, 'first_name' => 'Jim', 'last_name' => 'Halpert']);
        $pam = Contact::factory()->create(['vault_id' => $vault->id, 'first_name' => 'Pam', 'last_name' => 'Beesly']);
        LifeEventParticipant::factory()->create(['life_event_id' => $wedding->id, 'participant_id' => $jim->id]);
        LifeEventParticipant::factory()->create(['life_event_id' => $wedding->id, 'participant_id' => $pam->id, 'position' => 1]);

        LifeEvent::factory()->count(5)->create(['vault_id' => $vault->id, 'happened_on' => '2026-09-20']);
        LifeEvent::factory()->create(['vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id, 'happened_on' => '2026-09-25']);

        $events = new VaultDashboardViewModel(user: $user, vault: $vault)->lifeEvents();

        $this->assertCount(5, $events);
        $this->assertNotContains('Wedding', array_column($events, 'title'));

        LifeEvent::query()->where('id', '!=', $wedding->id)->delete();

        $events = new VaultDashboardViewModel(user: $user, vault: $vault)->lifeEvents();

        $this->assertSame('Wedding', $events[0]['title']);
        $this->assertSame('Jim Halpert, Pam Beesly', $events[0]['who']);
        $this->assertSame(route('contacts.show', ['vault' => $vault, 'contact' => $jim]), $events[0]['url']);
    }

    #[Test]
    public function it_leads_nowhere_from_a_life_event_whose_people_are_only_names(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $lunch = LifeEvent::factory()->create(['vault_id' => $vault->id, 'title' => 'Lunch']);
        $todd = Contact::factory()->partial()->create(['vault_id' => $vault->id, 'first_name' => 'Todd']);
        LifeEventParticipant::factory()->create(['life_event_id' => $lunch->id, 'participant_id' => $todd->id]);

        $this->assertNull(new VaultDashboardViewModel(user: $user, vault: $vault)->lifeEvents()[0]['url']);
    }

    #[Test]
    public function it_writes_a_life_event_date_no_more_precisely_than_it_was_recorded(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        LifeEvent::factory()->create(['vault_id' => $vault->id, 'happened_on' => '1994-06-01', 'date_precision' => DatePrecisionEnum::Year]);

        $this->assertSame('1994', new VaultDashboardViewModel(user: $user, vault: $vault)->lifeEvents()[0]['date']);
    }

    #[Test]
    public function it_says_where_every_box_leads(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $viewModel = new VaultDashboardViewModel(user: $user, vault: $vault);

        $this->assertEquals(route('vaults.index'), $viewModel->vaultsUrl());
        $this->assertEquals(route('contacts.new', $vault), $viewModel->newContactUrl());
        $this->assertEquals(route('contacts.index', $vault), $viewModel->allContactsUrl());
    }
}
