<?php

declare(strict_types=1);

namespace Tests\Feature\Dav;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_answers_a_device_holding_a_key(): void
    {
        $user = User::factory()->create();
        $key = $user->createToken('Dwight’s phone')->plainTextToken;

        $response = $this->dav('PROPFIND', 'dav/', $user->email, $key);

        $response->assertStatus(207);
    }

    #[Test]
    public function it_refuses_a_device_with_no_credential(): void
    {
        $response = $this->call('PROPFIND', 'dav/');

        $response->assertStatus(401);
        $this->assertStringStartsWith('Basic realm=', (string) $response->headers->get('WWW-Authenticate'));
    }

    #[Test]
    public function it_refuses_a_key_that_was_never_minted(): void
    {
        $user = User::factory()->create();

        $response = $this->dav('PROPFIND', 'dav/', $user->email, 'not-a-key');

        $response->assertStatus(401);
    }

    #[Test]
    public function it_refuses_a_revoked_key(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('Dwight’s phone');
        $key = $token->plainTextToken;
        $token->accessToken->delete();

        $response = $this->dav('PROPFIND', 'dav/', $user->email, $key);

        $response->assertStatus(401);
    }

    #[Test]
    public function it_refuses_the_account_password(): void
    {
        $user = User::factory()->create(['password' => Hash::make('beets')]);

        $response = $this->dav('PROPFIND', 'dav/', $user->email, 'beets');

        $response->assertStatus(401);
    }

    #[Test]
    public function it_refuses_a_key_presented_with_somebody_elses_email(): void
    {
        $user = User::factory()->create();
        $stranger = User::factory()->create();
        $key = $user->createToken('Dwight’s phone')->plainTextToken;

        $response = $this->dav('PROPFIND', 'dav/', $stranger->email, $key);

        $response->assertStatus(401);
    }

    #[Test]
    public function it_accepts_the_email_whatever_case_it_arrives_in(): void
    {
        $user = User::factory()->create(['email' => 'dwight@dundermifflin.test']);
        $key = $user->createToken('Dwight’s phone')->plainTextToken;

        $response = $this->dav('PROPFIND', 'dav/', 'Dwight@DunderMifflin.test', $key);

        $response->assertStatus(207);
    }

    #[Test]
    public function it_records_when_the_key_was_last_used(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('Dwight’s phone');
        $key = $token->plainTextToken;

        $this->assertNull($token->accessToken->last_used_at);

        $this->dav('PROPFIND', 'dav/', $user->email, $key);

        $this->assertNotNull($token->accessToken->refresh()->last_used_at);
    }

    #[Test]
    public function it_finds_the_principal_of_the_person_the_key_acts_as(): void
    {
        $user = User::factory()->create();
        $key = $user->createToken('Dwight’s phone')->plainTextToken;

        $response = $this->dav('PROPFIND', 'dav/principals/'.$user->email, $user->email, $key, body: <<<'XML'
            <?xml version="1.0"?>
            <d:propfind xmlns:d="DAV:" xmlns:s="http://sabredav.org/ns">
              <d:prop><d:displayname/><s:email-address/></d:prop>
            </d:propfind>
            XML);

        $response->assertStatus(207);
        $response->assertSee($user->email, escape: false);
        $response->assertSee($user->name, escape: false);
    }

    #[Test]
    public function it_hides_the_principal_of_somebody_else(): void
    {
        $user = User::factory()->create();
        $stranger = User::factory()->create();
        $key = $user->createToken('Dwight’s phone')->plainTextToken;

        $response = $this->dav('PROPFIND', 'dav/principals/'.$stranger->email, $user->email, $key);

        $response->assertStatus(404);
    }

    #[Test]
    public function it_answers_a_person_signed_in_on_the_session_in_the_local_environment(): void
    {
        $this->app->detectEnvironment(fn (): string => 'local');
        $user = User::factory()->create();

        $response = $this->davOnTheSessionOf($user);

        $response->assertStatus(207);
        $response->assertSee('/dav/addressbooks/', escape: false);
    }

    #[Test]
    public function it_refuses_a_person_signed_in_on_the_session_outside_the_local_environment(): void
    {
        $user = User::factory()->create();

        $response = $this->davOnTheSessionOf($user);

        $response->assertStatus(401);
    }

    private function davOnTheSessionOf(User $user): TestResponse
    {
        $session = $this->app['session']->driver();

        $session->start();
        $session->put(Auth::guard('web')->getName(), $user->id);
        $session->save();
        $session->flush();

        $this->withCookie((string) config('session.cookie'), $session->getId());

        return $this->call('PROPFIND', 'dav/', [], $this->prepareCookiesForRequest(), [], [
            'HTTP_DEPTH' => '1',
            'CONTENT_TYPE' => 'application/xml',
        ]);
    }

    private function dav(string $method, string $uri, string $email, string $key, string $depth = '0', string $body = ''): TestResponse
    {
        return $this->call($method, $uri, [], [], [], [
            'HTTP_AUTHORIZATION' => 'Basic '.base64_encode($email.':'.$key),
            'HTTP_DEPTH' => $depth,
            'CONTENT_TYPE' => 'application/xml',
        ], $body);
    }
}
