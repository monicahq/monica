<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DestroyContact;
use App\Actions\RestoreContact;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Contact;
use App\Models\LifeEvent;
use App\Models\LifeEventParticipant;
use App\Models\Relationship;
use App\Models\Reminder;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RestoreContactTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_brings_a_contact_back_with_what_went_to_the_trash_with_them(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id, 'contact_count' => 1]);
        $jan = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id, 'first_name' => 'Jan', 'last_name' => 'Levinson']);
        $relationship = Relationship::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id, 'contact_a_id' => $jan->id]);
        $reminder = Reminder::factory()->manual($jan, '2027-06-12')->create();
        $lifeEvent = LifeEvent::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id]);
        LifeEventParticipant::factory()->create(['life_event_id' => $lifeEvent->id, 'participant_id' => $jan->id]);
        new DestroyContact(user: $user, contact: $jan)->execute();

        new RestoreContact(user: $user, vault: $vault, contact: $jan->fresh())->execute();

        $this->assertNotSoftDeleted($jan);
        $this->assertNotSoftDeleted('relationships', ['id' => $relationship->id, 'deleted_with_id' => null]);
        $this->assertNotSoftDeleted($lifeEvent);
        $this->assertDatabaseHas('reminders', ['id' => $reminder->id, 'deleted_at' => null, 'is_active' => true]);
        $this->assertSame(1, $vault->fresh()->contact_count);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::ContactRestoration
                && $job->parameters === ['name' => 'Jan Levinson'],
        );
    }

    #[Test]
    public function it_refuses_a_contact_of_another_vault(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $jan = Contact::factory()->create(['account_id' => $user->account_id, 'deleted_at' => now()]);

        $this->expectException(ModelNotFoundException::class);

        new RestoreContact(user: $user, vault: $vault, contact: $jan)->execute();
    }

    #[Test]
    public function it_refuses_a_contact_already_restored_elsewhere(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id, 'contact_count' => 0]);
        $jan = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id, 'deleted_at' => now()]);
        $stale = Contact::query()->withTrashed()->findOrFail($jan->id);
        new RestoreContact(user: $user, vault: $vault, contact: $jan)->execute();

        try {
            new RestoreContact(user: $user, vault: $vault, contact: $stale)->execute();
            $this->fail('The second restoration went through.');
        } catch (ModelNotFoundException) {
            $this->assertSame(1, $vault->fresh()->contact_count);
        }
    }
}
