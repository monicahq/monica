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
    public function it_offers_to_write_down_a_life_event_for_the_signed_in_person(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $michael = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id, 'first_name' => 'Michael', 'is_me' => true]);

        $form = new VaultDashboardViewModel(user: $user, vault: $vault)->lifeEventForm();

        $this->assertNotNull($form);
        $this->assertTrue($form->choosesSubject());
        $this->assertEquals(['id' => $michael->id, 'name' => 'Michael'], $form->projection()['subject']);
    }

    #[Test]
    public function it_offers_no_life_event_form_in_a_vault_without_the_signed_in_person(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->assertNull(new VaultDashboardViewModel(user: $user, vault: $vault)->lifeEventForm());
    }

    #[Test]
    public function it_shows_the_ten_latest_life_events_of_the_signed_in_person(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $michael = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id, 'first_name' => 'Michael', 'is_me' => true]);
        $jim = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id, 'first_name' => 'Jim']);

        for ($day = 1; $day <= 12; $day++) {
            $lifeEvent = LifeEvent::factory()->create(['vault_id' => $vault->id, 'title' => "Meeting {$day}", 'happened_on' => sprintf('2026-09-%02d', $day)]);
            LifeEventParticipant::factory()->create(['life_event_id' => $lifeEvent->id, 'participant_id' => $michael->id]);
        }

        $prank = LifeEvent::factory()->create(['vault_id' => $vault->id, 'title' => 'Prank on Dwight', 'happened_on' => '2026-09-30']);
        LifeEventParticipant::factory()->create(['life_event_id' => $prank->id, 'participant_id' => $jim->id]);

        $viewModel = new VaultDashboardViewModel(user: $user, vault: $vault, lifeEventJustAddedId: $lifeEvent->id);
        $entries = $viewModel->timeline()?->years()[0]['entries'] ?? [];

        $this->assertCount(10, $entries);
        $this->assertSame('Meeting 12', $entries[0]['title']);
        $this->assertTrue($entries[0]['isJustAdded']);
        $this->assertNotContains('Prank on Dwight', array_column($entries, 'title'));
        $this->assertTrue($viewModel->timeline()?->isReadOnly());
        $this->assertSame(route('contacts.show', ['vault' => $vault, 'contact' => $michael]), $viewModel->fullTimelineUrl());
    }

    #[Test]
    public function it_shows_no_timeline_in_a_vault_without_the_signed_in_person(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $viewModel = new VaultDashboardViewModel(user: $user, vault: $vault);

        $this->assertNull($viewModel->timeline());
        $this->assertNull($viewModel->fullTimelineUrl());
    }
}
