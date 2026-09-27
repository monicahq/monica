---
name: passport-development
description: "Develops OAuth2 API authentication with Laravel Passport. Activates when installing or configuring Passport; setting up OAuth2 grants (authorization code, client credentials, personal access tokens, device authorization); managing OAuth clients; protecting API routes with token authentication; defining or checking token scopes; configuring SPA cookie authentication; handling token lifetimes and refresh tokens; or when the user mentions Passport, OAuth2, API tokens, bearer tokens, or API authentication. Make sure to use this skill whenever the user works with OAuth2, API tokens, or third-party API access, even if they don't explicitly mention Passport."
license: MIT
metadata:
  author: laravel
---

# Passport OAuth2 Authentication

## Documentation First

**Always use `search-docs` before writing Passport code.** The documentation covers every grant type, configuration option, and edge case in detail. This skill teaches you how to navigate Passport — the docs have the implementation specifics.

```
search-docs(queries: ["Passport installation"], packages: ["laravel/framework@12.x"])
```

The Passport docs live under the `laravel/framework` package — not `laravel/passport`.

## When to Apply

Activate this skill when:

- Installing or configuring Passport
- Setting up OAuth2 authorization grants
- Creating or managing OAuth clients
- Protecting API routes with token authentication
- Defining or checking token scopes
- Configuring SPA cookie-based authentication
- Choosing between Passport and Sanctum

## Passport vs. Sanctum

**Passport** is a full OAuth2 server — use it when third-party applications need to consume your API and when you need OAuth2 authorization code grants, client credentials for machine-to-machine auth, or device authorization flow.

**Sanctum** is simpler — use it when first-party SPAs, third parties, or mobile apps consume the API but you don't need the full OAuth2 grant flows.

## Installation

Three steps are always required:

### 1. Install Passport

```bash
php artisan install:api --passport
```

This publishes migrations, generates encryption keys, and registers routes.

### 2. Configure the User model

The User model needs both the `HasApiTokens` trait AND the `OAuthenticatable` interface. Missing the interface is the most common Passport setup mistake — it causes runtime errors that can be confusing to debug.

```php
use Laravel\Passport\Contracts\OAuthenticatable;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable implements OAuthenticatable
{
    use HasApiTokens;
}
```

### 3. Configure the auth guard

The `api` guard must use the `passport` driver in `config/auth.php`. Using `token` or `sanctum` here silently breaks Passport authentication.

```php
'guards' => [
    'api' => [
        'driver' => 'passport',
        'provider' => 'users',
    ],
],
```

## Choosing a Grant Type

Matching the right grant to the use case is the most important Passport decision. Use `search-docs` for implementation details of any grant.

| Use Case | Grant Type | Client Flag |
|----------|-----------|-------------|
| Third-party app accessing user data | Authorization Code | (default) |
| Mobile/SPA without client secret | Authorization Code + PKCE | `--public` |
| Machine-to-machine, no user context | Client Credentials | `--client` |
| User-generated API keys | Personal Access Tokens | `--personal` |
| Smart TV, CLI, IoT devices | Device Authorization | `--device` |

**Legacy grants** (Password, Implicit) are disabled by default and not recommended. They must be explicitly enabled with `Passport::enablePasswordGrant()` or `Passport::enableImplicitGrant()`.

## Client Management

Create clients with the appropriate flag for the grant type:

```bash
php artisan passport:client              # Authorization code
php artisan passport:client --public     # PKCE (no secret)
php artisan passport:client --client     # Client credentials
php artisan passport:client --personal   # Personal access tokens
php artisan passport:client --device     # Device authorization
```

Additional flags: `--name=`, `--redirect_uri=`, `--provider=`.

Client secrets are hashed by default — the plain-text secret is only shown at creation time and cannot be retrieved later.

## Protecting Routes

Apply `auth:api` middleware. Clients send tokens via the `Authorization: Bearer <token>` header.

```php
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:api');
```

### Scope Enforcement

Scope middleware must come alongside `auth:api`:

- `CheckToken::using('scope1', 'scope2')` — requires ALL listed scopes
- `CheckTokenForAnyScope::using('scope1', 'scope2')` — requires ANY listed scope
- `EnsureClientIsResourceOwner::using('scope1')` — restricts to client credential tokens

```php
use Laravel\Passport\Http\Middleware\CheckToken;

Route::get('/orders', function () {
    // ...
})->middleware(['auth:api', CheckToken::using('orders:read')]);
```

### Programmatic scope checking

```php
if ($request->user()->tokenCan('place-orders')) {
    // ...
}
```

Use `search-docs` for full scope middleware registration and usage patterns.

## Key Configuration

Configure in `AppServiceProvider::boot()`. Use `search-docs` for the full list of options.

```php
// Token lifetimes (each is independent)
Passport::tokensExpireIn(now()->addDays(15));
Passport::refreshTokensExpireIn(now()->addDays(30));
Passport::personalAccessTokensExpireIn(now()->addMonths(6));

// Define scopes
Passport::tokensCan([
    'place-orders' => 'Place orders',
    'check-status' => 'Check order status',
]);
```

## SPA Cookie Authentication

For first-party SPAs, the `CreateFreshApiToken` middleware issues a `laravel_token` cookie containing an encrypted JWT. The SPA must include CSRF tokens — missing the `X-CSRF-TOKEN` or `X-XSRF-TOKEN` header causes 419 errors.

Use `search-docs` for setup details — this feature has specific CSRF and cookie configuration requirements.

## Testing

Passport provides helpers to bypass full OAuth flows in tests:

```php
Passport::actingAs($user, ['scope1', 'scope2']);
Passport::actingAsClient($client, ['scope1']);
```

## Token Maintenance

```bash
php artisan passport:purge              # Purge revoked & expired
php artisan passport:purge --revoked    # Only revoked
php artisan passport:purge --expired    # Only expired
```

Schedule `passport:purge` for regular expired token clean-up.

## Events

All in `Laravel\Passport\Events`: `AccessTokenCreated`, `AccessTokenRevoked`, `RefreshTokenCreated`.

## Common Pitfalls

- **Missing `OAuthenticatable` interface** — both the `HasApiTokens` trait and the `OAuthenticatable` interface are required on the User model. Missing the interface causes runtime errors.
- **Wrong guard driver** — the `api` guard must use `passport`, not `token` or `sanctum`. This fails silently.
- **Token lifetime confusion** — access token, refresh token, and personal access token lifetimes are all independent settings.
- **Missing CSRF for SPA cookie auth** — `CreateFreshApiToken` requires CSRF tokens. Use `Passport::ignoreCsrfToken()` only if you understand the security implications.
- **Client secrets are hashed** — the plain-text secret is only available at creation time.
- **Legacy grants are disabled** — Password and Implicit grants must be explicitly enabled and are not recommended.
