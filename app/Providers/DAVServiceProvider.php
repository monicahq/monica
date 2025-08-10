<?php

namespace App\Providers;

use App\Domains\Contact\Dav\Web\Auth\AuthBackend;
use App\Domains\Contact\Dav\Web\Backend\CalDAV\CalDAVBackend;
use App\Domains\Contact\Dav\Web\Backend\CardDAV\AddressBookRoot;
use App\Domains\Contact\Dav\Web\Backend\CardDAV\CardDAVBackend;
use App\Domains\Contact\Dav\Web\DAVACL\PrincipalBackend;
use App\Domains\Contact\Dav\Web\DAVRedirect;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use LaravelSabre\LaravelSabre;
use Sabre\CalDAV\CalendarRoot;
use Sabre\CalDAV\ICSExportPlugin;
use Sabre\CalDAV\Plugin as CalDAVPlugin;
use Sabre\CardDAV\Plugin as CardDAVPlugin;
use Sabre\CardDAV\VCFExportPlugin;
use Sabre\DAV\Auth\Plugin as AuthPlugin;
use Sabre\DAV\Browser\Plugin as BrowserPlugin;
use Sabre\DAV\Sync\Plugin as SyncPlugin;
use Sabre\DAVACL\Plugin as AclPlugin;
use Sabre\DAVACL\PrincipalCollection;

class DAVServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        LaravelSabre::nodes(fn () => $this->nodes());
        LaravelSabre::plugins(fn () => $this->plugins());
    }

    /**
     * List of nodes for DAV Collection.
     */
    private function nodes(): array
    {
        $user = Auth::user();

        // Initiate custom backends for link between Sabre and Monica
        $principalBackend = app(PrincipalBackend::class, ['user' => $user]);
        $carddavBackend = app(CardDAVBackend::class)->withUser($user);
        $caldavBackend = app(CalDAVBackend::class)->withUser($user);

        return [
            new PrincipalCollection($principalBackend),
            new AddressBookRoot($principalBackend, $carddavBackend),
            new CalendarRoot($principalBackend, $caldavBackend),
        ];
    }

    /**
     * List of Sabre plugins.
     *
     * @return \Generator<\Sabre\DAV\ServerPlugin>
     */
    private function plugins()
    {
        // Authentication backend
        $authBackend = new AuthBackend;
        yield new AuthPlugin($authBackend);

        // CardDAV plugin
        yield new CardDAVPlugin;
        yield new VCFExportPlugin;

        // CalDAV plugin
        yield new CalDAVPlugin;
        yield new ICSExportPlugin;

        // Sync Plugin - rfc6578
        yield new SyncPlugin;

        // ACL plugnin
        $aclPlugin = new AclPlugin;
        $aclPlugin->allowUnauthenticatedAccess = false;
        $aclPlugin->hideNodesFromListings = true;
        yield $aclPlugin;

        // In local environment add browser plugin
        if (App::environment('local')) {
            yield new BrowserPlugin(false);
        } else {
            yield new DAVRedirect;
        }
    }
}
