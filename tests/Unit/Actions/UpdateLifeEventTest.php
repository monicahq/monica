<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateLifeEvent;
use App\Actions\ReplaceLifeEventTypeFields;
use App\Actions\UpdateLifeEvent;
use App\Enums\FieldTypeEnum;
use App\Enums\UserActionEnum;
use App\Exceptions\LifeEventTypeIsNotAvailable;
use App\Jobs\LogUserAction;
use App\Models\Contact;
use App\Models\LifeEvent;
use App\Models\LifeEventType;
use App\Models\User;
use App\Models\Vault;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateLifeEventTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_corrects_what_happened_and_when(): void
    {
        Queue::fake();

        [$user, $vault, $jim] = $this->office();
        $call = $this->lifeEventTypeAsking($user, 'Call', ['Channel' => FieldTypeEnum::Text]);
        $channel = $call->structure->publishedVersion->inputFields()->firstOrFail();
        $lifeEvent = $this->record($user, $vault, $jim, $call, values: [$channel->id => 'Mobile']);

        $this->update($user, $vault, $jim, $lifeEvent, $call, '2026-08-30', title: 'Call about the move', values: [$channel->id => 'Landline']);

        $this->assertEquals('2026-08-30', $lifeEvent->refresh()->happened_on->toDateString());
        $this->assertEquals('Call about the move', $lifeEvent->title);
        $this->assertDatabaseHas('field_values', ['valuable_id' => $lifeEvent->id, 'value_string' => 'Landline']);
        $this->assertEquals('2026-08-30', $jim->refresh()->last_life_event_at?->toDateString());

        Queue::assertPushedOn('low', LogUserAction::class, fn (LogUserAction $job): bool => $job->action === UserActionEnum::LifeEventUpdate);
    }

    #[Test]
    public function it_recounts_everybody_added_or_taken_off(): void
    {
        Queue::fake();

        [$user, $vault, $jim, $pam, $dwight] = $this->office();
        $call = $this->lifeEventTypeAsking($user, 'Call');
        $lifeEvent = $this->record($user, $vault, $jim, $call, participants: [$pam]);

        $this->update($user, $vault, $jim, $lifeEvent, $call, '2026-07-03', participants: [$dwight]);

        $this->assertEquals(0, $pam->refresh()->life_event_count);
        $this->assertNull($pam->last_life_event_at);
        $this->assertEquals(1, $dwight->refresh()->life_event_count);
        $this->assertEquals(1, $jim->refresh()->life_event_count);
        $this->assertEquals(2, $lifeEvent->refresh()->participant_count);
        $this->assertDatabaseHas('life_event_participants', ['life_event_id' => $lifeEvent->id, 'participant_id' => $jim->id, 'position' => 0]);
    }

    #[Test]
    public function it_keeps_a_participant_whose_contact_is_in_the_trash(): void
    {
        Queue::fake();

        [$user, $vault, $jim, $pam] = $this->office();
        $call = $this->lifeEventTypeAsking($user, 'Call');
        $lifeEvent = $this->record($user, $vault, $jim, $call, participants: [$pam]);

        $pam->delete();

        $this->update($user, $vault, $jim, $lifeEvent, $call, '2026-07-03');

        $this->assertDatabaseHas('life_event_participants', ['life_event_id' => $lifeEvent->id, 'participant_id' => $pam->id]);
        $this->assertEquals(2, $lifeEvent->refresh()->participant_count);
    }

    #[Test]
    public function it_keeps_the_fields_it_was_written_with_when_the_type_stays(): void
    {
        Queue::fake();

        [$user, $vault, $jim] = $this->office();
        $call = $this->lifeEventTypeAsking($user, 'Call', ['Channel' => FieldTypeEnum::Text]);
        $lifeEvent = $this->record($user, $vault, $jim, $call);
        $recordedOn = $lifeEvent->structure_version_id;
        $this->republish($user, $call, ['Channel', 'Mood']);

        $this->update($user, $vault, $jim, $lifeEvent, $call->refresh(), '2026-07-03');

        $this->assertEquals($recordedOn, $lifeEvent->refresh()->structure_version_id);
    }

    #[Test]
    public function it_moves_to_the_fields_of_another_type_and_lets_the_old_values_go(): void
    {
        Queue::fake();

        [$user, $vault, $jim] = $this->office();
        $call = $this->lifeEventTypeAsking($user, 'Call', ['Channel' => FieldTypeEnum::Text]);
        $meal = $this->lifeEventTypeAsking($user, 'Meal', ['Dish' => FieldTypeEnum::Text]);
        $channel = $call->structure->publishedVersion->inputFields()->firstOrFail();
        $lifeEvent = $this->record($user, $vault, $jim, $call, values: [$channel->id => 'Mobile']);

        $this->update($user, $vault, $jim, $lifeEvent, $meal, '2026-07-03');

        $this->assertEquals($meal->id, $lifeEvent->refresh()->life_event_type_id);
        $this->assertEquals($meal->structure->published_version_id, $lifeEvent->structure_version_id);
        $this->assertDatabaseMissing('field_values', ['valuable_id' => $lifeEvent->id, 'field_id' => $channel->id]);
        $this->assertEquals(0, $call->structure->publishedVersion->refresh()->record_count);
        $this->assertEquals(1, $meal->structure->publishedVersion->refresh()->record_count);
    }

    #[Test]
    public function it_lets_a_file_go_when_its_field_is_removed(): void
    {
        Queue::fake();
        Storage::fake('local');

        [$user, $vault, $jim] = $this->office();
        $party = $this->lifeEventTypeAsking($user, 'Party', ['Invitation' => FieldTypeEnum::File]);
        $invitation = $party->structure->publishedVersion->inputFields()->firstOrFail();
        $lifeEvent = $this->record($user, $vault, $jim, $party, values: [$invitation->id => UploadedFile::fake()->create('invitation.pdf', 4, 'application/pdf')]);

        $this->update($user, $vault, $jim, $lifeEvent, $party, '2026-07-03', removedFieldIds: [$invitation->id]);

        $this->assertDatabaseMissing('field_values', ['valuable_id' => $lifeEvent->id, 'field_id' => $invitation->id]);
    }

    #[Test]
    public function it_keeps_a_type_that_was_switched_off_since(): void
    {
        Queue::fake();

        [$user, $vault, $jim] = $this->office();
        $fax = $this->lifeEventTypeAsking($user, 'Fax');
        $lifeEvent = $this->record($user, $vault, $jim, $fax);
        $fax->update(['is_active' => false]);

        $this->update($user, $vault, $jim, $lifeEvent, $fax, '2026-07-03');

        $this->assertEquals('2026-07-03', $lifeEvent->refresh()->happened_on->toDateString());
    }

    #[Test]
    public function it_refuses_a_new_type_that_was_switched_off(): void
    {
        Queue::fake();

        [$user, $vault, $jim] = $this->office();
        $lifeEvent = $this->record($user, $vault, $jim, $this->lifeEventTypeAsking($user, 'Call'));
        $fax = $this->lifeEventTypeAsking($user, 'Fax');
        $fax->update(['is_active' => false]);

        $this->expectException(LifeEventTypeIsNotAvailable::class);

        $this->update($user, $vault, $jim, $lifeEvent, $fax, '2026-07-03');
    }

    /** @return array{0: User, 1: Vault, 2: Contact, 3: Contact, 4: Contact} */
    private function office(): array
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $person = fn (string $name): Contact => Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id, 'first_name' => $name]);

        return [$user, $vault, $person('Jim'), $person('Pam'), $person('Dwight')];
    }

    /** @param  list<string>  $labels */
    private function republish(User $user, LifeEventType $type, array $labels): void
    {
        new ReplaceLifeEventTypeFields(
            user: $user,
            lifeEventType: $type,
            groups: [],
            fields: array_map(fn (string $label): array => [
                'key' => null,
                'group' => '',
                'label' => $label,
                'type' => FieldTypeEnum::Text->value,
                'is_required' => false,
                'help_text' => null,
                'settings' => [],
                'choices' => [],
            ], $labels),
        )->execute();
    }

    /**
     * @param  list<Contact>  $participants
     * @param  array<int, mixed>  $values
     */
    private function record(User $user, Vault $vault, Contact $contact, LifeEventType $type, array $participants = [], array $values = []): LifeEvent
    {
        return new CreateLifeEvent(
            user: $user,
            vault: $vault,
            contact: $contact,
            lifeEventType: $type,
            happenedOn: CarbonImmutable::parse('2026-06-01'),
            participants: $participants,
            values: $values,
        )->execute();
    }

    /**
     * @param  list<Contact>  $participants
     * @param  array<int, mixed>  $values
     * @param  array<int, int>  $removedFieldIds
     */
    private function update(User $user, Vault $vault, Contact $contact, LifeEvent $lifeEvent, LifeEventType $type, string $on, ?string $title = null, array $participants = [], array $values = [], array $removedFieldIds = []): LifeEvent
    {
        return new UpdateLifeEvent(
            user: $user,
            vault: $vault,
            contact: $contact,
            lifeEvent: $lifeEvent,
            lifeEventType: $type,
            happenedOn: CarbonImmutable::parse($on),
            title: $title,
            participants: $participants,
            values: $values,
            removedFieldIds: $removedFieldIds,
        )->execute();
    }
}
