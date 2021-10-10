<?php

namespace Tests\Browser\Auth;

use Tests\TestCase;
use GuzzleHttp\Client;
use App\Models\User\User;
use Tests\Traits\Asserts;
use Tests\Traits\ApiSignIn;
use Illuminate\Testing\TestResponse;
use Laravel\Passport\ClientRepository;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;

class AuthControllerTest extends TestCase
{
    use ApiSignIn,
        Asserts;

    public function setUp(): void
    {
        parent::setUp();

        if ($this->getActualConnection() != 'testing') {
            $this->markTestSkipped("Set DB_CONNECTION on 'testing' to run this test.");
        }
    }

    protected $jsonStructureOAuthLogin = [
        'access_token',
        'expires_in',
    ];

    private const OAUTH_LOGIN_URL = 'http://localhost:8001/oauth/login';

    public function test_oauth_login()
    {
        $repository = new ClientRepository();
        $client = null;
        try {
            $client = $repository->createPasswordGrantClient(
                null, config('app.name'), config('app.url')
            );

            $this->setEnvironmentValue([
                'PASSPORT_PASSWORD_GRANT_CLIENT_ID' => $client->id,
                'PASSPORT_PASSWORD_GRANT_CLIENT_SECRET' => $client->secret,
            ]);

            $userPassword = 'password';
            $user = factory(User::class)->create([
                'password' => bcrypt($userPassword),
            ]);

            $response = $this->postClient(self::OAUTH_LOGIN_URL, [
                'email' => $user->email,
                'password' => $userPassword,
            ]);

            $response->assertStatus(200);

            $response->assertJsonStructure($this->jsonStructureOAuthLogin);
        } finally {
            if ($client) {
                $repository->delete($client);
            }
            if ($user) {
                $user->account->delete();
            }
        }
    }

    public function test_oauth_login_bad_password()
    {
        $repository = new ClientRepository();
        $client = null;
        try {
            $client = $repository->createPasswordGrantClient(
                null, config('app.name'), config('app.url')
            );

            $this->setEnvironmentValue([
                'PASSPORT_PASSWORD_GRANT_CLIENT_ID' => $client->id,
                'PASSPORT_PASSWORD_GRANT_CLIENT_SECRET' => $client->secret,
            ]);

            $userPassword = 'password';
            $user = factory(User::class)->create([
                'password' => bcrypt($userPassword),
            ]);

            $response = $this->postClient(self::OAUTH_LOGIN_URL, [
                'email' => $user->email,
                'password' => 'wrongPassword',
            ]);

            $this->expectNotAuthorized($response);
        } finally {
            if ($client) {
                $repository->delete($client);
            }
            if ($user) {
                $user->account->delete();
            }
        }
    }

    public function test_oauth_login_wrong_mail()
    {
        $response = $this->postClient(self::OAUTH_LOGIN_URL, [
            'email' => 'badmail',
            'password' => 'xx',
        ]);

        $response->assertStatus(422);
        $this->expectDataError($response, ['The email must be a valid email address.']);
    }

    public function test_oauth_login_wrong_password()
    {
        $response = $this->postClient(self::OAUTH_LOGIN_URL, [
            'email' => 'mail@mail.com',
            'password' => 'xx',
        ]);

        $this->expectNotAuthorized($response);
    }

    public function test_oauth_login_2fa()
    {
        $repository = new ClientRepository();
        $client = null;
        try {
            $client = $repository->createPasswordGrantClient(
                null, config('app.name'), config('app.url')
            );

            $this->setEnvironmentValue([
                'PASSPORT_PASSWORD_GRANT_CLIENT_ID' => $client->id,
                'PASSPORT_PASSWORD_GRANT_CLIENT_SECRET' => $client->secret,
            ]);

            $userPassword = 'password';
            $user = factory(User::class)->create([
                'password' => bcrypt($userPassword),
                'google2fa_secret' => 'UFKZDTYO64WDEZPPQEO4HF3PC5UUTFLE',
            ]);

            $response = $this->postClient(self::OAUTH_LOGIN_URL, [
                'email' => $user->email,
                'password' => $userPassword,
            ]);

            $response->assertStatus(200);

            $response->assertSee('Two Factor Authentication');
        } finally {
            if ($client) {
                $repository->delete($client);
            }
            if ($user) {
                $user->account->delete();
            }
        }
    }

    private function getActualConnection()
    {
        $handle = fopen('.env', 'r');
        if (! $handle) {
            return;
        }

        $value = null;
        while (($line = fgets($handle)) !== false) {
            if (preg_match('/DB_CONNECTION=(.{1,})/', $line, $matches)) {
                $value = $matches[1];
                break;
            }
        }

        fclose($handle);

        return $value;
    }

    private function setEnvironmentValue(array $values)
    {
        $envFile = app()->environmentFilePath();
        $str = file_get_contents($envFile);

        if (count($values) > 0) {
            foreach ($values as $envKey => $envValue) {
                $str .= "\n"; // In case the searched variable is in the last line without \n
                $keyPosition = strpos($str, "{$envKey}=");
                $endOfLinePosition = strpos($str, "\n", $keyPosition);
                $oldLine = substr($str, $keyPosition, $endOfLinePosition - $keyPosition);

                // If key does not exist, add it
                if (! $keyPosition || ! $endOfLinePosition || ! $oldLine) {
                    $str .= "{$envKey}={$envValue}\n";
                } else {
                    $str = str_replace($oldLine, "{$envKey}={$envValue}", $str);
                }
            }
        }

        $str = substr($str, 0, -1);

        return file_put_contents($envFile, $str);
    }

    /**
     * @param  string  $path
     * @param  array  $param
     * @return TestResponse
     */
    protected function postClient($path, $param)
    {
        try {
            $http = new Client([
                'timeout' => 30,
            ]);
            $response = $http->post($path, [
                'form_params' => $param,
            ]);
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $response = $e->getResponse();
        }

        $factory = new HttpFoundationFactory();
        $response = $factory->createResponse($response);

        return TestResponse::fromBaseResponse($response);
    }
}
