<?php

namespace App\Providers;

use Illuminate\Support\Arr;
use LaravelSabre\LaravelSabre;
use Sabre\CalDAV\CalendarRoot;
use Sabre\CalDAV\ICSExportPlugin;
use Sabre\CardDAV\VCFExportPlugin;
use Illuminate\Support\Facades\App;
use Sabre\DAVACL\Plugin as AclPlugin;
use Sabre\DAVACL\PrincipalCollection;
use Illuminate\Support\ServiceProvider;
use Sabre\CalDAV\Plugin as CalDAVPlugin;
use Sabre\DAV\Auth\Plugin as AuthPlugin;
use Sabre\DAV\Sync\Plugin as SyncPlugin;
use App\Http\Controllers\DAV\DAVRedirect;
use Sabre\CardDAV\Plugin as CardDAVPlugin;
use App\Http\Controllers\DAV\Auth\AuthBackend;
use Sabre\DAV\Browser\Plugin as BrowserPlugin;
use App\Http\Controllers\DAV\DAVACL\PrincipalBackend;
use App\Http\Controllers\DAV\Backend\CalDAV\CalDAVBackend;
use App\Http\Controllers\DAV\Backend\CardDAV\CardDAVBackend;
use App\Http\Controllers\DAV\Backend\CardDAV\AddressBookRoot;

class DAVServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        LaravelSabre::nodes(function () {
            return $this->nodes();
        });
        LaravelSabre::plugins(function () {
            return $this->plugins();
        });
        LaravelSabre::auth(function (\Illuminate\Http\Request $request) : bool {
            if ($request->user()->admin ||
                config('laravelsabre.users') == null) {
                return true;
            }

            $users = explode(',', config('laravelsabre.users'));
            $filtered = Arr::where($users, function ($value, $key) use ($request) {
                return $value === $request->user()->email;
            });

            return count($filtered) > 0;
        });
    }

    /**
     * List of nodes for DAV Collection.
     */
    private function nodes() : array
    {
        // Initiate custom backends for link between Sabre and Monica
        $principalBackend = new PrincipalBackend();   // User rights
        $carddavBackend = new CardDAVBackend();       // Contacts
        $caldavBackend = new CalDAVBackend();         // Calendar

        return [
            new PrincipalCollection($principalBackend),
            new AddressBookRoot($principalBackend, $carddavBackend),
            new CalendarRoot($principalBackend, $caldavBackend),
        ];
    }

    /**
     * List of Sabre plugins.
     */
    private function plugins()
    {
        // Authentication backend
        $authBackend = new AuthBackend();
        yield new AuthPlugin($authBackend);

        // CardDAV plugin
        yield new CardDAVPlugin();
        yield new VCFExportPlugin();

        // CalDAV plugin
        yield new CalDAVPlugin();
        yield new ICSExportPlugin();

        // Sync Plugin - rfc6578
        yield new SyncPlugin();

        // ACL plugnin
        $aclPlugin = new AclPlugin();
        $aclPlugin->allowUnauthenticatedAccess = false;
        $aclPlugin->hideNodesFromListings = true;
        yield $aclPlugin;

        // In local environment add browser plugin
        if (App::environment('local')) {
            yield new BrowserPlugin(false);
        } else {
            yield new DAVRedirect();
        }
    }
}
