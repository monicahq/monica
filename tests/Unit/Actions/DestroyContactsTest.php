<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DestroyContacts;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Contact;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DestroyContactsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_deletes_every_contact_given_each_as_it_would_alone(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $toby = $this->contact($user, $vault, 'Toby', 'Flenderson');
        $todd = $this->contact($user, $vault, 'Todd', 'Packer');
        $roy = $this->contact($user, $vault, 'Roy', 'Anderson');

        $outcome = new DestroyContacts(user: $user, vault: $vault, contacts: [$toby, $todd, $roy])->execute();

        $this->assertCount(3, $outcome['deleted']);
        $this->assertSame([], $outcome['kept']);
        $this->assertSoftDeleted('contacts', ['id' => $toby->id]);
        $this->assertSoftDeleted('contacts', ['id' => $todd->id]);
        $this->assertSoftDeleted('contacts', ['id' => $roy->id]);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::ContactDeletion,
        );
        $this->assertCount(3, Queue::pushed(LogUserAction::class));
    }

    #[Test]
    public function it_keeps_the_contact_representing_the_user_and_deletes_the_others(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $me = Contact::factory()->me()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id]);
        $todd = $this->contact($user, $vault, 'Todd', 'Packer');

        $outcome = new DestroyContacts(user: $user, vault: $vault, contacts: [$me, $todd])->execute();

        $this->assertSame([$todd->id], array_map(fn (Contact $contact): int => $contact->id, $outcome['deleted']));
        $this->assertSame($me->id, $outcome['kept'][0]['contact']->id);
        $this->assertSame('This is the contact that represents you, and it stays. Everything on the record is written from its point of view.', $outcome['kept'][0]['reason']);
        $this->assertNotSoftDeleted('contacts', ['id' => $me->id]);
        $this->assertSoftDeleted('contacts', ['id' => $todd->id]);
    }

    #[Test]
    public function it_skips_a_contact_deleted_in_the_meantime_and_deletes_the_others(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id, 'contact_count' => 2]);
        $creed = $this->contact($user, $vault, 'Creed', 'Bratton');
        $creed->delete();
        $todd = $this->contact($user, $vault, 'Todd', 'Packer');

        $outcome = new DestroyContacts(user: $user, vault: $vault, contacts: [$creed, $todd])->execute();

        $this->assertSame([$todd->id], array_map(fn (Contact $contact): int => $contact->id, $outcome['deleted']));
        $this->assertSame([], $outcome['kept']);
        $this->assertSame(1, $vault->fresh()->contact_count);
        $this->assertCount(1, Queue::pushed(LogUserAction::class));
    }

    #[Test]
    public function it_refuses_a_contact_of_another_vault(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $scranton = Vault::factory()->create(['account_id' => $user->account_id]);
        $stamford = Vault::factory()->create(['account_id' => $user->account_id]);
        $todd = $this->contact($user, $scranton, 'Todd', 'Packer');
        $karen = $this->contact($user, $stamford, 'Karen', 'Filippelli');

        try {
            new DestroyContacts(user: $user, vault: $scranton, contacts: [$todd, $karen])->execute();
            $this->fail('A contact of another vault was accepted.');
        } catch (ModelNotFoundException) {
            $this->assertNotSoftDeleted('contacts', ['id' => $todd->id]);
        }
    }

    #[Test]
    public function it_refuses_a_vault_of_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new DestroyContacts(user: $user, vault: $vault, contacts: [])->execute();
    }

    #[Test]
    public function it_refuses_more_than_five_hundred_contacts(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contacts = Contact::factory()->count(501)->make(['account_id' => $user->account_id, 'vault_id' => $vault->id])->all();

        $this->expectException(InvalidArgumentException::class);

        new DestroyContacts(user: $user, vault: $vault, contacts: $contacts)->execute();
    }

    private function contact(User $user, Vault $vault, string $firstName, string $lastName): Contact
    {
        return Contact::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'first_name' => $firstName,
            'last_name' => $lastName,
        ]);
    }
}
