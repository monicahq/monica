<?php

declare(strict_types=1);

namespace App\Providers;

use App\Enums\ApiKeyScopeEnum;
use App\Http\Dav\AddressBookBackend;
use App\Http\Dav\CalendarBackend;
use App\Http\Dav\CardDavPlugin;
use App\Http\Dav\DAVRedirect;
use App\Http\Dav\MoveGuard;
use App\Http\Dav\PrincipalBackend;
use App\Http\Dav\ReadOnlyCalendar;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;
use LaravelSabre\Http\Auth\AuthBackend;
use LaravelSabre\LaravelSabre;
use Sabre\CalDAV\ICSExportPlugin;
use Sabre\CalDAV\Plugin as CalDavPlugin;
use Sabre\CardDAV\AddressBookRoot;
use Sabre\CardDAV\VCFExportPlugin;
use Sabre\DAV\Auth\Plugin as AuthPlugin;
use Sabre\DAV\Browser\Plugin as BrowserPlugin;
use Sabre\DAV\SimpleCollection;
use Sabre\DAV\Sync\Plugin as SyncPlugin;
use Sabre\DAVACL\Plugin as AclPlugin;
use Sabre\DAVACL\PrincipalCollection;

class DavServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->acceptTheKeyAsABasicPassword();

        LaravelSabre::principal(fn (User $user): string => (string) $user->email);

        LaravelSabre::nodes(fn (): array => $this->nodes());

        LaravelSabre::plugins(fn (): array => $this->plugins());
    }

    private function acceptTheKeyAsABasicPassword(): void
    {
        Sanctum::getAccessTokenFromRequestUsing(function (Request $request): ?string {
            if (! $request->routeIs('sabre.dav')) {
                return $request->bearerToken();
            }

            return $request->bearerToken() ?? $request->getPassword();
        });
    }

    /** @return array<int, mixed> */
    private function nodes(): array
    {
        $user = Auth::guard('sanctum')->user();

        if (! $user instanceof User) {
            return [];
        }

        $principals = new PrincipalBackend($user);

        return [
            new PrincipalCollection($principals),
            new AddressBookRoot($principals, $this->addressBooks($user)),
            new SimpleCollection('calendars', [
                new SimpleCollection($user->email, $this->calendars($user, $principals->uri())),
            ]),
        ];
    }

    /** @return array<int, ReadOnlyCalendar> */
    private function calendars(User $user, string $principalUri): array
    {
        $backend = new CalendarBackend($user);

        return array_map(
            fn (array $calendar): ReadOnlyCalendar => new ReadOnlyCalendar($backend, $calendar),
            $backend->getCalendarsForUser($principalUri),
        );
    }

    private function addressBooks(User $user): AddressBookBackend
    {
        return once(fn (): AddressBookBackend => new AddressBookBackend($user, $this->reachesSensitiveFields($user)));
    }

    private function reachesSensitiveFields(User $user): bool
    {
        $token = $user->currentAccessToken();

        if (! $token instanceof PersonalAccessToken) {
            return false;
        }

        return in_array(ApiKeyScopeEnum::SensitiveFields->value, $token->abilities ?? [], true);
    }

    /** @return array<int, mixed> */
    private function plugins(): array
    {
        $acl = new AclPlugin;
        $acl->allowUnauthenticatedAccess = false;
        $acl->hideNodesFromListings = true;

        $plugins = [
            new AuthPlugin(new AuthBackend),
            $acl,
            new CardDavPlugin,
            new VCFExportPlugin,
            new CalDavPlugin,
            new ICSExportPlugin,
            new SyncPlugin,
        ];

        $user = Auth::guard('sanctum')->user();

        if ($user instanceof User) {
            $plugins[] = new MoveGuard($user, $this->addressBooks($user));
        }

        if ($this->app->environment('local')) {
            $plugins[] = new BrowserPlugin(false);
        } else {
            $plugins[] = new DAVRedirect;
        }

        return $plugins;
    }
}
