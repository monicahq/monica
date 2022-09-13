<?php

namespace Tests\Unit\Actions\Jetstream;

use App\Actions\Jetstream\UserProfile;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Tests\TestCase;

class UserProfileTest extends TestCase
{
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
        $this->assertEquals(['provider'], $data['providers']->toArray());
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
                'last_active' => '1 day ago',
            ],
        ], $data['webauthnKeys']);
    }
}
