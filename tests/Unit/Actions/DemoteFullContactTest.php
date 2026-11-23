<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DemoteFullContact;
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

class DemoteFullContactTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_makes_a_full_contact_partial(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = $this->contactIn($vault, ['first_name' => 'Toby', 'last_name' => 'Flenderson']);

        $contact = new DemoteFullContact(user: $user, contact: $contact)->execute();

        $this->assertInstanceOf(Contact::class, $contact);
        $this->assertDatabaseHas('contacts', ['id' => $contact->id, 'is_partial' => true]);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::ContactDemoted
                && $job->account->id === $user->account_id
                && $job->user->id === $user->id
                && $job->vault->id === $vault->id
                && $job->isAbout($contact)
                && $job->parameters === ['name' => 'Toby Flenderson'],
        );
    }

    #[Test]
    public function it_keeps_everything_else_about_the_contact(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = $this->contactIn($vault, [
            'first_name' => 'Toby',
            'last_name' => 'Flenderson',
            'nickname' => 'Toby from HR',
            'is_favourite' => true,
        ]);

        new DemoteFullContact(user: $user, contact: $contact)->execute();

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'first_name' => 'Toby',
            'last_name' => 'Flenderson',
            'nickname' => 'Toby from HR',
            'is_favourite' => true,
        ]);
    }

    #[Test]
    public function it_frees_a_place_in_the_plan(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = $this->contactIn($vault);
        $before = $user->account->contactCount();

        new DemoteFullContact(user: $user, contact: $contact)->execute();

        $this->assertSame($before - 1, $user->account->contactCount());
    }

    #[Test]
    public function it_refuses_the_contact_representing_the_account_holder(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $me = $this->contactIn($vault, ['is_me' => true]);

        $this->expectException(AuthorizationException::class);

        new DemoteFullContact(user: $user, contact: $me)->execute();
    }

    #[Test]
    public function it_refuses_a_contact_who_is_already_partial(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = $this->contactIn($vault, ['is_partial' => true]);

        $this->expectException(InvalidArgumentException::class);

        new DemoteFullContact(user: $user, contact: $contact)->execute();
    }

    #[Test]
    public function it_refuses_an_archived_contact(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = $this->contactIn($vault, ['is_archived' => true]);

        $this->expectException(AuthorizationException::class);

        new DemoteFullContact(user: $user, contact: $contact)->execute();
    }

    #[Test]
    public function it_refuses_a_contact_in_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = Contact::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new DemoteFullContact(user: $user, contact: $contact)->execute();
    }

    /** @param array<string, mixed> $attributes */
    private function contactIn(Vault $vault, array $attributes = []): Contact
    {
        return Contact::factory()->create([
            'account_id' => $vault->account_id,
            'vault_id' => $vault->id,
            'is_partial' => false,
            ...$attributes,
        ]);
    }
}
