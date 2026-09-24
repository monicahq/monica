<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateLifeEvent;
use App\Actions\DestroyLifeEvent;
use App\Actions\RestoreLifeEvent;
use App\Enums\FieldTypeEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Contact;
use App\Models\User;
use App\Models\Vault;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RestoreLifeEventTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_brings_the_life_event_back_with_everything_it_held(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $jim = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id]);
        $pam = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id]);
        $call = $this->lifeEventTypeAsking($user, 'Call', ['Channel' => FieldTypeEnum::Text]);
        $channel = $call->structure->publishedVersion->inputFields()->firstOrFail();

        $lifeEvent = new CreateLifeEvent(
            user: $user,
            vault: $vault,
            contact: $jim,
            lifeEventType: $call,
            happenedOn: CarbonImmutable::parse('2026-08-30'),
            participants: [$pam],
            values: [$channel->id => 'Mobile'],
        )->execute();

        new DestroyLifeEvent(user: $user, vault: $vault, contact: $jim, lifeEvent: $lifeEvent)->execute();

        new RestoreLifeEvent(user: $user, vault: $vault, contact: $jim, lifeEvent: $lifeEvent->refresh())->execute();

        $this->assertNotSoftDeleted($lifeEvent);
        $this->assertDatabaseHas('field_values', ['valuable_id' => $lifeEvent->id, 'value_string' => 'Mobile']);
        $this->assertEquals(1, $call->structure->publishedVersion->refresh()->record_count);

        foreach ([$jim, $pam] as $person) {
            $this->assertEquals(1, $person->refresh()->life_event_count);
            $this->assertEquals('2026-08-30', $person->last_life_event_at?->toDateString());
        }

        Queue::assertPushedOn('low', LogUserAction::class, fn (LogUserAction $job): bool => $job->action === UserActionEnum::LifeEventRestoration);
    }

    #[Test]
    public function it_refuses_a_life_event_that_was_not_deleted(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $jim = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id]);

        $lifeEvent = new CreateLifeEvent(
            user: $user,
            vault: $vault,
            contact: $jim,
            lifeEventType: $this->lifeEventTypeAsking($user, 'Call'),
            happenedOn: CarbonImmutable::parse('2026-08-30'),
        )->execute();

        $this->expectException(ModelNotFoundException::class);

        new RestoreLifeEvent(user: $user, vault: $vault, contact: $jim, lifeEvent: $lifeEvent)->execute();
    }
}
