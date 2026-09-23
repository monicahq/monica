<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\ArchiveContact;
use App\Enums\ReminderInactiveReasonEnum;
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

class ArchiveContactTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_takes_a_contact_out_of_the_main_lists(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'first_name' => 'Roy',
            'last_name' => 'Anderson',
        ]);

        $archived = new ArchiveContact(user: $user, contact: $contact)->execute();

        $this->assertTrue($archived->is_archived);
        $this->assertDatabaseHas('contacts', ['id' => $contact->id, 'is_archived' => true]);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::ContactArchived
                && $job->account->id === $user->account_id
                && $job->user->id === $user->id
                && $job->vault->id === $vault->id
                && $job->isAbout($contact)
                && $job->parameters === ['name' => 'Roy Anderson'],
        );
    }

    #[Test]
    public function it_leaves_the_contact_in_the_count_of_the_vault(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id, 'contact_count' => 4]);
        $contact = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id]);

        new ArchiveContact(user: $user, contact: $contact)->execute();

        $this->assertSame(4, $vault->fresh()->contact_count);
    }

    #[Test]
    public function it_refuses_a_contact_in_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = Contact::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new ArchiveContact(user: $user, contact: $contact)->execute();
    }

    #[Test]
    public function it_silences_every_reminder_about_them(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $reminder = $this->reminderAbout($user);

        new ArchiveContact(user: $user, contact: $reminder->remindable)->execute();

        $this->assertDatabaseHas('reminders', [
            'id' => $reminder->id,
            'is_active' => false,
            'inactive_reason' => ReminderInactiveReasonEnum::RecordArchived->value,
        ]);
    }
}
