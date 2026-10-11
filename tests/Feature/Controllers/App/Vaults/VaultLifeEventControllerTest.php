<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

use App\Models\Contact;
use App\Models\LifeEvent;
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
            ->assertSee('Step 1 of 2 · Choose what happened')
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
            ->assertRedirect(route('vaults.show', $vault))
            ->assertSessionHas('status', 'Promotion, 11/10/2026, is on the timeline of Michael.')
            ->assertSessionHas('lifeEventJustAdded', LifeEvent::query()->sole()->id);

        $this->assertDatabaseHas('life_events', ['life_event_type_id' => $promotion->id, 'created_from' => 'dashboard']);
        $this->assertDatabaseHas('life_event_participants', ['participant_id' => $michael->id, 'position' => 0]);
    }

    #[Test]
    public function it_puts_the_life_event_on_the_timeline_of_everybody_who_was_there(): void
    {
        Queue::fake();

        [$user, $vault, $michael] = $this->michael();
        $dwight = $this->contactOn($vault, 'Dwight');

        $this->actingAs($user)
            ->post(route('vaults.lifeEvents.create', $vault), [
                'contact_id' => $michael->id,
                'life_event_type_id' => $this->lifeEventTypeAsking($user, 'Sales call')->id,
                'happened_on' => '2026-10-11',
                'participants' => [$dwight->id],
            ])
            ->assertSessionHas('status', 'Sales call, 11/10/2026, is on the timeline of Michael and Dwight.');

        $this->assertDatabaseHas('life_event_participants', ['participant_id' => $dwight->id, 'position' => 1]);
    }

    #[Test]
    public function it_records_a_life_event_for_somebody_else_without_the_signed_in_person(): void
    {
        Queue::fake();

        [$user, $vault, $michael] = $this->michael();
        $pam = $this->contactOn($vault, 'Pam');

        $this->actingAs($user)
            ->post(route('vaults.lifeEvents.create', $vault), [
                'contact_id' => $pam->id,
                'life_event_type_id' => $this->lifeEventTypeAsking($user, 'Wedding')->id,
                'happened_on' => '2026-10-11',
            ])
            ->assertRedirect(route('vaults.show', $vault))
            ->assertSessionHas('status', 'Wedding, 11/10/2026, is on the timeline of Pam.');

        $this->assertDatabaseHas('life_event_participants', ['participant_id' => $pam->id, 'position' => 0]);
        $this->assertDatabaseMissing('life_event_participants', ['participant_id' => $michael->id]);
    }

    #[Test]
    public function it_brings_back_the_person_chosen_when_the_form_is_refused(): void
    {
        [$user, $vault] = $this->michael();
        $pam = $this->contactOn($vault, 'Pam');

        $this->actingAs($user)
            ->from(route('vaults.show', $vault))
            ->post(route('vaults.lifeEvents.create', $vault), [
                'contact_id' => $pam->id,
                'life_event_type_id' => $this->lifeEventTypeAsking($user, 'Wedding')->id,
            ])
            ->assertRedirect(route('vaults.show', $vault))
            ->assertSessionHasErrors('happened_on')
            ->assertSessionHasInput('contact_id', $pam->id);
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
}
