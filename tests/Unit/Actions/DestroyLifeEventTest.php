<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateLifeEvent;
use App\Actions\DestroyLifeEvent;
use App\Enums\FieldTypeEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Contact;
use App\Models\FieldValue;
use App\Models\LifeEvent;
use App\Models\LifeEventType;
use App\Models\User;
use App\Models\Vault;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DestroyLifeEventTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_takes_the_life_event_off_every_timeline_and_keeps_it_whole(): void
    {
        Queue::fake();
        Storage::fake('local');

        [$user, $vault, $jim, $pam] = $this->jimAndPam();
        $party = $this->lifeEventTypeAsking($user, 'Party', ['Invitation' => FieldTypeEnum::File]);
        $invitation = $party->structure->publishedVersion->inputFields()->firstOrFail();
        $lifeEvent = $this->record($user, $vault, $jim, $party, '2026-05-01', [$pam], [$invitation->id => UploadedFile::fake()->create('invitation.pdf', 4, 'application/pdf')]);

        new DestroyLifeEvent(user: $user, vault: $vault, contact: $jim, lifeEvent: $lifeEvent)->execute();

        $this->assertSoftDeleted($lifeEvent);
        $this->assertDatabaseHas('life_event_participants', ['life_event_id' => $lifeEvent->id, 'participant_id' => $pam->id]);
        $file = FieldValue::query()->where('valuable_id', $lifeEvent->id)->sole()->file;
        $this->assertEquals(1, $file->reference_count);
        $this->assertNull($file->orphaned_at);
        $this->assertEquals(0, $party->structure->publishedVersion->refresh()->record_count);

        foreach ([$jim, $pam] as $person) {
            $this->assertEquals(0, $person->refresh()->life_event_count);
            $this->assertNull($person->last_life_event_at);
            $this->assertNull($person->last_interaction_at);
        }

        Queue::assertPushedOn('low', LogUserAction::class, fn (LogUserAction $job): bool => $job->action === UserActionEnum::LifeEventDeletion);
    }

    #[Test]
    public function it_falls_back_to_the_life_event_before_the_one_deleted(): void
    {
        Queue::fake();

        [$user, $vault, $jim] = $this->jimAndPam();
        $prank = $this->lifeEventTypeAsking($user, 'Prank');
        $this->record($user, $vault, $jim, $prank, '2026-02-14');
        $latest = $this->record($user, $vault, $jim, $prank, '2026-06-01');

        new DestroyLifeEvent(user: $user, vault: $vault, contact: $jim, lifeEvent: $latest)->execute();

        $this->assertEquals(1, $jim->refresh()->life_event_count);
        $this->assertEquals('2026-02-14', $jim->last_life_event_at?->toDateString());
        $this->assertEquals('2026-02-14', $jim->last_interaction_at?->toDateString());
    }

    #[Test]
    public function it_refuses_a_contact_who_did_not_take_part(): void
    {
        Queue::fake();

        [$user, $vault, $jim, $pam] = $this->jimAndPam();
        $lifeEvent = $this->record($user, $vault, $jim, $this->lifeEventTypeAsking($user, 'Prank'), '2026-02-14');

        $this->expectException(ModelNotFoundException::class);

        new DestroyLifeEvent(user: $user, vault: $vault, contact: $pam, lifeEvent: $lifeEvent)->execute();
    }

    #[Test]
    public function it_refuses_a_life_event_of_another_vault(): void
    {
        Queue::fake();

        [$user, $vault, $jim] = $this->jimAndPam();
        $lifeEvent = $this->record($user, $vault, $jim, $this->lifeEventTypeAsking($user, 'Prank'), '2026-02-14');

        $this->expectException(ModelNotFoundException::class);

        new DestroyLifeEvent(
            user: $user,
            vault: Vault::factory()->create(['account_id' => $user->account_id]),
            contact: $jim,
            lifeEvent: $lifeEvent,
        )->execute();
    }

    /** @return array{0: User, 1: Vault, 2: Contact, 3: Contact} */
    private function jimAndPam(): array
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        return [
            $user,
            $vault,
            Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id, 'first_name' => 'Jim']),
            Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id, 'first_name' => 'Pam']),
        ];
    }

    /**
     * @param  list<Contact>  $participants
     * @param  array<int, mixed>  $values
     */
    private function record(User $user, Vault $vault, Contact $contact, LifeEventType $type, string $on, array $participants = [], array $values = []): LifeEvent
    {
        return new CreateLifeEvent(
            user: $user,
            vault: $vault,
            contact: $contact,
            lifeEventType: $type,
            happenedOn: CarbonImmutable::parse($on),
            participants: $participants,
            values: $values,
        )->execute();
    }
}
