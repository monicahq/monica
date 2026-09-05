<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\RevokeShareLink;
use App\Enums\UserActionEnum;
use App\Exceptions\ShareLinkIsRevoked;
use App\Jobs\LogUserAction;
use App\Models\ShareLink;
use App\Models\ShareLinkVisit;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RevokeShareLinkTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_closes_the_link_at_once(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $shareLink = $this->shareLink($user);

        new RevokeShareLink(user: $user, shareLink: $shareLink)->execute();

        $this->assertNotNull($shareLink->refresh()->revoked_at);
        $this->assertFalse($shareLink->isUsable());
    }

    #[Test]
    public function it_keeps_every_visit_written_against_it(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $shareLink = $this->shareLink($user);
        ShareLinkVisit::factory()->count(2)->create(['share_link_id' => $shareLink->id]);

        new RevokeShareLink(user: $user, shareLink: $shareLink)->execute();

        $this->assertCount(2, $shareLink->refresh()->visits);
    }

    #[Test]
    public function it_refuses_a_link_in_another_account(): void
    {
        Queue::fake();

        $this->expectException(ModelNotFoundException::class);

        new RevokeShareLink(
            user: User::factory()->create(),
            shareLink: ShareLink::factory()->create(),
        )->execute();
    }

    #[Test]
    public function it_refuses_a_link_already_revoked(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $shareLink = $this->shareLink($user, ['revoked_at' => now()->subHour()]);

        $this->expectException(ShareLinkIsRevoked::class);

        new RevokeShareLink(user: $user, shareLink: $shareLink)->execute();
    }

    #[Test]
    public function it_logs_the_action(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        new RevokeShareLink(
            user: $user,
            shareLink: $this->shareLink($user, ['label' => 'For the family']),
        )->execute();

        Queue::assertPushedOn('low', LogUserAction::class, function (LogUserAction $job): bool {
            return $job->action === UserActionEnum::ShareLinkRevocation
                && $job->parameters === ['label' => 'For the family'];
        });
    }

    /** @param  array<string, mixed>  $attributes */
    private function shareLink(User $user, array $attributes = []): ShareLink
    {
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        return ShareLink::factory()->forVault($vault)->create([
            ...$attributes,
            'created_by_user_id' => $user->id,
        ]);
    }
}
