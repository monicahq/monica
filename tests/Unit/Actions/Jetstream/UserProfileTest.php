<?php

namespace Tests\Unit\Actions\Jetstream;

use App\Actions\Jetstream\UserProfile;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Tests\TestCase;

class UserProfileTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @test
     */
    public function it_gets_user_profile()
    {
        config(['auth.login_providers' => collect(['provider'])]);

        $user = User::factory()->create();
        $this->actingAs($user);

        $request = $this->app->make(Request::class)
            ->setUserResolver(fn () => $user);

        $data = app(UserProfile::class)($request, []);

        $this->assertArrayHasKey('providers', $data);
        $this->assertEquals([
            'provider' => [
                'name' => 'auth.login_provider_provider',
                'logo' => '/img/auth/provider.svg',
            ],
        ], $data['providers']->toArray());
    }

    /**
     * @test
     */
    public function it_gets_user_webauthnkeys()
    {
        Carbon::setTestNow(Carbon::create(2020, 1, 1, 0, 0, 0));

        $user = User::factory()->create();
        $this->actingAs($user);

        $key = $user->webauthnKeys()->create([
            'name' => 'name',
            'type' => 'type',
            'credentialId' => '0',
            'transports' => '',
            'attestationType' => '',
            'trustPath' => '',
            'aaguid' => '',
            'credentialPublicKey' => '',
            'counter' => 0,
            'used_at' => Carbon::now(),
        ]);

        $request = $this->app->make(Request::class)
            ->setUserResolver(fn () => $user);

        Carbon::setTestNow(Carbon::create(2020, 1, 2, 0, 0, 0));

        $data = app(UserProfile::class)($request, []);

        $this->assertArrayHasKey('webauthnKeys', $data);
        $this->assertEquals([
            [
                'id' => $key->id,
                'name' => 'name',
                'type' => 'type',
                'last_used' => '1 day ago',
            ],
        ], $data['webauthnKeys']);
    }
}
