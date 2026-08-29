# Quickstart: Validating the sent emails endpoints

**Feature**: `specs/005-sent-emails-api` | **Date**: 2026-08-29

How to prove this feature works, end to end, once it is built. Shapes and field meanings are in
[contracts/emails-sent.md](contracts/emails-sent.md) and [data-model.md](data-model.md) rather than
repeated here.

## Prerequisites

- The application running locally (`composer run dev`, or `php artisan serve` with `bun run dev`).
- A migrated database. No migration is added by this feature.
- An account with at least one sent email. Signing in with a magic link produces one, or seed a few
  through `EmailSent::factory()`.
- An API key, minted at `/settings/account/security` or through `POST /api/v1/api-keys`.

## 1. Run the tests

The narrowest set that covers the change:

```bash
php artisan test --compact \
  tests/Feature/Controllers/Api/EmailSentControllerTest.php \
  tests/Unit/Models/EmailSentTest.php \
  tests/Unit/Enums/EmailDeliveryStatusEnumTest.php
```

The reference is guarded separately, in both directions. This is the pair that fails if a route was
added without documentation, or documented without existing:

```bash
php artisan test --compact \
  tests/Unit/ViewModels/Docs/ApiDocsViewModelTest.php \
  tests/Feature/Controllers/App/Docs/ApiDocsControllerTest.php
```

The screen must not have changed while the partial was rewired:

```bash
php artisan test --compact tests/Feature/Controllers/App/Settings/Account/Logs/EmailSentControllerTest.php
```

Then the whole suite, before the pull request:

```bash
php artisan test --compact
vendor/bin/pint --dirty --format agent
php artisan monica:localize --check
```

## 2. Call the endpoints

```bash
export TOKEN="the key you minted"
export BASE="http://127.0.0.1:8000/api/v1"

curl -s -H "Authorization: Bearer $TOKEN" -H "Accept: application/json" \
  "$BASE/emails-sent" | jq
```

**Expect**: `200`, a `data` array newest sent first, and a `meta` block carrying exactly `page`,
`per_page` and `total`. **Check that no row carries a `body` key.** That is FR-008, and it is the one
thing easiest to get wrong and hardest to notice.

```bash
curl -s -H "Authorization: Bearer $TOKEN" -H "Accept: application/json" \
  "$BASE/emails-sent/1" | jq
```

**Expect**: `200`, one `data` object, and this time `body` is present.

## 3. Walk the edge cases by hand

| Try this | Expect |
| --- | --- |
| `?per_page=9999` | `200`, `meta.per_page` is `100`. |
| `?per_page=0` and `?per_page=-5` | `200`, `meta.per_page` is `1`. |
| `?page=2` on an account with one page | `200`, empty `data`, `total` unchanged. |
| An identifier belonging to another account | `404`. |
| An identifier that exists nowhere | `404`, byte for byte the same as the row above. |
| No `Authorization` header | `401`. |
| `POST`, `PUT` or `DELETE` on either URL | `405`. No write exists. |
| `/emails-sent/abc` | `404` from the route constraint, before the controller runs. |

## 4. Check the three delivery states

Set the timestamps directly and read the value back:

```bash
php artisan tinker --execute '$e = App\Models\EmailSent::first(); $e->update(["delivered_at" => now(), "bounced_at" => null]); echo $e->fresh()->delivery_status->value;'
```

**Expect** `delivered`. Then set `bounced_at` as well and read it again: **expect** `bounced`, because
bounced wins. Clear both: **expect** `sent`.

## 5. Confirm the screen still reads the same

Open `/settings/account/logs/emails` and confirm the three badges are unchanged: "Bounced" in the
danger tone, "Delivered" in the success tone, "On its way" in the warning tone. The partial now reads
the accessor instead of computing the state inline, and nothing a person sees may have moved.

This is the only step that cannot be skipped on the grounds that the tests passed, because the
partial's three-state mapping has no test today. The task list adds those cases before the partial is
touched; if for any reason it did not, this step is the guard.

## 6. Read the reference

Open `/docs/api` and confirm a sidebar group named **Emails sent** sits under RESOURCES with two
entries, each carrying a GET badge, and that the sample responses match what step 2 actually
returned, field for field. Then `/docs/api.md` and confirm the group appears there too.

## Done when

- Every command in step 1 passes.
- Steps 2 and 3 answer as tabulated, `body` absent from list rows and present on the single email.
- All three delivery states resolve correctly, with bounced beating delivered.
- The sent emails screen renders exactly as it did before the branch.
- The reference documents both endpoints, and its samples match the real responses.
