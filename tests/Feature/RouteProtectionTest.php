<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Middleware\EnsurePasswordIsConfirmedWhenThereIsOne;
use App\Http\Middleware\EnsureUserIsAdministrator;
use App\Models\Account;
use App\Models\Concerns\ResolvesModelInAccount;
use App\Models\Concerns\ResolvesModelInVault;
use App\Models\Contact;
use App\Models\Field;
use App\Models\MarketplaceListingVersion;
use App\Models\MobileSession;
use App\Models\Passkey;
use App\Models\PreviewListEntry;
use App\Models\SecurityKey;
use App\Models\StructureVersion;
use App\Models\StructureVersionPage;
use App\Models\User;
use App\Models\ValueList;
use App\Models\ValueListItem;
use App\Models\Vault;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;
use Laravel\Passkeys\Passkey as PackagePasskey;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RouteProtectionTest extends TestCase
{
    use RefreshDatabase;

    /** @var list<string> */
    private array $publicRoutes = [
        'home.index',
        'marketplace.index',
        'docs.index',
        'changelog.index',
        'llmsText.index',
        'robotsText.index',
        'sitemap.index',
        'docs.api.index',
        'docs.api.markdown.index',
        'docs.api.openapi',
        'docs.api.show',
        'docs.api.markdown.show',
        'docs.api.changelog.index',
        'docs.api.changelog.markdown.index',
        'docs.api.changelog.show',
        'docs.api.changelog.markdown.show',
        'docs.mcp.index',
        'docs.mcp.tools.show',
        'docs.mcp.markdown.index',
        'docs.carddav.index',
        'docs.carddav.markdown.index',
        'docs.portal.index',
        'docs.portal.show',
        'docs.portal.markdown.index',
        'docs.portal.markdown.section',
        'docs.portal.markdown.show',
        'share.show',
        'share.password.create',
        'auth.sso.callback',
        'auth.sso.relay',
        'auth.screenshotLink.show',
        'api.v1.status',
        'oauth.metadata.show',
        'oauth.revocations.create',
        'api.v1.register',
        'api.v1.login',
        'telegram.webhook',
        'notFound.show',
    ];

    /** @var list<class-string<Model>> */
    private array $modelsScopedByTheirController = [
        Account::class,
        Field::class,
        MarketplaceListingVersion::class,
        MobileSession::class,
        Passkey::class,
        PackagePasskey::class,
        PreviewListEntry::class,
        SecurityKey::class,
        StructureVersion::class,
        StructureVersionPage::class,
        User::class,
        ValueList::class,
        ValueListItem::class,
    ];

    /** @return array<string, array{string}> */
    public static function routesAskingForThePassword(): array
    {
        return [
            'settings.email.update' => ['settings.email.update'],
            'settings.account.destroy' => ['settings.account.destroy'],
            'settings.accountExports.create' => ['settings.accountExports.create'],
            'settings.accountExportDownloads.show' => ['settings.accountExportDownloads.show'],
            'settings.accountImports.create' => ['settings.accountImports.create'],
            'settings.accountImports.show' => ['settings.accountImports.show'],
            'settings.accountImports.update' => ['settings.accountImports.update'],
            'settings.accountImports.destroy' => ['settings.accountImports.destroy'],
            'settings.password.create' => ['settings.password.create'],
            'settings.password.update' => ['settings.password.update'],
            'settings.password.destroy' => ['settings.password.destroy'],
            'settings.twoFactor.new' => ['settings.twoFactor.new'],
            'settings.twoFactor.create' => ['settings.twoFactor.create'],
            'settings.twoFactor.destroy' => ['settings.twoFactor.destroy'],
            'settings.recoveryCodes.create' => ['settings.recoveryCodes.create'],
            'settings.sso.redirect' => ['settings.sso.redirect'],
            'settings.sso.destroy' => ['settings.sso.destroy'],
            'settings.passkeys.update' => ['settings.passkeys.update'],
            'passkey.registration-options' => ['passkey.registration-options'],
            'passkey.store' => ['passkey.store'],
            'passkey.destroy' => ['passkey.destroy'],
            'settings.securityKeyOptions.show' => ['settings.securityKeyOptions.show'],
            'settings.securityKeys.create' => ['settings.securityKeys.create'],
            'settings.securityKeys.update' => ['settings.securityKeys.update'],
            'settings.securityKeys.destroy' => ['settings.securityKeys.destroy'],
            'settings.apiKeys.create' => ['settings.apiKeys.create'],
            'settings.apiKeys.destroy' => ['settings.apiKeys.destroy'],
            'settings.connectedAssistants.destroy' => ['settings.connectedAssistants.destroy'],
            'settings.mobileSessions.destroy' => ['settings.mobileSessions.destroy'],
            'admin.configuration.update' => ['admin.configuration.update'],
            'admin.configuration.destroy' => ['admin.configuration.destroy'],
            'admin.configuration.orphans.destroy' => ['admin.configuration.orphans.destroy'],
            'admin.configuration.telegram.registration.create' => ['admin.configuration.telegram.registration.create'],
            'admin.configuration.telegram.secret.create' => ['admin.configuration.telegram.secret.create'],
            'admin.flags.activation.create' => ['admin.flags.activation.create'],
            'admin.flags.activation.destroy' => ['admin.flags.activation.destroy'],
        ];
    }

    #[Test]
    public function every_route_of_the_application_asks_who_is_signed_in_unless_it_is_listed_as_public(): void
    {
        foreach ($this->applicationRoutes() as $route) {
            if (in_array($route->getName(), $this->publicRoutes, true)) {
                continue;
            }

            $middleware = collect(app('router')->gatherRouteMiddleware($route));

            $this->assertTrue(
                $middleware->contains(fn (string $name): bool => str_starts_with($name, Authenticate::class)
                    || $name === RedirectIfAuthenticated::class
                    || $name === EnsureUserIsAdministrator::class),
                "The route `{$route->uri()}` answers somebody nobody has signed in as.",
            );
        }
    }

    #[Test]
    public function every_route_of_the_admin_space_is_closed_to_whoever_does_not_run_the_instance(): void
    {
        $adminRoutes = collect($this->applicationRoutes())
            ->filter(fn (RoutingRoute $route): bool => str_starts_with($route->uri(), 'admin'));

        $this->assertNotEmpty($adminRoutes);

        foreach ($adminRoutes as $route) {
            $this->assertContains(
                EnsureUserIsAdministrator::class,
                app('router')->gatherRouteMiddleware($route),
                "The route `{$route->uri()}` is in the admin space but lets anybody signed in through.",
            );
        }
    }

    #[Test]
    public function it_sends_a_visitor_to_sign_in(): void
    {
        $this->get(route('vaults.index'))->assertRedirect(route('auth.login.new'));
    }

    #[Test]
    public function it_answers_a_visitor_of_the_api_with_unauthorized(): void
    {
        $this->json('GET', '/api/v1/vaults')->assertUnauthorized();
    }

    #[Test]
    public function every_model_a_route_binds_is_looked_up_inside_the_account_of_whoever_asks(): void
    {
        foreach ($this->applicationRoutes() as $route) {
            foreach ($route->signatureParameters(['subClass' => Model::class]) as $parameter) {
                $model = $parameter->getType()->getName();

                if (in_array($model, $this->modelsScopedByTheirController, true)) {
                    continue;
                }

                $traits = class_uses_recursive($model);

                $this->assertTrue(
                    in_array(ResolvesModelInAccount::class, $traits, true) || in_array(ResolvesModelInVault::class, $traits, true),
                    "The route `{$route->uri()}` binds {$model}, which can be reached from another account.",
                );
            }
        }
    }

    #[Test]
    #[DataProvider('routesAskingForThePassword')]
    public function a_sensitive_route_asks_for_the_password_again(string $name): void
    {
        $middleware = collect(app('router')->gatherRouteMiddleware(Route::getRoutes()->getByName($name)));

        $this->assertTrue(
            $middleware->contains(fn (string $class): bool => str_starts_with($class, RequirePassword::class)
                || $class === EnsurePasswordIsConfirmedWhenThereIsOne::class),
            "The route `{$name}` no longer asks for the password again.",
        );
    }

    #[Test]
    public function a_record_of_another_account_is_not_found_through_its_address(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create(['first_name' => 'Jan', 'last_name' => 'Levinson']);

        $this->actingAs($user)
            ->get(route('contacts.edit', [$vault, $contact]))
            ->assertNotFound();
    }

    /** @return list<RoutingRoute> */
    private function applicationRoutes(): array
    {
        return collect(Route::getRoutes()->getRoutes())
            ->filter(fn (RoutingRoute $route): bool => str_starts_with((string) $route->getControllerClass(), 'App\\Http\\Controllers\\'))
            ->values()
            ->all();
    }
}
