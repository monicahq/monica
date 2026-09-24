<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateLifeEvent;
use App\Enums\FieldTypeEnum;
use App\Enums\UserActionEnum;
use App\Exceptions\LifeEventTypeIsNotAvailable;
use App\Jobs\LogUserAction;
use App\Models\Contact;
use App\Models\LifeEvent;
use App\Models\StructureVersionSection;
use App\Models\User;
use App\Models\Vault;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateLifeEventTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_records_a_life_event_with_everybody_who_was_there(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $jim = $this->contactOn($vault, 'Jim');
        $pam = $this->contactOn($vault, 'Pam');
        $lifeEventType = $this->lifeEventTypeAsking($user, 'Move');

        $lifeEvent = new CreateLifeEvent(
            user: $user,
            vault: $vault,
            contact: $jim,
            lifeEventType: $lifeEventType,
            happenedOn: CarbonImmutable::parse('2026-07-03'),
            title: 'Moved to a house near the office',
            narrative: 'The one Jim bought for his parents.',
            location: 'Scranton',
            durationInMinutes: 240,
            participants: [$pam],
        )->execute();

        $this->assertInstanceOf(LifeEvent::class, $lifeEvent);
        $this->assertDatabaseHas('life_events', [
            'id' => $lifeEvent->id,
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'life_event_type_id' => $lifeEventType->id,
            'structure_version_id' => $lifeEventType->structure->published_version_id,
            'title' => 'Moved to a house near the office',
            'narrative' => 'The one Jim bought for his parents.',
            'location' => 'Scranton',
            'duration_in_minutes' => 240,
            'date_precision' => 'day',
            'created_from' => 'contact',
            'created_by_user_id' => $user->id,
            'participant_count' => 2,
        ]);
        $this->assertDatabaseHas('life_event_participants', ['life_event_id' => $lifeEvent->id, 'participant_id' => $jim->id, 'position' => 0]);
        $this->assertDatabaseHas('life_event_participants', ['life_event_id' => $lifeEvent->id, 'participant_id' => $pam->id, 'position' => 1]);
        $this->assertEquals(1, $lifeEventType->structure->publishedVersion->refresh()->record_count);

        Queue::assertPushedOn('low', LogUserAction::class, fn (LogUserAction $job): bool => $job->action === UserActionEnum::LifeEventCreation);
    }

    #[Test]
    public function it_saves_the_custom_fields_of_its_type(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $dwight = $this->contactOn($vault, 'Dwight');
        $lifeEventType = $this->lifeEventTypeAsking($user, 'Harvest', ['Crop' => FieldTypeEnum::Text]);
        $crop = StructureVersionSection::query()
            ->where('structure_version_id', $lifeEventType->structure->published_version_id)
            ->firstOrFail()
            ->sectionVersion->fields->firstOrFail();

        $lifeEvent = new CreateLifeEvent(
            user: $user,
            vault: $vault,
            contact: $dwight,
            lifeEventType: $lifeEventType,
            happenedOn: CarbonImmutable::parse('2026-09-01'),
            values: [$crop->id => 'Beets'],
        )->execute();

        $this->assertDatabaseHas('field_values', [
            'valuable_type' => LifeEvent::class,
            'valuable_id' => $lifeEvent->id,
            'field_id' => $crop->id,
            'value_string' => 'Beets',
        ]);
    }

    #[Test]
    public function it_counts_the_life_event_for_every_participant(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $jim = $this->contactOn($vault, 'Jim');
        $pam = $this->contactOn($vault, 'Pam');

        new CreateLifeEvent(
            user: $user,
            vault: $vault,
            contact: $jim,
            lifeEventType: $this->lifeEventTypeAsking($user, 'Wedding'),
            happenedOn: CarbonImmutable::parse('2026-10-10'),
            participants: [$pam],
        )->execute();

        foreach ([$jim, $pam] as $person) {
            $person->refresh();

            $this->assertEquals(1, $person->life_event_count);
            $this->assertEquals('2026-10-10', $person->last_life_event_at?->toDateString());
            $this->assertNull($person->last_interaction_at);
        }
    }

    #[Test]
    public function it_leaves_a_later_last_life_event_alone(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $jim = $this->contactOn($vault, 'Jim');
        $jim->forceFill(['last_life_event_at' => '2026-09-01 00:00:00'])->saveQuietly();

        new CreateLifeEvent(
            user: $user,
            vault: $vault,
            contact: $jim,
            lifeEventType: $this->lifeEventTypeAsking($user, 'Prank'),
            happenedOn: CarbonImmutable::parse('2026-02-14'),
        )->execute();

        $this->assertEquals('2026-09-01', $jim->refresh()->last_life_event_at?->toDateString());
        $this->assertEquals(1, $jim->life_event_count);
    }

    #[Test]
    public function it_lists_a_participant_once(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $jim = $this->contactOn($vault, 'Jim');

        $lifeEvent = new CreateLifeEvent(
            user: $user,
            vault: $vault,
            contact: $jim,
            lifeEventType: $this->lifeEventTypeAsking($user, 'Call'),
            happenedOn: CarbonImmutable::parse('2026-03-01'),
            participants: [$jim],
        )->execute();

        $this->assertEquals(1, $lifeEvent->participant_count);
        $this->assertEquals(1, $jim->refresh()->life_event_count);
    }

    #[Test]
    public function it_refuses_a_participant_of_another_vault(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $stamford = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->expectException(ModelNotFoundException::class);

        new CreateLifeEvent(
            user: $user,
            vault: $vault,
            contact: $this->contactOn($vault, 'Jim'),
            lifeEventType: $this->lifeEventTypeAsking($user, 'Meeting'),
            happenedOn: CarbonImmutable::parse('2026-03-01'),
            participants: [$this->contactOn($stamford, 'Karen')],
        )->execute();
    }

    #[Test]
    public function it_refuses_a_type_of_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->expectException(ModelNotFoundException::class);

        new CreateLifeEvent(
            user: $user,
            vault: $vault,
            contact: $this->contactOn($vault, 'Jim'),
            lifeEventType: $this->lifeEventTypeAsking(User::factory()->create(), 'Merger'),
            happenedOn: CarbonImmutable::parse('2026-03-01'),
        )->execute();
    }

    #[Test]
    public function it_refuses_a_type_that_can_no_longer_be_chosen(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $lifeEventType = $this->lifeEventTypeAsking($user, 'Fun run');
        $lifeEventType->update(['is_active' => false]);

        $this->expectException(LifeEventTypeIsNotAvailable::class);

        new CreateLifeEvent(
            user: $user,
            vault: $vault,
            contact: $this->contactOn($vault, 'Michael'),
            lifeEventType: $lifeEventType,
            happenedOn: CarbonImmutable::parse('2026-03-01'),
        )->execute();
    }

    private function contactOn(Vault $vault, string $firstName): Contact
    {
        return Contact::factory()->create([
            'account_id' => $vault->account_id,
            'vault_id' => $vault->id,
            'first_name' => $firstName,
            'life_event_count' => 0,
            'last_life_event_at' => null,
            'last_interaction_at' => null,
        ]);
    }
}
