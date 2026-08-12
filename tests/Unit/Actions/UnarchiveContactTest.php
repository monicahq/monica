<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UnarchiveContact;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Contact;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UnarchiveContactTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_puts_a_contact_back_in_the_main_lists(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = Contact::factory()->archived()->create(['account_id' => $user->account_id]);

        $contact = new UnarchiveContact(user: $user, contact: $contact)->execute();

        $this->assertFalse($contact->is_archived);
        $this->assertDatabaseHas('contacts', ['id' => $contact->id, 'is_archived' => false]);
    }

    #[Test]
    public function it_refuses_a_contact_in_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = Contact::factory()->archived()->create();

        $this->expectException(ModelNotFoundException::class);

        new UnarchiveContact(user: $user, contact: $contact)->execute();
    }

    #[Test]
    public function it_logs_the_unarchiving(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->archived()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'first_name' => 'Roy',
            'last_name' => 'Anderson',
        ]);

        new UnarchiveContact(user: $user, contact: $contact)->execute();

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::ContactUnarchived
                && $job->account->id === $user->account_id
                && $job->user->id === $user->id
                && $job->vault->id === $vault->id
                && $job->isAbout($contact)
                && $job->parameters === ['name' => 'Roy Anderson'],
        );
    }
}
