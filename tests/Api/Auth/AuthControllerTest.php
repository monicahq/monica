<?php

namespace Tests\Api\Auth;

use Tests\ApiTestCase;
use App\Models\User\User;
use Laravel\Passport\ClientRepository;

class AuthControllerTest extends ApiTestCase
{
    public function setUp()
    {
        parent::setUp();

        if ($this->getActualConnection() != 'testing') {
            $this->markTestSkipped("Set DB_CONNECTION on 'testing' to run this test.");
        }
    }

    private function getActualConnection()
    {
        $handle = fopen('.env', 'r');
        if (! $handle) {
            return;
        }
        while (($line = fgets($handle)) !== false) {
            if (preg_match('/DB_CONNECTION=(.{1,})/', $line, $matches)) {
                fclose($handle);

                return $matches[1];
            }
        }

        fclose($handle);
    }

    protected $jsonStructureOAuthLogin = [
        'access_token',
        'expires_in',
    ];

    public function test_oauth_login()
    {
        $client = (new ClientRepository())->createPasswordGrantClient(
            null, config('app.name'), config('app.url')
        );

        config([
            'monica.mobile_client_id' => $client->id,
            'monica.mobile_client_secret' => $client->secret,
        ]);

        $userPassword = 'password';
        $user = factory(User::class)->create([
            'password' => bcrypt($userPassword),
        ]);

        $response = $this->json('POST', '/oauth/login', [
            'email' => $user->email,
            'password' => $userPassword,
        ]);

        $response->assertStatus(200);

        $response->assertJsonStructure($this->jsonStructureOAuthLogin);
    }
}
