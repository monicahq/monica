# Phase 1 data model: API foundations and the API docs portal

This feature adds **no migration and no table**. Everything below is either a record that already
exists and is now reachable from outside the browser, or an in-memory structure the reference builder
assembles from PHP files on disk.

## Persisted entities (existing, unchanged)

### User

The person a token acts as. One per account.

Exposed by `GET /api/v1/me` and written by `PUT /api/v1/me`, through `app/Http/Resources/UserResource.php`.

| Attribute | Type | Exposed | Writable | Notes |
|---|---|---|---|---|
| `id` | int | yes, cast to string | no | The envelope's `id`, always a string. |
| `first_name` | string | yes | yes | Maximum 100 characters. |
| `last_name` | string | yes | yes | Maximum 100 characters. |
| `email` | string | yes | yes | Lowercased, unique, not a disposable provider. |
| `email_verified_at` | timestamp, nullable | yes, as `email_verified` boolean | no | Cleared by `UpdateUserProfile` when the address changes. |
| `locale` | string, nullable | yes | no | Preferences are updated by `UpdatePreferences`, out of scope. |
| `timezone` | string | yes | no | As above. |
| `time_format` | enum | yes, as its value | no | As above. |
| `date_format` | enum, nullable | yes, as its value | no | As above. |
| `name_order` | enum | yes, as its value | no | As above. |
| `number_format` | enum, nullable | yes, as its value | no | As above. |
| `is_active` | bool | no | no | A deactivated user cannot obtain a token at all. |
| `two_factor_confirmed_at` | timestamp, nullable | no | no | Read during login to decide whether a code is required. |
| `password`, `two_factor_secret`, `two_factor_recovery_codes`, `last_login_ip` | | never | never | Credentials and traces. Not in any response. |

Timestamps in the envelope are Unix integers, matching the convention the `api-writer` skill fixes.

**Validation on `PUT /me`**: `first_name` and `last_name` required strings up to 100 characters;
`email` required, lowercase, a valid address, up to 255 characters, unique ignoring the current user,
and refused by the disposable address rule. These are the profile screen's rules, applied by the same
action.

### Account

The compartment. Every authenticated call reads and writes inside the account of the token holder,
and nothing in this feature reaches across one. Not exposed as a resource yet.

Created by `POST /api/v1/register` through `CreateAccount`, which also creates the first vault, the
default value lists and the default template. Registering through the API produces exactly the account
the sign-up screen produces.

### Personal access token

Sanctum's `personal_access_tokens` row. Minted by `CreateApiKey`, revoked by `DestroyApiKey`, both of
which log to the activity log.

| Attribute | Notes |
|---|---|
| `name` | Taken from `device_name` on register and login. Falls back to a fixed wording when absent. |
| `token` | Only a hash is stored. The plain text value is returned once, at creation, and is unrecoverable afterwards. |
| `abilities` | `["*"]` today. Scoping is the open item recorded in the plan's Complexity Tracking. |
| `expires_at` | Always null. Reported in the response body so a client that reads it keeps working when expiry arrives. |

**Lifecycle**: created by register and login, revoked one at a time by logout or from the security
screen. Revoking one leaves the holder's other tokens working, which is the whole point of naming them
per device.

## In-memory structures (new)

These are built by `ApiDocsViewModel` from `resources/docs/api/*.php` on each request. They are not
persisted and never touch the database.

### Documentation group

One file, one sidebar heading. Files load in filename order, and the numeric prefix is what sets that
order.

| Field | Type | Notes |
|---|---|---|
| `name` | string | The sidebar heading, for example `Your profile`. |
| `guide` | bool | Defaults to false. True renders the group under Getting started as plain links with no verb, and excludes its sections from the route synchronisation test. |
| `sections` | list of Documentation section | In the order they are declared. |

### Documentation section

One entry in the sidebar and one endpoint panel. Modelled by `ApiDocsSection`.

| Field | Type | Required | Notes |
|---|---|---|---|
| `id` | string | yes | Kebab-case, unique across every group. The URL segment of the endpoint's own page, `/docs/api/{section}`, and of its markdown at `/docs/api/{section}.md`. |
| `title` | string | yes | The heading above the prose column. |
| `label` | string | no | The sidebar wording. Defaults to `title`. |
| `method` | string | yes | One of GET, POST, PUT, PATCH, DELETE. Decides the badge colour. |
| `path` | string | yes | The route path, with `{parameters}` written exactly as `routes/api.php` writes them. Compared against the real routes. |
| `examplePath` | string | no | The path used in the samples when the real one carries a parameter. Defaults to `path`. |
| `auth` | bool | no | Defaults to true. False drops the Authorization header from every sample. |
| `description` | string | yes | The first paragraph. |
| `body` | list of string | no | Further paragraphs. |
| `paramsTitle` | string | no | Defaults to `Parameters`. The design uses `Body parameters` and `Query parameters`. |
| `pathParams`, `queryParams`, `bodyParams` | list of Parameter | no | Rendered as one list under `paramsTitle`. |
| `rateLimit` | string | yes | The sentence in the rate limit box, for example `Six requests per minute, per IP.` |
| `returns` | string | no | One sentence describing the response. |
| `responseStatus` | int | no | Defaults to 200. |
| `response` | array | no | The body as a PHP array. Omitted for 204. |
| `exampleBody` | array | no | Overrides the request body the samples build from the parameter examples. |

**Derived at build time**, not written in the definition files: `samples` (cURL, JavaScript and PHP,
each as highlighted lines), `responseLines` (the response pretty printed, highlighted, split), and
`markdown` (the section as one markdown document, used by Copy for LLM and by
`/docs/api/{section}.md`).

### Parameter

| Field | Type | Required | Notes |
|---|---|---|---|
| `name` | string | yes | |
| `type` | string | yes | `string`, `integer`, `boolean`, `date`. |
| `required` | bool | yes | Rendered as the words `required` or `optional`, in colour and in text. |
| `description` | string | yes | |
| `default` | string | no | |
| `example` | mixed | no | Parameters carrying an example become the request body in the samples. |

## Relationships

```
Account 1 --- 1 User 1 --- * PersonalAccessToken

DocumentationGroup 1 --- * DocumentationSection 1 --- * Parameter
DocumentationSection.path + .method  ===  a route in routes/api.php   (enforced both ways, guides excluded)
```

## Invariants

- A token acts as exactly one user, with exactly the access that user has.
- Revoking one token never affects another.
- A section `id` is unique across every group, because it is the URL segment naming that endpoint's
  page. An address naming no section is a 404 rather than an arbitrary page.
- Every non-guide section corresponds to exactly one registered route, and every registered route
  corresponds to exactly one non-guide section. The suite fails otherwise.
- No response in this feature contains a password, a token hash, a two-factor secret or a recovery
  code.
