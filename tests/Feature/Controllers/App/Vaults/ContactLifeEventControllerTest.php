<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\App\Vaults;

use App\Actions\CreateLifeEvent;
use App\Enums\FieldTypeEnum;
use App\Models\Contact;
use App\Models\LifeEvent;
use App\Models\User;
use App\Models\Vault;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactLifeEventControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_records_a_life_event_and_says_whose_timeline_it_is_on(): void
    {
        Queue::fake();

        [$user, $vault, $jim] = $this->jim();
        $pam = $this->contactOn($vault, 'Pam');
        $move = $this->lifeEventTypeAsking($user, 'Move');

        $this->actingAs($user)
            ->post($this->url($vault, $jim), [
                'life_event_type_id' => $move->id,
                'happened_on' => '2026-07-03',
                'participants' => [$pam->id],
                'title' => 'Moved to a house near the office',
            ])
            ->assertRedirect(route('contacts.show', ['vault' => $vault, 'contact' => $jim]))
            ->assertSessionHas('status', 'Move, 03/07/2026, is on the timeline of Jim and Pam.')
            ->assertSessionHas('lifeEventJustAdded', LifeEvent::query()->sole()->id);

        $this->assertDatabaseHas('life_events', ['title' => 'Moved to a house near the office', 'participant_count' => 2]);
    }

    #[Test]
    public function it_refuses_a_life_event_with_no_date_or_a_duration_of_nothing(): void
    {
        [$user, $vault, $jim] = $this->jim();
        $move = $this->lifeEventTypeAsking($user, 'Move');

        $this->actingAs($user)
            ->post($this->url($vault, $jim), [
                'life_event_type_id' => $move->id,
                'duration_in_minutes' => 0,
            ])
            ->assertSessionHasErrors(['happened_on', 'duration_in_minutes']);

        $this->assertSame(0, LifeEvent::query()->count());
    }

    #[Test]
    public function it_refuses_a_value_its_custom_field_refuses(): void
    {
        Queue::fake();

        [$user, $vault, $jim] = $this->jim();
        $sale = $this->lifeEventTypeAsking($user, 'Sale', ['Reams' => FieldTypeEnum::Number]);
        $reams = $sale->structure->publishedVersion->inputFields()->firstOrFail();

        $this->actingAs($user)
            ->post($this->url($vault, $jim), [
                'life_event_type_id' => $sale->id,
                'happened_on' => '2026-07-03',
                'fields' => [$reams->id => 'a lot'],
            ])
            ->assertSessionHasErrors("fields.{$reams->id}");
    }

    #[Test]
    public function it_ignores_a_value_for_a_field_the_type_does_not_ask_for(): void
    {
        Queue::fake();

        [$user, $vault, $jim] = $this->jim();
        $call = $this->lifeEventTypeAsking($user, 'Call');
        $sale = $this->lifeEventTypeAsking($user, 'Sale', ['Client' => FieldTypeEnum::Text]);
        $client = $sale->structure->publishedVersion->inputFields()->firstOrFail();

        $this->actingAs($user)
            ->post($this->url($vault, $jim), [
                'life_event_type_id' => $call->id,
                'happened_on' => '2026-07-03',
                'fields' => [$client->id => 'Blue Cross'],
            ])
            ->assertRedirect();

        $this->assertDatabaseMissing('field_values', ['field_id' => $client->id]);
    }

    #[Test]
    public function it_says_so_when_the_type_can_no_longer_be_chosen(): void
    {
        [$user, $vault, $jim] = $this->jim();
        $fax = $this->lifeEventTypeAsking($user, 'Fax');
        $fax->update(['is_active' => false]);

        $this->actingAs($user)
            ->post($this->url($vault, $jim), [
                'life_event_type_id' => $fax->id,
                'happened_on' => '2026-07-03',
            ])
            ->assertSessionHasErrors(['life_event_type_id' => 'That life event type can no longer be chosen.']);
    }

    #[Test]
    public function it_does_not_find_a_participant_kept_in_another_vault(): void
    {
        [$user, $vault, $jim] = $this->jim();
        $karen = $this->contactOn(Vault::factory()->create(['account_id' => $user->account_id]), 'Karen');

        $this->actingAs($user)
            ->post($this->url($vault, $jim), [
                'life_event_type_id' => $this->lifeEventTypeAsking($user, 'Meeting')->id,
                'happened_on' => '2026-07-03',
                'participants' => [$karen->id],
            ])
            ->assertNotFound();
    }

    #[Test]
    public function it_does_not_find_a_contact_through_another_vault(): void
    {
        [$user, $vault, $jim] = $this->jim();
        $elsewhere = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)
            ->post($this->url($elsewhere, $jim), [
                'life_event_type_id' => $this->lifeEventTypeAsking($user, 'Meeting')->id,
                'happened_on' => '2026-07-03',
            ])
            ->assertNotFound();
    }

    #[Test]
    public function it_opens_the_panel_to_record_a_life_event(): void
    {
        [$user, $vault, $jim] = $this->jim();

        $this->actingAs($user)
            ->get(route('contacts.lifeEvents.new', ['vault' => $vault, 'contact' => $jim]))
            ->assertOk()
            ->assertSee('Step 1 of 2 · Choose what happened')
            ->assertSee('aria-controls="life-event-panel"', escape: false);
    }

    #[Test]
    public function it_does_not_open_the_panel_for_a_contact_through_another_vault(): void
    {
        [$user, , $jim] = $this->jim();
        $elsewhere = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->actingAs($user)
            ->get(route('contacts.lifeEvents.new', ['vault' => $elsewhere, 'contact' => $jim]))
            ->assertNotFound();
    }

    #[Test]
    public function it_opens_the_panel_filled_in_to_correct_a_life_event(): void
    {
        Queue::fake();

        [$user, $vault, $jim] = $this->jim();
        $lifeEvent = $this->recorded($user, $vault, $jim, 'Call about the move');

        $this->actingAs($user)
            ->get(route('contacts.lifeEvents.edit', ['vault' => $vault, 'contact' => $jim, 'lifeEvent' => $lifeEvent]))
            ->assertOk()
            ->assertSee('Edit life event')
            ->assertSee('Save changes')
            ->assertSee('value="Call about the move"', escape: false);
    }

    #[Test]
    public function it_saves_the_corrections_and_says_whose_timeline_it_is_on(): void
    {
        Queue::fake();

        [$user, $vault, $jim] = $this->jim();
        $lifeEvent = $this->recorded($user, $vault, $jim, 'Call');

        $this->actingAs($user)
            ->put(route('contacts.lifeEvents.update', ['vault' => $vault, 'contact' => $jim, 'lifeEvent' => $lifeEvent]), [
                'life_event_type_id' => $lifeEvent->life_event_type_id,
                'happened_on' => '2026-08-30',
                'title' => 'Call about the move',
            ])
            ->assertRedirect(route('contacts.show', ['vault' => $vault, 'contact' => $jim]))
            ->assertSessionHas('status', 'Changes saved. Call, 30/08/2026, is on the timeline of Jim.')
            ->assertSessionHas('lifeEventEdited', $lifeEvent->id);

        $this->assertEquals('Call about the move', $lifeEvent->refresh()->title);
    }

    #[Test]
    public function it_refuses_a_correction_without_a_date_and_keeps_the_panel_editing(): void
    {
        Queue::fake();

        [$user, $vault, $jim] = $this->jim();
        $lifeEvent = $this->recorded($user, $vault, $jim, 'Call');

        $this->actingAs($user)
            ->from(route('contacts.show', ['vault' => $vault, 'contact' => $jim]))
            ->put(route('contacts.lifeEvents.update', ['vault' => $vault, 'contact' => $jim, 'lifeEvent' => $lifeEvent]), [
                'life_event_type_id' => $lifeEvent->life_event_type_id,
            ])
            ->assertRedirect(route('contacts.lifeEvents.edit', ['vault' => $vault, 'contact' => $jim, 'lifeEvent' => $lifeEvent]))
            ->assertSessionHasErrors('happened_on');
    }

    #[Test]
    public function it_does_not_find_a_life_event_the_contact_did_not_take_part_in(): void
    {
        Queue::fake();

        [$user, $vault, $jim] = $this->jim();
        $dwight = $this->contactOn($vault, 'Dwight');
        $lifeEvent = $this->recorded($user, $vault, $jim, 'Call');

        $this->actingAs($user)
            ->get(route('contacts.lifeEvents.edit', ['vault' => $vault, 'contact' => $dwight, 'lifeEvent' => $lifeEvent]))
            ->assertNotFound();

        $this->actingAs($user)
            ->delete(route('contacts.lifeEvents.destroy', ['vault' => $vault, 'contact' => $dwight, 'lifeEvent' => $lifeEvent]))
            ->assertNotFound();
    }

    #[Test]
    public function it_deletes_a_life_event_and_offers_to_take_it_back(): void
    {
        Queue::fake();

        [$user, $vault, $jim] = $this->jim();
        $lifeEvent = $this->recorded($user, $vault, $jim, 'Call');

        $this->actingAs($user)
            ->delete(route('contacts.lifeEvents.destroy', ['vault' => $vault, 'contact' => $jim, 'lifeEvent' => $lifeEvent]))
            ->assertRedirect(route('contacts.show', ['vault' => $vault, 'contact' => $jim]))
            ->assertSessionHas('status', 'Call, 01/06/2026, is deleted from the timeline of Jim.')
            ->assertSessionHas('lifeEventDeleted', $lifeEvent->uuid)
            ->assertSessionHas('lifeEventDeletedLine', 'Call, 01/06/2026, is deleted from the timeline of Jim.');

        $this->assertSoftDeleted($lifeEvent);

        $this->actingAs($user)
            ->withSession(['lifeEventDeleted' => $lifeEvent->uuid, 'lifeEventDeletedLine' => 'Call, 01/06/2026, is deleted from the timeline of Jim.'])
            ->get(route('contacts.show', ['vault' => $vault, 'contact' => $jim]))
            ->assertSee('Call, 01/06/2026, is deleted from the timeline of Jim.')
            ->assertSee(route('contacts.lifeEventRestoration.update', ['vault' => $vault, 'contact' => $jim, 'lifeEventUuid' => $lifeEvent->uuid]), escape: false)
            ->assertSee('Undo');
    }

    private function recorded(User $user, Vault $vault, Contact $contact, string $typeName): LifeEvent
    {
        return new CreateLifeEvent(
            user: $user,
            vault: $vault,
            contact: $contact,
            lifeEventType: $this->lifeEventTypeAsking($user, $typeName),
            happenedOn: CarbonImmutable::parse('2026-06-01'),
            title: $typeName === 'Call' ? null : $typeName,
        )->execute();
    }

    /** @return array{0: User, 1: Vault, 2: Contact} */
    private function jim(): array
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        return [$user, $vault, $this->contactOn($vault, 'Jim')];
    }

    private function contactOn(Vault $vault, string $firstName): Contact
    {
        return Contact::factory()->create([
            'account_id' => $vault->account_id,
            'vault_id' => $vault->id,
            'first_name' => $firstName,
        ]);
    }

    private function url(Vault $vault, Contact $contact): string
    {
        return route('contacts.lifeEvents.create', ['vault' => $vault, 'contact' => $contact]);
    }
}
