<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\PromotePartialContact;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Contact;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PromotePartialContactTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_makes_a_partial_contact_a_full_one(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'first_name' => 'Holly',
            'last_name' => 'Flax',
            'is_partial' => true,
        ]);

        $contact = new PromotePartialContact(user: $user, contact: $contact)->execute();

        $this->assertInstanceOf(Contact::class, $contact);
        $this->assertDatabaseHas('contacts', ['id' => $contact->id, 'is_partial' => false]);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::ContactPromoted
                && $job->account->id === $user->account_id
                && $job->user->id === $user->id
                && $job->vault->id === $vault->id
                && $job->isAbout($contact)
                && $job->parameters === ['name' => 'Holly Flax'],
        );
    }

    #[Test]
    public function it_refuses_a_contact_who_is_already_a_full_one(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'is_partial' => false,
        ]);

        $this->expectException(InvalidArgumentException::class);

        new PromotePartialContact(user: $user, contact: $contact)->execute();
    }

    #[Test]
    public function it_refuses_once_the_free_plan_holds_every_contact_it_can(): void
    {
        Queue::fake();
        config(['monica.self_hosted' => false, 'monica.free_contact_limit' => 1]);

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id]);
        $contact = Contact::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'is_partial' => true,
        ]);

        try {
            new PromotePartialContact(user: $user, contact: $contact)->execute();
            $this->fail('The promotion went through past the ceiling of the free plan.');
        } catch (AuthorizationException) {
            $this->assertTrue($contact->refresh()->is_partial);
            Queue::assertNothingPushed();
        }
    }

    #[Test]
    public function it_refuses_a_contact_in_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = Contact::factory()->create(['is_partial' => true]);

        $this->expectException(ModelNotFoundException::class);

        new PromotePartialContact(user: $user, contact: $contact)->execute();
    }
}
