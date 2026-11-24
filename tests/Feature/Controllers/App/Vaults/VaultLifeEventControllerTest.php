<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

use App\Models\Contact;
use App\Models\LifeEvent;
use App\Models\LifeEventParticipant;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VaultLifeEventControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_opens_the_panel_on_the_dashboard(): void
    {
        [$user, $vault] = $this->michael();

        $this->actingAs($user)
            ->get(route('vaults.lifeEvents.new', $vault))
            ->assertOk()
            ->assertSee('x-data="lifeEventPanel(', escape: false)
            ->assertSee('aria-controls="life-event-panel"', escape: false);
    }

    #[Test]
    public function it_records_a_life_event_for_the_signed_in_person_from_the_dashboard(): void
    {
        Queue::fake();

        [$user, $vault, $michael] = $this->michael();
        $promotion = $this->lifeEventTypeAsking($user, 'Promotion');

        $this->actingAs($user)
            ->post(route('vaults.lifeEvents.create', $vault), [
                'contact_id' => $michael->id,
                'life_event_type_id' => $promotion->id,
                'happened_on' => '2026-10-11',
            ])
            ->assertRedirect(route('vaults.show', ['vault' => $vault, 'slice' => '2026']))
            ->assertSessionHas('status')
            ->assertSessionHas('lifeEventJustAdded', LifeEvent::query()->sole()->id);

        $this->assertDatabaseHas('life_events', ['life_event_type_id' => $promotion->id, 'created_from' => 'dashboard']);
        $this->assertDatabaseHas('life_event_participants', ['participant_id' => $michael->id, 'position' => 0]);
    }

    #[Test]
    public function it_does_not_find_somebody_of_another_vault(): void
    {
        [$user, $vault] = $this->michael();
        $stamford = Vault::factory()->create(['account_id' => $user->account_id]);
        $karen = $this->contactOn($stamford, 'Karen');

        $this->actingAs($user)
            ->post(route('vaults.lifeEvents.create', $vault), [
                'contact_id' => $karen->id,
                'life_event_type_id' => $this->lifeEventTypeAsking($user, 'Transfer')->id,
                'happened_on' => '2026-10-11',
            ])
            ->assertNotFound();

        $this->assertSame(0, LifeEvent::query()->count());
    }

    #[Test]
    public function it_refuses_a_life_event_for_nobody(): void
    {
        [$user, $vault] = $this->michael();

        $this->actingAs($user)
            ->post(route('vaults.lifeEvents.create', $vault), [
                'life_event_type_id' => $this->lifeEventTypeAsking($user, 'Promotion')->id,
                'happened_on' => '2026-10-11',
            ])
            ->assertSessionHasErrors('contact_id');

        $this->assertSame(0, LifeEvent::query()->count());
    }

    #[Test]
    public function it_opens_the_timeline_on_what_is_coming_up_after_recording_a_life_event_still_to_come(): void
    {
        Queue::fake();

        [$user, $vault, $michael] = $this->michael();
        $party = $this->lifeEventTypeAsking($user, 'Party');

        $this->actingAs($user)
            ->post(route('vaults.lifeEvents.create', $vault), [
                'contact_id' => $michael->id,
                'life_event_type_id' => $party->id,
                'happened_on' => now()->addMonth()->toDateString(),
            ])
            ->assertRedirect(route('vaults.show', ['vault' => $vault, 'slice' => 'upcoming']));
    }

    #[Test]
    public function it_comes_back_with_the_reason_when_the_type_can_no_longer_be_chosen(): void
    {
        [$user, $vault, $michael] = $this->michael();
        $fax = $this->lifeEventTypeAsking($user, 'Fax');
        $fax->update(['is_active' => false]);

        $this->actingAs($user)
            ->from(route('vaults.show', $vault))
            ->post(route('vaults.lifeEvents.create', $vault), [
                'contact_id' => $michael->id,
                'life_event_type_id' => $fax->id,
                'happened_on' => '2026-10-11',
            ])
            ->assertRedirect(route('vaults.show', $vault))
            ->assertSessionHasErrors('life_event_type_id')
            ->assertSessionHasInput('contact_id', $michael->id);
    }

    #[Test]
    public function it_shows_the_details_of_a_life_event(): void
    {
        [$user, $vault, $michael] = $this->michael();
        $lifeEvent = $this->lifeEventOf($michael, ['title' => 'Dundies', 'narrative' => 'Pam won Whitest Sneakers.']);

        $this->actingAs($user)
            ->get(route('vaults.lifeEvents.show', ['vault' => $vault, 'lifeEvent' => $lifeEvent]))
            ->assertOk()
            ->assertSee('id="life-event-details-'.$lifeEvent->uuid.'"', escape: false)
            ->assertSee('Pam won Whitest Sneakers.')
            ->assertSee('Who was there');
    }

    #[Test]
    public function it_does_not_find_a_deleted_life_event(): void
    {
        [$user, $vault, $michael] = $this->michael();
        $lifeEvent = $this->lifeEventOf($michael);
        $lifeEvent->delete();

        $this->actingAs($user)
            ->get(route('vaults.lifeEvents.show', ['vault' => $vault, 'lifeEvent' => $lifeEvent]))
            ->assertNotFound();
    }

    #[Test]
    public function it_does_not_find_a_life_event_through_another_vault(): void
    {
        [$user, $vault, $michael] = $this->michael();
        $stamford = Vault::factory()->create(['account_id' => $user->account_id]);
        $lifeEvent = $this->lifeEventOf($michael);

        $this->actingAs($user)
            ->get(route('vaults.lifeEvents.show', ['vault' => $stamford, 'lifeEvent' => $lifeEvent]))
            ->assertNotFound();
    }

    /** @return array{0: User, 1: Vault, 2: Contact} */
    private function michael(): array
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $michael = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id, 'first_name' => 'Michael', 'is_me' => true]);

        return [$user, $vault, $michael];
    }

    private function contactOn(Vault $vault, string $firstName): Contact
    {
        return Contact::factory()->create([
            'account_id' => $vault->account_id,
            'vault_id' => $vault->id,
            'first_name' => $firstName,
        ]);
    }

    /** @param array<string, mixed> $attributes */
    private function lifeEventOf(Contact $contact, array $attributes = []): LifeEvent
    {
        $lifeEvent = LifeEvent::factory()->create(['vault_id' => $contact->vault_id, ...$attributes]);

        LifeEventParticipant::factory()->create(['life_event_id' => $lifeEvent->id, 'participant_id' => $contact->id]);

        return $lifeEvent;
    }
}
