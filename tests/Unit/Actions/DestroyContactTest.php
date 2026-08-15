<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DestroyContact;
use App\Enums\UserActionEnum;
use App\Exceptions\CannotDeleteTheMeContact;
use App\Jobs\LogUserAction;
use App\Models\Contact;
use App\Models\ShareLink;
use App\Models\TemplateVersion;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DestroyContactTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_deletes_a_contact_softly(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = Contact::factory()->create(['account_id' => $user->account_id]);

        new DestroyContact(user: $user, contact: $contact)->execute();

        $this->assertSoftDeleted('contacts', ['id' => $contact->id]);
    }

    #[Test]
    public function it_takes_the_contact_out_of_the_vault_and_version_counts(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id, 'contact_count' => 4]);
        $version = TemplateVersion::factory()->published()->used(3)->create();
        $contact = Contact::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'template_version_id' => $version->id,
        ]);

        new DestroyContact(user: $user, contact: $contact)->execute();

        $this->assertSame(3, $vault->fresh()->contact_count);
        $this->assertSame(2, $version->fresh()->record_count);
    }

    #[Test]
    public function it_never_takes_a_count_below_zero(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id, 'contact_count' => 0]);
        $version = TemplateVersion::factory()->published()->create();
        $contact = Contact::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'template_version_id' => $version->id,
        ]);

        new DestroyContact(user: $user, contact: $contact)->execute();

        $this->assertSame(0, $vault->fresh()->contact_count);
        $this->assertSame(0, $version->fresh()->record_count);
    }

    #[Test]
    public function it_refuses_to_delete_the_contact_representing_the_user(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = Contact::factory()->me()->create(['account_id' => $user->account_id]);

        $this->expectException(CannotDeleteTheMeContact::class);

        new DestroyContact(user: $user, contact: $contact)->execute();
    }

    #[Test]
    public function it_refuses_a_contact_in_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = Contact::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new DestroyContact(user: $user, contact: $contact)->execute();
    }

    /**
     * The deletion is soft, so a link left alone would go on opening the page
     * of somebody who was deleted.
     */
    #[Test]
    public function it_revokes_the_links_opened_onto_the_contact(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
        ]);
        $shareLink = ShareLink::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'shareable_type' => Contact::class,
            'shareable_id' => $contact->id,
        ]);
        $elsewhere = ShareLink::factory()->create();

        new DestroyContact(user: $user, contact: $contact)->execute();

        $this->assertNotNull($shareLink->refresh()->revoked_at);
        $this->assertNull($elsewhere->refresh()->revoked_at);
    }

    #[Test]
    public function it_logs_the_deletion(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'first_name' => 'Todd',
            'last_name' => 'Packer',
        ]);

        new DestroyContact(user: $user, contact: $contact)->execute();

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::ContactDeletion
                && $job->account->id === $user->account_id
                && $job->user->id === $user->id
                && $job->vault->id === $vault->id
                && $job->isAbout($contact)
                && $job->parameters === ['name' => 'Todd Packer'],
        );
    }
}
