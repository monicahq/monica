<?php

declare(strict_types=1);

namespace Tests\Unit\ViewModels\Vaults;

use App\Models\Contact;
use App\Models\LifeEvent;
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
    public function it_counts_every_life_event_of_the_vault_on_the_timeline_tab(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        LifeEvent::factory()->count(3)->create(['vault_id' => $vault->id]);

        $this->assertSame(3, new VaultDashboardViewModel(user: $user, vault: $vault)->timelineCount());
    }
}
