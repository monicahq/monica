# Research: MCP Server

Eight questions the plan had to answer before it could be written. Everything here was checked
against `laravel/mcp` v1.0.0 (released 2026-08-13) and against the shipped code, not inferred.

## 1. The dependency

`laravel/mcp` v1.0.0, Laravel's own package, which the user approved on 2026-09-14. It requires
`illuminate/* ^13.0` and this project runs `laravel/framework ^13.8`, so nothing else moves. It is a
required dependency, not a suggested one: the constitution's suggest-rule governs **outbound**
channels reaching a third-party service, and this is an inbound endpoint the product serves itself.

**Rejected**: writing the protocol by hand, and `php-mcp/laravel`. The first is a maintenance
liability for a moving specification; the second is not first party.

## 2. Where the code lives, and the amendment it needs

The package generates `App\Mcp\Servers` and `App\Mcp\Tools`. That is a new top-level folder under
`app/`, and the constitution's Technology and Architecture Constraints say plainly that one
**requires an amendment**. There is no placement that avoids this: `app/Http` enumerates its five
children just as explicitly, and a tool is not a controller, so smuggling it into
`app/Http/Controllers/Mcp` trades a named amendment for an unnamed one.

**Decision**: take the package's convention, `app/Mcp/{Servers,Tools}`, and ship the amendment with
the feature. Fighting a generator to land in a folder that is equally unlisted buys nothing.

## 3. Where the route is registered

The package's `McpServiceProvider::registerRoutes()` loads `base_path('routes/ai.php')` **only if the
file exists**, and returns silently otherwise. So `routes/ai.php` is optional, and `Mcp::web()` works
from any route file. It is registered in `routes/api.php`, which the constitution already names, so
no second question about the route layout arises.

Registering it there is not merely tidy. The api group prepends `SetApiLocale`, which resolves a
request's locale through `LocaleResolution::forApi()`, and that is exactly what FR-011 asks for.

`Mcp::web()` returns an `Illuminate\Routing\Route`, so `auth:sanctum` and the throttle chain onto it
the way they do anywhere else.

## 4. How a capability mirrors an API capability

FR-003 says each capability is served by the same logic as the API's matching capability. Each tool
runs the same action the `Api` controller runs, and declares the route name it mirrors. SC-003's test
reads the server's tool list, asks each for that name and fails when `Route::has()` says no.

**Rejected**: dispatching an internal HTTP request per tool call. It would inherit validation and
shaping for free, but it fabricates a request, doubles the middleware stack, and buries the failure
mode where no test looks. The constitution already calls controllers transport, which makes MCP a
third transport rather than a client of the second.

## 5. How the catalogue narrows to the key

`Primitive::eligibleForRegistration()` calls a tool's optional `shouldRegister()` through the
container, so it can type-hint the request and read the presented key. That is FR-004's mechanism.

What it has to read is thin today: `ApiKeyScopeEnum` holds one case, `sensitive-fields`, and there is
no read/write split, so every tool registers for every live key until the key work lands. This is the
shape clarification 1 chose, and `shouldRegister()` is where scoping arrives with no other change.

## 6. How the changelog learns it was MCP

`LogUserAction` already reads `request()->attributes->get('log_source', LogSourceEnum::Web)` and
`log_source_detail`, and `AuthenticateWithApiKeyOnBasicAuth` already sets both for CardDAV.
`LogSourceEnum::Mcp` exists and nothing writes it.

**Decision**: one middleware on the MCP route sets `log_source` to `Mcp` and `log_source_detail` to
the key's name. FR-008 then holds for all thirteen capabilities without touching a single action.

## 7. The rate limit

FR-010 wants a per-key limit on hosted and none self hosted. `config('monica.self_hosted')` already
exists. A named `mcp` limiter returns `Limit::none()` when it is true and a per-key limit otherwise.
The number is configuration, which `spec/models/006` says explicitly, so it is not fixed here.

## 8. The Me contact, and a divergence worth stating

FR-013 adds the API capability that FR-006 needs. Where it hangs is not obvious: the constitution
says "every account has exactly one Me contact", while `app/Models/Contact.php` documents one per
**vault** and `CreateContact` writes it that way.

**Decision**: follow the code. The endpoint is `GET vaults/{vault}/contacts/me` and the tool takes a
vault. The divergence is real and the constitution's Governance requires the pull request to say
which of the two is wrong rather than leave both standing; it is named here so that it cannot be
missed, and settling it is not this feature's job.
