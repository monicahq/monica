<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\CreateShareLink;
use App\Actions\OpenShareLink;
use App\Exceptions\ShareLinkNeedsAPassword;
use App\Exceptions\ShareLinkPasswordIsWrong;
use App\Models\ShareLink;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OpenShareLinkTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_opens_a_link_for_somebody_with_no_account(): void
    {
        $shareLink = $this->shareLink();

        $opened = new OpenShareLink(token: (string) $shareLink->plainToken)->execute();

        $this->assertInstanceOf(ShareLink::class, $opened);
        $this->assertEquals($shareLink->id, $opened->id);
    }

    #[Test]
    public function it_counts_the_opening_and_notes_when_it_happened(): void
    {
        $shareLink = $this->shareLink();

        new OpenShareLink(token: (string) $shareLink->plainToken)->execute();

        $this->assertEquals(1, $shareLink->refresh()->view_count);
        $this->assertNotNull($shareLink->last_viewed_at);
    }

    #[Test]
    public function it_writes_the_visit_down(): void
    {
        $shareLink = $this->shareLink();

        new OpenShareLink(
            token: (string) $shareLink->plainToken,
            ip: '192.168.1.1',
            userAgent: 'Mozilla/5.0',
            country: 'US',
        )->execute();

        $this->assertDatabaseHas('share_link_visits', [
            'share_link_id' => $shareLink->id,
            'account_id' => $shareLink->account_id,
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Mozilla/5.0',
            'country' => 'US',
            'password_was_required' => false,
            'password_failed' => false,
        ]);
    }

    #[Test]
    public function it_does_not_find_a_token_nothing_answers_to(): void
    {
        $this->shareLink();

        $this->expectException(ModelNotFoundException::class);

        new OpenShareLink(token: 'dwight schrute')->execute();
    }

    #[Test]
    public function it_does_not_find_a_link_that_has_expired(): void
    {
        $shareLink = $this->shareLink();
        $shareLink->expires_at = now()->subDay();
        $shareLink->save();

        $this->expectException(ModelNotFoundException::class);

        new OpenShareLink(token: (string) $shareLink->plainToken)->execute();
    }

    #[Test]
    public function it_does_not_find_a_link_that_was_revoked(): void
    {
        $shareLink = $this->shareLink();
        $shareLink->revoked_at = now();
        $shareLink->save();

        $this->expectException(ModelNotFoundException::class);

        new OpenShareLink(token: (string) $shareLink->plainToken)->execute();
    }

    /**
     * Asking for the password is the normal first step rather than a failure,
     * so nothing is written down and nothing is counted.
     */
    #[Test]
    public function it_asks_for_the_password_when_the_link_is_protected(): void
    {
        $shareLink = $this->shareLink(password: 'bears beets battlestar');

        try {
            new OpenShareLink(token: (string) $shareLink->plainToken)->execute();
            $this->fail('The link opened without its password.');
        } catch (ShareLinkNeedsAPassword) {
            $this->assertEquals(0, $shareLink->refresh()->view_count);
            $this->assertDatabaseCount('share_link_visits', 0);
        }
    }

    #[Test]
    public function it_opens_when_the_password_is_the_right_one(): void
    {
        $shareLink = $this->shareLink(password: 'bears beets battlestar');

        new OpenShareLink(
            token: (string) $shareLink->plainToken,
            password: 'bears beets battlestar',
        )->execute();

        $this->assertEquals(1, $shareLink->refresh()->view_count);
        $this->assertDatabaseHas('share_link_visits', [
            'share_link_id' => $shareLink->id,
            'password_was_required' => true,
            'password_failed' => false,
        ]);
    }

    /**
     * A run of these is the only signal the owner ever gets that a link may
     * have leaked, so the attempt is written down before it is refused.
     */
    #[Test]
    public function it_records_the_attempt_that_failed_on_the_password(): void
    {
        $shareLink = $this->shareLink(password: 'bears beets battlestar');

        try {
            new OpenShareLink(
                token: (string) $shareLink->plainToken,
                password: 'identity theft is not a joke',
            )->execute();
            $this->fail('The link opened on the wrong password.');
        } catch (ShareLinkPasswordIsWrong) {
            $this->assertDatabaseHas('share_link_visits', [
                'share_link_id' => $shareLink->id,
                'password_was_required' => true,
                'password_failed' => true,
            ]);
            $this->assertEquals(0, $shareLink->refresh()->view_count);
        }
    }

    private function shareLink(?string $password = null): ShareLink
    {
        Queue::fake();

        $user = User::factory()->create();

        return new CreateShareLink(
            user: $user,
            shareable: Vault::factory()->create(['account_id' => $user->account_id]),
            label: 'For the family',
            password: $password,
        )->execute();
    }
}
