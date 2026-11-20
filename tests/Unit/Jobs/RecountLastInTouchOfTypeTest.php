<?php

declare(strict_types=1);

namespace Tests\Unit\Jobs;

use App\Actions\CreateLifeEvent;
use App\Jobs\RecountLastInTouchOfType;
use App\Models\Contact;
use App\Models\User;
use App\Models\Vault;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RecountLastInTouchOfTypeTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_moves_last_in_touch_once_the_type_updates_it(): void
    {
        Queue::fake();
        $this->travelTo(CarbonImmutable::parse('2026-11-20'));

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $jim = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id, 'last_interaction_at' => null]);
        $move = $this->lifeEventTypeAsking($user, 'Move');
        $move->update(['updates_last_in_touch' => false]);

        new CreateLifeEvent(
            user: $user,
            vault: $vault,
            contact: $jim,
            lifeEventType: $move,
            happenedOn: CarbonImmutable::parse('2026-11-12'),
        )->execute();

        $this->assertNull($jim->refresh()->last_interaction_at);

        $move->update(['updates_last_in_touch' => true]);

        new RecountLastInTouchOfType(lifeEventType: $move)->handle();

        $this->assertEquals('2026-11-12', $jim->refresh()->last_interaction_at?->toDateString());
    }
}
