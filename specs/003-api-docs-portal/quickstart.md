# Quickstart: validating the API and the docs portal

How to prove this feature works end to end, from a clean checkout. Nothing here is implementation
code. The shapes it refers to are in [contracts/openapi.yaml](contracts/openapi.yaml) and
[data-model.md](data-model.md).

## Prerequisites

```bash
git checkout 2026-08-27-api-doc-portal
composer install
bun install && bun run build
cp -n .env.example .env && php artisan key:generate
php artisan migrate:fresh --seed
```

Then start it, in two terminals or with the one command that runs both:

```bash
composer run dev          # serve, queue, vite
```

The portal reads PHP files from disk on every request, so a change to `resources/docs/api/*.php`
shows up on refresh with nothing to rebuild. A change to a Blade file or to `app.css` needs Vite,
which `composer run dev` is already watching.

## 1. The reference renders

Open `http://localhost:8000/docs/api` while signed out.

Expected:

- It leads to `/docs/api/introduction` and renders with no sign-in prompt.
- The sidebar shows **Getting started** with Introduction, Authentication, Rate limits, Pagination
  and Errors as plain links, then **Resources** with Health, Authentication and Your profile, each
  entry prefixed by its verb.
- Selecting an entry opens that endpoint at an address of its own, such as `/docs/api/me-show`.
  Reloading it, opening it in a new tab, and the browser's back button all behave the way they do on
  any other page. An address naming no endpoint, such as `/docs/api/nope`, answers 404.
- The code panel switches between cURL, JavaScript and PHP, and Copy puts the visible sample on the
  clipboard.
- **Get an API key** goes to the security settings screen.

Then the two conditions Principle IX makes non-negotiable:

- Tab through the page from the top. Every sidebar entry, every language tab and every copy button is
  reachable, and focus is visible on each.
- Switch the theme with the toggle, and narrow the window to 375 pixels. The page stays readable and
  never scrolls sideways; the code panels scroll inside themselves.

## 2. The reference is readable as text

```bash
curl -s http://localhost:8000/docs/api.md | head -40
curl -s http://localhost:8000/docs/api/me-show.md
```

Expected: markdown, not HTML, with the same endpoints the page shows. The second returns that one
section.

## 3. The six calls, against a real account

Everything below assumes `BASE=http://localhost:8000/api/v1`.

**Check status**, no token:

```bash
curl -s $BASE/status
```

Expected: 200 and `{"status":"ok","version":"…","instance":"…"}`.

**Register**, and keep the token:

```bash
TOKEN=$(curl -s $BASE/register \
  -H 'Content-Type: application/json' \
  -d '{"first_name":"Monica","last_name":"Geller","email":"monica@example.com",
       "password":"a-very-strong-password","password_confirmation":"a-very-strong-password",
       "device_name":"Quickstart"}' | php -r 'echo json_decode(stream_get_contents(STDIN))->token;')
echo $TOKEN
```

Expected: 201 and a token. Then confirm the account was really built the way the sign-up screen builds
it, which is the point of reusing `CreateAccount`:

```bash
php artisan tinker --execute 'echo App\Models\User::query()->where("email","monica@example.com")->first()->account->vaults()->count();'
```

Expected: `1`. The first vault exists. The default template arrives on the queue, so it needs the
worker `composer run dev` is already running.

**Get your profile**:

```bash
curl -s $BASE/me -H "Authorization: Bearer $TOKEN"
```

Expected: 200 and `data.type` of `user`, `data.id` a string, `data.attributes.email` the address you
registered with, and no password, token hash, two-factor secret or recovery code anywhere in the body.

**Update your profile**:

```bash
curl -s -X PUT $BASE/me -H "Authorization: Bearer $TOKEN" \
  -H 'Content-Type: application/json' \
  -d '{"first_name":"Monica","last_name":"Bing","email":"monica.bing@example.com"}'
```

Expected: 200 and the updated user. Then confirm the consequence the profile screen also has:

```bash
php artisan tinker --execute 'echo var_export(App\Models\User::query()->where("email","monica.bing@example.com")->first()->email_verified_at, true);'
```

Expected: `NULL`. Changing the address cleared its confirmation.

**Log in**, getting a second token:

```bash
curl -s $BASE/login -H 'Content-Type: application/json' \
  -d '{"email":"monica.bing@example.com","password":"a-very-strong-password","device_name":"Second"}'
```

Expected: 200 and a different token.

**Log out**, and prove the revocation is surgical:

```bash
curl -s -o /dev/null -w '%{http_code}\n' -X DELETE $BASE/logout -H "Authorization: Bearer $TOKEN"
curl -s -o /dev/null -w '%{http_code}\n' $BASE/me -H "Authorization: Bearer $TOKEN"
```

Expected: `204` then `401`. The second token from the login above still returns 200, because logging
out revokes one token and not the rest.

## 4. The refusals

| Check | Command shape | Expected |
|---|---|---|
| No token | `GET /me` with no header | 401 |
| Revoked token | `GET /me` with the token from step 3 | 401 |
| Wrong password | `POST /login` with a bad password | 401, and the same message as an address nobody holds |
| Unknown address | `POST /login` with an unused address | 401, message identical to the line above |
| Deactivated user | set `is_active` to false, then `POST /login` | 401, same message again |
| Duplicate address | `POST /register` with an address already used | 422, `errors.email` |
| Disposable address | `POST /register` with a throwaway provider | 422, `errors.email` |
| Weak password | `POST /register` with `password` of `12345678` | 422, `errors.password` |
| Missing fields | `PUT /me` with `{}` | 422, naming `first_name`, `last_name` and `email` at once |
| Rate limit | `POST /login` seven times in a minute | the seventh returns 429 with `Retry-After` |

The three identical 401 messages are the point, not an oversight. Anything else tells an attacker
which addresses have accounts.

Two-factor cannot be driven from curl alone. Switch it on for the user in the interface, then confirm
`POST /login` without `code` returns 401 and issues no token. That case is covered by
`LoginControllerTest` and does not need to be done by hand.

## 5. The suite

```bash
php artisan test --compact tests/Feature/Controllers/Api tests/Feature/Controllers/App/Docs tests/Unit/ViewModels/Docs
```

Then the two gates that catch what a manual pass cannot:

```bash
php artisan test --compact --filter=it_documents_every_registered_route
php artisan test --compact --filter=it_registers_every_documented_route
```

These fail if the reference and `routes/api.php` disagree in either direction, which is what makes
FR-019 real. Add an endpoint without documenting it, or document one that does not exist, and the
suite goes red on the pull request that did it.

Before the change is done, per the constitution's definition of done:

```bash
vendor/bin/pint --format agent
vendor/bin/phpstan analyse
php artisan monica:localize --check
php artisan test --compact
```

`monica:localize` should report nothing new. The portal ships in English by decision, so it adds no
keys, and no other screen changed.
