<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Actions\CreateLifeEvent;
use App\Jobs\RecordLastInTouchOfTheDay;
use App\Models\Contact;
use App\Models\User;
use App\Models\Vault;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RecordLastInTouchOfTheDayTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_records_a_life_event_once_its_day_has_come(): void
    {
        Queue::fake();
        $this->travelTo(CarbonImmutable::parse('2026-11-20'));

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $jim = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id, 'last_interaction_at' => null]);

        new CreateLifeEvent(
            user: $user,
            vault: $vault,
            contact: $jim,
            lifeEventType: $this->lifeEventTypeAsking($user, 'Meal'),
            happenedOn: CarbonImmutable::parse('2026-11-21'),
        )->execute();

        new RecordLastInTouchOfTheDay()->handle();

        $this->assertNull($jim->refresh()->last_interaction_at);

        $this->travelTo(CarbonImmutable::parse('2026-11-21 02:15'));

        new RecordLastInTouchOfTheDay()->handle();

        $this->assertEquals('2026-11-21', $jim->refresh()->last_interaction_at?->toDateString());
    }
}
