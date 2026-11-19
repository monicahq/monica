<?php

use Illuminate\Support\Str;

/*
|--------------------------------------------------------------------------
| OpenID Connect providers
|--------------------------------------------------------------------------
|
| Comma separated list of OpenID Connect provider ids. Each id is enabled
| as a login provider named "oidc_{id}" and is configured with environment
| variables prefixed by OIDC_{ID}_, for instance with OIDC_PROVIDERS=work:
|
| OIDC_WORK_NAME           Label displayed on the login button.
| OIDC_WORK_BASE_URL       Issuer URL, used for discovery.
| OIDC_WORK_CLIENT_ID      Client id.
| OIDC_WORK_CLIENT_SECRET  Client secret.
| OIDC_WORK_REDIRECT_URI   Defaults to /auth/oidc_work/callback.
| OIDC_WORK_LOGO           Defaults to /img/auth/openid.svg.
| OIDC_WORK_SCOPES         Defaults to "openid email profile".
|
| This uses the socialiteproviders/openidconnect package.
|
*/

$connections = collect(explode(',', (string) env('OIDC_PROVIDERS', '')))
    ->map(fn (string $id) => Str::lower(trim($id)))
    ->filter()
    ->unique()
    ->mapWithKeys(function (string $id) {
        $prefix = 'OIDC_'.Str::of($id)->upper()->replaceMatches('/[^A-Z0-9]+/', '_').'_';

        return [
            $id => array_filter([
                'name' => env($prefix.'NAME', Str::headline($id)),
                'logo' => env($prefix.'LOGO', '/img/auth/openid.svg'),
                'base_url' => env($prefix.'BASE_URL'),
                'client_id' => env($prefix.'CLIENT_ID'),
                'client_secret' => env($prefix.'CLIENT_SECRET'),
                'redirect' => env($prefix.'REDIRECT_URI', "/auth/oidc_{$id}/callback"),
                'scopes' => env($prefix.'SCOPES'),
            ], fn ($value) => $value !== null && $value !== ''),
        ];
    });

return [

    'driver_prefix' => 'oidc_',

    'connections' => $connections->all(),

];
