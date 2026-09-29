<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Middleware\EnsureUserIsAdministrator;
use App\Models\Account;
use App\Models\Concerns\ResolvesModelInAccount;
use App\Models\Concerns\ResolvesModelInVault;
use App\Models\Contact;
use App\Models\Field;
use App\Models\MarketplaceListingVersion;
use App\Models\Passkey;
use App\Models\StructureVersion;
use App\Models\StructureVersionPage;
use App\Models\StructureVersionSection;
use App\Models\User;
use App\Models\ValueList;
use App\Models\ValueListItem;
use App\Models\Vault;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;
use Laravel\Passkeys\Passkey as PackagePasskey;
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
        'llmsText.index',
        'docs.api.index',
        'docs.api.markdown.index',
        'docs.api.openapi',
        'docs.api.show',
        'docs.api.markdown.show',
        'docs.mcp.index',
        'docs.mcp.markdown.index',
        'docs.carddav.index',
        'docs.carddav.markdown.index',
        'share.show',
        'share.password.create',
        'auth.sso.callback',
        'auth.screenshotLink.show',
        'api.v1.status',
        'api.v1.register',
        'api.v1.login',
        'telegram.webhook',
    ];

    /** @var list<class-string<Model>> */
    private array $modelsScopedByTheirController = [
        Account::class,
        Field::class,
        MarketplaceListingVersion::class,
        Passkey::class,
        PackagePasskey::class,
        StructureVersion::class,
        StructureVersionPage::class,
        StructureVersionSection::class,
        User::class,
        ValueList::class,
        ValueListItem::class,
    ];

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
