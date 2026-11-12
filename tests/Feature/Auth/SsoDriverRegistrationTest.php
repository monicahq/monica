<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Enums\InstanceSettingEnum;
use App\Helpers\InstanceSettings;
use App\Models\InstanceSetting;
use App\Providers\AppServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresMethod;
use PHPUnit\Framework\Attributes\Test;
use SocialiteProviders\Apple\Provider as AppleProvider;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\OpenIDConnect\OpenIDConnectServiceProvider;
use SocialiteProviders\OpenIDConnect\Provider;
use SocialiteProviders\OpenIDConnect\Providers\Auth0Provider;
use SocialiteProviders\OpenIDConnect\Providers\EntraProvider;
use SocialiteProviders\OpenIDConnect\Providers\KeycloakProvider;
use SocialiteProviders\OpenIDConnect\Providers\OktaProvider;
use Tests\TestCase;

class SsoDriverRegistrationTest extends TestCase
{
    use RefreshDatabase;

    /** @param  class-string  $driverClass */
    #[Test]
    #[DataProvider('driversOfThePackage')]
    public function it_resolves_a_provider_to_the_driver_of_the_openid_connect_package(string $provider, string $driverClass): void
    {
        config([
            "services.{$provider}.client_id" => 'dunder-mifflin-scranton',
            "services.{$provider}.client_secret" => 'that-is-what-she-said',
            "services.{$provider}.redirect" => "https://monica.test/sso/{$provider}/callback",
            "services.{$provider}.base_url" => 'https://id.dundermifflin.com',
            "services.{$provider}.server_url" => 'https://id.dundermifflin.com',
            "services.{$provider}.realm" => 'scranton',
            "services.{$provider}.domain" => 'id.dundermifflin.com',
        ]);

        $this->assertSame($driverClass, Socialite::driver($provider)::class);
    }

    /** @return array<string, array{string, class-string}> */
    public static function driversOfThePackage(): array
    {
        return [
            'Microsoft' => ['entra', EntraProvider::class],
            'Keycloak' => ['keycloak', KeycloakProvider::class],
            'Auth0' => ['auth0', Auth0Provider::class],
            'Okta' => ['okta', OktaProvider::class],
            'the generic provider' => ['openidconnect', Provider::class],
        ];
    }

    #[Test]
    public function it_puts_a_stored_secret_of_the_generic_provider_where_the_driver_reads_it(): void
    {
        InstanceSetting::factory()->create([
            'name' => InstanceSettingEnum::OpenIdConnectClientSecret->value,
            'value' => 'that-is-what-she-said',
        ]);

        InstanceSettings::apply();

        $this->assertSame('that-is-what-she-said', config('services.openidconnect.client_secret'));
    }

    #[Test]
    public function it_puts_a_stored_microsoft_identifier_where_the_driver_reads_it(): void
    {
        InstanceSetting::factory()->create([
            'name' => InstanceSettingEnum::EntraClientId->value,
            'value' => 'dunder-mifflin-scranton',
        ]);

        InstanceSettings::apply();

        $this->assertSame('dunder-mifflin-scranton', config('services.entra.client_id'));
        $this->assertSame('common', config('services.entra.tenant'));
    }

    #[Test]
    #[RequiresMethod(AppleProvider::class, 'redirect')]
    public function it_resolves_apple_to_the_driver_of_its_own_package(): void
    {
        $this->configureApple(privateKey: $this->applePrivateKey());

        $this->assertSame(AppleProvider::class, Socialite::driver('apple')::class);
    }

    #[Test]
    #[RequiresMethod(AppleProvider::class, 'redirect')]
    #[DataProvider('shapesOfAnApplePrivateKey')]
    public function it_hands_apple_a_key_it_can_read_whatever_shape_the_key_was_supplied_in(string $shape): void
    {
        $key = $this->applePrivateKey();

        $this->configureApple(privateKey: match ($shape) {
            'several lines' => $key,
            'one line' => str_replace("\n", ' ', $key),
            'escaped line breaks' => str_replace("\n", '\n', $key),
            'no markers' => (string) preg_replace('/-----[A-Z ]+-----|\s/', '', $key),
        });

        $this->assertNotFalse(openssl_pkey_get_private((string) config('services.apple.private_key')));
    }

    /** @return array<string, array{string}> */
    public static function shapesOfAnApplePrivateKey(): array
    {
        return [
            'several lines' => ['several lines'],
            'one line' => ['one line'],
            'escaped line breaks' => ['escaped line breaks'],
            'no markers' => ['no markers'],
        ];
    }

    #[Test]
    #[RequiresMethod(AppleProvider::class, 'redirect')]
    public function it_hands_apple_the_secret_generated_in_advance_when_no_key_is_supplied(): void
    {
        $this->configureApple(privateKey: '', clientSecret: 'eyJhbGciOiJFUzI1NiJ9.scranton.branch');

        $this->assertSame('eyJhbGciOiJFUzI1NiJ9.scranton.branch', $this->clientSecretOf(Socialite::driver('apple')));
    }

    #[Test]
    #[RequiresMethod(AppleProvider::class, 'redirect')]
    public function it_signs_a_secret_of_its_own_with_the_key_when_both_are_supplied(): void
    {
        $this->configureApple(privateKey: $this->applePrivateKey(), clientSecret: 'eyJhbGciOiJFUzI1NiJ9.scranton.branch');

        $secret = $this->clientSecretOf(Socialite::driver('apple'));

        $this->assertNotSame('eyJhbGciOiJFUzI1NiJ9.scranton.branch', $secret);
        $this->assertCount(3, explode('.', $secret));
    }

    #[Test]
    #[RequiresMethod(AppleProvider::class, 'redirect')]
    public function it_leaves_the_path_to_an_apple_key_file_for_the_package_to_read(): void
    {
        $path = (string) tempnam(sys_get_temp_dir(), 'AuthKey_');
        file_put_contents($path, $this->applePrivateKey());

        $this->configureApple(privateKey: $path);

        $this->assertSame($path, config('services.apple.private_key'));

        unlink($path);
    }

    #[Test]
    public function it_boots_the_package_before_the_instance_settings_are_applied(): void
    {
        $providers = array_keys($this->app->getLoadedProviders());

        $this->assertLessThan(
            array_search(AppServiceProvider::class, $providers, true),
            array_search(OpenIDConnectServiceProvider::class, $providers, true),
        );
    }

    private function configureApple(string $privateKey, ?string $clientSecret = null): void
    {
        config([
            'services.apple.client_id' => 'com.dundermifflin.monica',
            'services.apple.client_secret' => $clientSecret,
            'services.apple.team_id' => 'DUNDER1234',
            'services.apple.key_id' => 'SCRANTON12',
            'services.apple.private_key' => $privateKey,
            'services.apple.redirect' => 'https://monica.test/sso/apple/callback',
        ]);

        event(app(SocialiteWasCalled::class));
    }

    private function clientSecretOf(object $driver): string
    {
        return (fn (): string => $this->getClientSecret())->call($driver);
    }

    private function applePrivateKey(): string
    {
        $key = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => 'prime256v1']);
        openssl_pkey_export($key, $pem);

        return $pem;
    }
}
