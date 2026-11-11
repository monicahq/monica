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
use PHPUnit\Framework\Attributes\Test;
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
    public function it_boots_the_package_before_the_instance_settings_are_applied(): void
    {
        $providers = array_keys($this->app->getLoadedProviders());

        $this->assertLessThan(
            array_search(AppServiceProvider::class, $providers, true),
            array_search(OpenIDConnectServiceProvider::class, $providers, true),
        );
    }
}
