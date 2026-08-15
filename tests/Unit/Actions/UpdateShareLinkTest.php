<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdateShareLink;
use App\Enums\UserActionEnum;
use App\Exceptions\ShareLinkIsRevoked;
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

class UpdateShareLinkTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_changes_what_the_link_says_and_shows(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $shareLink = $this->shareLink($user);

        new UpdateShareLink(
            user: $user,
            shareLink: $shareLink,
            label: 'For the grandparents',
            recipientHint: 'Michael Scott',
            includesFiles: true,
            includesRelationships: true,
            excludedSectionIds: [12],
        )->execute();

        $this->assertDatabaseHas('share_links', [
            'id' => $shareLink->id,
            'label' => 'For the grandparents',
            'recipient_hint' => 'Michael Scott',
            'includes_files' => true,
            'includes_relationships' => true,
        ]);
        $this->assertEquals([12], $shareLink->refresh()->excluded_section_ids);
    }

    /**
     * The boxes are written as a whole, so leaving one unticked closes what it
     * names rather than leaving it as it was.
     */
    #[Test]
    public function it_closes_what_the_owner_stopped_asking_for(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $shareLink = $this->shareLink($user, ['includes_files' => true, 'includes_groups' => true]);

        new UpdateShareLink(
            user: $user,
            shareLink: $shareLink,
            label: 'For the family',
        )->execute();

        $this->assertFalse($shareLink->refresh()->includes_files);
        $this->assertFalse($shareLink->includes_groups);
    }

    #[Test]
    public function it_leaves_the_token_alone(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $shareLink = $this->shareLink($user);
        $token = $shareLink->token;

        new UpdateShareLink(
            user: $user,
            shareLink: $shareLink,
            label: 'For the grandparents',
        )->execute();

        $this->assertEquals($token, $shareLink->refresh()->token);
    }

    #[Test]
    public function it_sets_a_password_on_a_link_that_had_none(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $shareLink = $this->shareLink($user);

        new UpdateShareLink(
            user: $user,
            shareLink: $shareLink,
            label: 'For the family',
            password: 'bears beets battlestar',
        )->execute();

        $this->assertTrue(Hash::check('bears beets battlestar', (string) $shareLink->refresh()->password_hash));
    }

    #[Test]
    public function it_keeps_the_password_when_nothing_is_said_about_it(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $shareLink = $this->shareLink($user, ['password_hash' => Hash::make('bears beets battlestar')]);

        new UpdateShareLink(
            user: $user,
            shareLink: $shareLink,
            label: 'For the grandparents',
        )->execute();

        $this->assertTrue(Hash::check('bears beets battlestar', (string) $shareLink->refresh()->password_hash));
    }

    #[Test]
    public function it_takes_the_password_away_when_asked(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $shareLink = $this->shareLink($user, ['password_hash' => Hash::make('bears beets battlestar')]);

        new UpdateShareLink(
            user: $user,
            shareLink: $shareLink,
            label: 'For the family',
            removesPassword: true,
        )->execute();

        $this->assertNull($shareLink->refresh()->password_hash);
    }

    #[Test]
    public function it_refuses_a_link_in_another_account(): void
    {
        Queue::fake();

        $this->expectException(ModelNotFoundException::class);

        new UpdateShareLink(
            user: User::factory()->create(),
            shareLink: ShareLink::factory()->create(),
            label: 'For the family',
        )->execute();
    }

    #[Test]
    public function it_refuses_a_revoked_link(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $shareLink = $this->shareLink($user, ['revoked_at' => now()->subHour()]);

        $this->expectException(ShareLinkIsRevoked::class);

        new UpdateShareLink(
            user: $user,
            shareLink: $shareLink,
            label: 'For the family',
        )->execute();
    }

    #[Test]
    public function it_logs_the_action(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        new UpdateShareLink(
            user: $user,
            shareLink: $this->shareLink($user),
            label: 'For the grandparents',
        )->execute();

        Queue::assertPushedOn('low', LogUserAction::class, function (LogUserAction $job): bool {
            return $job->action === UserActionEnum::ShareLinkUpdate
                && $job->parameters === ['label' => 'For the grandparents'];
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function shareLink(User $user, array $attributes = []): ShareLink
    {
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create([
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
        ]);

        return ShareLink::factory()->create([
            ...$attributes,
            'account_id' => $user->account_id,
            'vault_id' => $vault->id,
            'shareable_type' => Contact::class,
            'shareable_id' => $contact->id,
            'created_by_user_id' => $user->id,
        ]);
    }
}
