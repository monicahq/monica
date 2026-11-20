<?php

declare(strict_types=1);

namespace Tests\Unit\Migrations;

use App\Jobs\RecountLastInTouchOfType;
use App\Models\Contact;
use App\Models\LifeEvent;
use App\Models\LifeEventParticipant;
use App\Models\LifeEventType;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BackfillLastInteractionAtTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_stops_news_about_one_person_from_updating_last_in_touch(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $meal = LifeEventType::factory()->create(['account_id' => $user->account_id, 'is_system' => true, 'key' => 'meal']);
        $move = LifeEventType::factory()->create(['account_id' => $user->account_id, 'is_system' => true, 'key' => 'move']);

        $this->migration()->up();

        $this->assertTrue($meal->refresh()->updates_last_in_touch);
        $this->assertFalse($move->refresh()->updates_last_in_touch);
    }

    #[Test]
    public function it_queues_a_delayed_recount_for_each_type_that_updates_last_in_touch_and_holds_life_events(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $jim = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id]);
        $meal = LifeEventType::factory()->create(['account_id' => $user->account_id, 'is_system' => true, 'key' => 'meal']);
        $move = LifeEventType::factory()->create(['account_id' => $user->account_id, 'is_system' => true, 'key' => 'move']);
        LifeEventType::factory()->create(['account_id' => $user->account_id, 'is_system' => true, 'key' => 'coffee']);
        $this->participate($jim, $meal, '2026-10-02');
        $this->participate($jim, $move, '2026-11-01');

        $this->migration()->up();

        Queue::assertPushedOn('low', RecountLastInTouchOfType::class, fn (RecountLastInTouchOfType $job): bool => $job->lifeEventType->is($meal) && $job->delay !== null);
        Queue::assertPushed(RecountLastInTouchOfType::class, 1);
    }

    #[Test]
    public function it_fills_last_in_touch_once_the_queued_recounts_run(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $jim = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id, 'last_interaction_at' => null]);
        $meal = LifeEventType::factory()->create(['account_id' => $user->account_id, 'is_system' => true, 'key' => 'meal']);
        $move = LifeEventType::factory()->create(['account_id' => $user->account_id, 'is_system' => true, 'key' => 'move']);
        $this->participate($jim, $meal, '2026-10-02');
        $this->participate($jim, $move, '2026-11-01');

        $this->migration()->up();

        $this->assertEquals('2026-10-02', $jim->refresh()->last_interaction_at?->toDateString());
    }

    private function participate(Contact $contact, LifeEventType $type, string $on): void
    {
        $lifeEvent = LifeEvent::factory()->create([
            'account_id' => $contact->account_id,
            'vault_id' => $contact->vault_id,
            'life_event_type_id' => $type->id,
            'happened_on' => $on,
        ]);

        LifeEventParticipant::factory()->create(['life_event_id' => $lifeEvent->id, 'participant_id' => $contact->id]);
    }

    private function migration(): Migration
    {
        return require database_path('migrations/2026_11_20_000002_backfill_last_interaction_at.php');
    }
}
