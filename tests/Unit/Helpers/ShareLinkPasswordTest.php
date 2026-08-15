<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Helpers\ShareLinkPassword;
use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ShareLinkPasswordTest extends TestCase
{
    #[Test]
    public function it_hands_back_what_the_browser_gave_for_that_link(): void
    {
        $request = $this->requestWithASession();

        ShareLinkPassword::remember($request, 'a-token', 'bears beets battlestar');

        $this->assertSame('bears beets battlestar', ShareLinkPassword::remembered($request, 'a-token'));
    }

    #[Test]
    public function it_knows_nothing_about_a_link_it_was_never_given(): void
    {
        $this->assertNull(ShareLinkPassword::remembered($this->requestWithASession(), 'a-token'));
    }

    #[Test]
    public function one_link_never_answers_for_another(): void
    {
        $request = $this->requestWithASession();

        ShareLinkPassword::remember($request, 'a-token', 'bears beets battlestar');

        $this->assertNull(ShareLinkPassword::remembered($request, 'another-token'));
    }

    #[Test]
    public function it_forgets_a_password_that_stopped_working(): void
    {
        $request = $this->requestWithASession();
        ShareLinkPassword::remember($request, 'a-token', 'bears beets battlestar');

        ShareLinkPassword::forget($request, 'a-token');

        $this->assertNull(ShareLinkPassword::remembered($request, 'a-token'));
    }

    /**
     * The token never appears in the session, only its hash, so an address that
     * opens something is never written down beside the password that unlocks it.
     */
    #[Test]
    public function it_writes_the_hash_of_the_token_rather_than_the_token(): void
    {
        $request = $this->requestWithASession();

        ShareLinkPassword::remember($request, 'a-token', 'bears beets battlestar');

        $held = $request->session()->get('share-link');

        $this->assertSame([hash('sha256', 'a-token') => 'bears beets battlestar'], $held);
        $this->assertArrayNotHasKey('a-token', $held);
    }

    private function requestWithASession(): Request
    {
        $request = Request::create('/s/a-token');
        $request->setLaravelSession(new Store('erica-test', new ArraySessionHandler(120)));

        return $request;
    }
}
