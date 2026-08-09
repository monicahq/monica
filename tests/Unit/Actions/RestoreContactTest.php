<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\RestoreContact;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Contact;
use App\Models\TemplateVersion;
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
    public function it_brings_a_deleted_contact_back(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = Contact::factory()->create(['account_id' => $user->account_id]);
        $contact->delete();

        $contact = new RestoreContact(user: $user, contact: $contact)->execute();

        $this->assertFalse($contact->trashed());
        $this->assertDatabaseHas('contacts', ['id' => $contact->id, 'deleted_at' => null]);
    }

    #[Test]
    public function it_gives_the_contact_back_to_the_vault_and_version_counts(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id, 'contact_count' => 3]);
        $version = TemplateVersion::factory()->published()->used(2)->create();
        $contact = Contact::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'template_version_id' => $version->id,
        ]);
        $contact->delete();

        new RestoreContact(user: $user, contact: $contact)->execute();

        $this->assertSame(4, $vault->fresh()->contact_count);
        $this->assertSame(3, $version->fresh()->record_count);
    }

    #[Test]
    public function it_counts_a_contact_who_never_left_only_once(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id, 'contact_count' => 3]);
        $contact = Contact::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
        ]);

        new RestoreContact(user: $user, contact: $contact)->execute();

        $this->assertSame(3, $vault->fresh()->contact_count);
    }

    #[Test]
    public function it_refuses_a_contact_in_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = Contact::factory()->create();
        $contact->delete();

        $this->expectException(ModelNotFoundException::class);

        new RestoreContact(user: $user, contact: $contact)->execute();
    }

    #[Test]
    public function it_logs_the_restoration(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'first_name' => 'Ryan',
            'last_name' => 'Howard',
        ]);
        $contact->delete();

        new RestoreContact(user: $user, contact: $contact)->execute();

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::ContactRestoration
                && $job->account->id === $user->account_id
                && $job->user->id === $user->id
                && $job->vault->id === $vault->id
                && $job->loggable->is($contact)
                && $job->parameters === ['name' => 'Ryan Howard'],
        );
    }
}
