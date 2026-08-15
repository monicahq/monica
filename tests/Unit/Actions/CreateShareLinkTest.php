<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateShareLink;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Contact;
use App\Models\ShareLink;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateShareLinkTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_opens_a_link_onto_a_contact(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
        ]);

        $shareLink = new CreateShareLink(
            user: $user,
            shareable: $contact,
            label: 'For the family',
            recipientHint: 'Pam Beesly',
        )->execute();

        $this->assertInstanceOf(ShareLink::class, $shareLink);
        $this->assertDatabaseHas('share_links', [
            'id' => $shareLink->id,
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'shareable_type' => Contact::class,
            'shareable_id' => $contact->id,
            'label' => 'For the family',
            'recipient_hint' => 'Pam Beesly',
            'created_by_user_id' => $user->id,
            'view_count' => 0,
        ]);
    }

    #[Test]
    public function it_opens_a_link_onto_a_whole_vault(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $shareLink = new CreateShareLink(
            user: $user,
            shareable: $vault,
            label: 'For Michael',
        )->execute();

        $this->assertEquals(Vault::class, $shareLink->shareable_type);
        $this->assertEquals($vault->id, $shareLink->shareable_id);
        $this->assertEquals($vault->id, $shareLink->vault_id);
    }

    /**
     * A database dump must not yield a working link, so what is written down is
     * the hash and the token itself is handed back once.
     */
    #[Test]
    public function it_writes_down_the_hash_of_the_token_and_never_the_token(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $shareLink = new CreateShareLink(
            user: $user,
            shareable: Vault::factory()->create(['account_id' => $user->account_id]),
            label: 'For Michael',
        )->execute();

        $this->assertNotNull($shareLink->plainToken);
        $this->assertEquals(ShareLink::TOKEN_LENGTH, mb_strlen((string) $shareLink->plainToken));
        $this->assertEquals(hash('sha256', (string) $shareLink->plainToken), $shareLink->token);
        $this->assertDatabaseMissing('share_links', ['token' => $shareLink->plainToken]);
    }

    /**
     * The owner opts in, never out, so a link made in a hurry shows the least
     * rather than the most.
     */
    #[Test]
    public function it_shows_nothing_sensitive_unless_it_is_asked_to(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $shareLink = new CreateShareLink(
            user: $user,
            shareable: Vault::factory()->create(['account_id' => $user->account_id]),
            label: 'For Michael',
        )->execute();

        $this->assertFalse($shareLink->includes_files);
        $this->assertFalse($shareLink->includes_activities);
        $this->assertFalse($shareLink->includes_activity_narrative);
        $this->assertFalse($shareLink->includes_day_narrative);
        $this->assertFalse($shareLink->includes_relationships);
        $this->assertFalse($shareLink->includes_groups);
        $this->assertFalse($shareLink->includes_sensitive_fields);
        $this->assertEquals([], $shareLink->excluded_section_ids);
        $this->assertEquals([], $shareLink->excluded_activity_ids);
    }

    #[Test]
    public function it_keeps_what_the_owner_decided_to_show_and_to_hold_back(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $shareLink = new CreateShareLink(
            user: $user,
            shareable: Vault::factory()->create(['account_id' => $user->account_id]),
            label: 'For Michael',
            includesFiles: true,
            includesRelationships: true,
            excludedSectionIds: [12, 34],
        )->execute();

        $this->assertTrue($shareLink->includes_files);
        $this->assertTrue($shareLink->includes_relationships);
        $this->assertEquals([12, 34], $shareLink->excluded_section_ids);
    }

    #[Test]
    public function it_hashes_the_password_protecting_the_link(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $shareLink = new CreateShareLink(
            user: $user,
            shareable: Vault::factory()->create(['account_id' => $user->account_id]),
            label: 'For Michael',
            password: 'bears beets battlestar',
        )->execute();

        $this->assertNotEquals('bears beets battlestar', $shareLink->password_hash);
        $this->assertTrue(Hash::check('bears beets battlestar', (string) $shareLink->password_hash));
    }

    #[Test]
    public function it_stops_working_when_the_owner_said_it_should(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $shareLink = new CreateShareLink(
            user: $user,
            shareable: Vault::factory()->create(['account_id' => $user->account_id]),
            label: 'For Michael',
            expiresAt: now()->addWeek(),
        )->execute();

        $this->assertNotNull($shareLink->expires_at);
        $this->assertTrue($shareLink->isUsable());
    }

    #[Test]
    public function it_refuses_a_record_in_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new CreateShareLink(
            user: $user,
            shareable: Contact::factory()->create(),
            label: 'For the family',
        )->execute();
    }

    #[Test]
    public function it_logs_the_action(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id, 'name' => 'Famille']);

        new CreateShareLink(
            user: $user,
            shareable: $vault,
            label: 'For Michael',
        )->execute();

        Queue::assertPushedOn('low', LogUserAction::class, function (LogUserAction $job): bool {
            return $job->action === UserActionEnum::ShareLinkCreation
                && $job->parameters === ['label' => 'For Michael', 'name' => 'Famille'];
        });
    }
}
