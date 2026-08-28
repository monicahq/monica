# Quickstart: validating the API keys and logs endpoints

How to prove this feature works, from the test suite and from a live instance. It assumes the
implementation described in [plan.md](./plan.md) is in place.

## Prerequisites

- The application installed and migrated, served by Herd at the URL `config('app.url')` names.
- An account with a user, and at least one action already recorded in its journal. Signing in once
  is enough to produce one.

## 1. The suite

The narrowest run that covers the feature:

```bash
php artisan test --compact \
  tests/Feature/Controllers/Api/ApiKeyControllerTest.php \
  tests/Feature/Controllers/Api/LogControllerTest.php
```

The endpoints are not the only thing that moves, so these have to pass too. The first is the route to
reference sync test, which fails on its own the moment a route exists without documentation or the
reverse. The others cover the files this feature edits.

```bash
php artisan test --compact \
  tests/Unit/ViewModels/Docs/ApiDocsViewModelTest.php \
  tests/Feature/Controllers/App/Docs/ApiDocsControllerTest.php \
  tests/Unit/Actions/CreateApiKeyTest.php \
  tests/Unit/Actions/DestroyApiKeyTest.php \
  tests/Feature/Controllers/Api/Auth/LoginControllerTest.php \
  tests/Feature/Controllers/App/Settings/Account/Security/ApiKeyControllerTest.php
```

Then the whole suite, because `CreateApiKey` is called from three places now:

```bash
php artisan test --compact
```

## 2. The gates the project applies to every change

```bash
vendor/bin/pint --dirty --format agent
vendor/bin/phpstan analyse
php artisan monica:localize --check
```

`monica:localize` is expected to report nothing new: the reference is English copy in PHP files
rather than translated strings, and the journal sentences already exist. If it does report something,
translate it in every configured locale rather than editing `lang/*.json` by hand.

## 3. Against a live instance

Get a token, then use only that token for everything else.

```bash
BASE="$(php artisan tinker --execute 'echo rtrim(config("app.url"), "/");')/api/v1"

TOKEN=$(curl -s -X POST "$BASE/login" \
  -H "Content-Type: application/json" \
  -d '{"email":"michael.scott@dundermifflin.com","password":"the-password","device_name":"quickstart"}' \
  | php -r 'echo json_decode(stream_get_contents(STDIN), true)["token"];')
```

Mint a key, and keep the value it hands back once:

```bash
curl -s -X POST "$BASE/api-keys" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"name":"Dundie awards bot"}'
```

Expected: 201, a `data` object of type `api_key`, and a sibling `token`.

List them, read one, then revoke it:

```bash
curl -s "$BASE/api-keys" -H "Authorization: Bearer $TOKEN"
curl -s "$BASE/api-keys/1" -H "Authorization: Bearer $TOKEN"
curl -s -i -X DELETE "$BASE/api-keys/1" -H "Authorization: Bearer $TOKEN"
```

Expected: a page with no secret anywhere in it, one `api_key` object, then a 204 with an empty body.

Read the journal, which by now contains the creation and the revocation you just performed:

```bash
curl -s "$BASE/logs?per_page=5&page=1" -H "Authorization: Bearer $TOKEN"
curl -s "$BASE/logs/1" -H "Authorization: Bearer $TOKEN"
```

Expected: five entries newest first with `page`, `per_page` and `total` in `meta`, and one entry
carrying an `author`, an `action`, a translated `description`, a `source` and an `ip_address`.

## 4. The checks worth doing by hand

| What to try | Expected |
| --- | --- |
| `POST /api-keys` with `{"name":"ab"}` | 422, `errors.name` present, no key minted. |
| `GET /api-keys/{id}` with the id of a key belonging to another account | 404, and that key still works. |
| `GET /logs/{id}` with the id of an entry from another account | 404. |
| `GET /logs?per_page=100000` | 200, and `meta.per_page` reads 100. |
| `GET /logs?per_page=0` | 200, and `meta.per_page` reads 1. |
| `GET /logs?per_page=1&page=2` | 200, one entry, and `meta.page` reads 2. |
| Revoke the key you are calling with, then call anything | 204, then 401. |
| Any of the six with no `Authorization` header | 401. |
| Set the account language to French, then read the journal | The `description` comes back in French. |
| `POST /logs`, `PUT /logs/1`, `DELETE /logs/1` | 405, because no such route is registered. |

## 5. The reference

Open `/docs/api` and confirm the sidebar shows an API keys group of four entries (GET, GET, POST,
DELETE) and a Logs group of two (GET, GET), in that order, directly after the profile. Then confirm
the markdown views answer, since they are built from the same definitions:

```bash
curl -s "$(php artisan tinker --execute 'echo rtrim(config("app.url"), "/");')/docs/api/api-keys-index.md"
```

Copy a sample out of any of the six sections, change the host and the token, and it should run
against the live instance unchanged. That is SC-005, and it is the one thing no test asserts.
