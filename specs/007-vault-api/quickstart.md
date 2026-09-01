# Quickstart: Validating the vault endpoints

**Feature**: `specs/007-vault-api` | **Date**: 2026-09-01

How to prove this feature works, end to end, once it is built. Shapes and field meanings are in
[contracts/vaults.md](contracts/vaults.md) and [data-model.md](data-model.md) rather than repeated
here.

## Prerequisites

- The application running locally (`composer run dev`, or `php artisan serve` with `bun run dev`).
- A migrated database. No migration is added by this feature.
- An account. It does not need a vault: the first call below makes one, and an account with none is a
  case worth exercising.
- An API key, minted at `/settings/account/security` or through `POST /api/v1/api-keys`.

## 1. Run the tests

The narrowest set that covers the change:

```bash
php artisan test --compact tests/Feature/Controllers/Api/VaultControllerTest.php
```

The operations underneath are untouched, so their tests are the proof that they stayed untouched:

```bash
php artisan test --compact \
  tests/Unit/Actions/CreateVaultTest.php \
  tests/Unit/Actions/UpdateVaultTest.php \
  tests/Unit/Actions/DestroyVaultTest.php \
  tests/Unit/Actions/SetDefaultVaultTest.php \
  tests/Unit/Actions/SetVaultDefaultTemplateTest.php
```

The reference is guarded in both directions. This is the pair that fails if a route was added without
documentation, or documented without existing:

```bash
php artisan test --compact \
  tests/Unit/ViewModels/Docs/ApiDocsViewModelTest.php \
  tests/Feature/Controllers/App/Docs/ApiDocsControllerTest.php
```

The web screens must be unaffected, since nothing they use was changed:

```bash
php artisan test --compact tests/Feature/Controllers/App/Vaults/VaultControllerTest.php
```

Then the gates, before the pull request:

```bash
php artisan test --compact
vendor/bin/pint --dirty --format agent
vendor/bin/phpstan analyse
php artisan monica:localize --check
```

## 2. Open a vault, and keep its address

```bash
export TOKEN="the key you minted"
export BASE="http://127.0.0.1:8000/api/v1"

VAULT=$(curl -s -X POST -H "Authorization: Bearer $TOKEN" -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"name":"Dunder Mifflin","description":"Everybody at the Scranton branch.","colour":"#4f8a49"}' \
  "$BASE/vaults" | jq -r '.data.id')

echo "$VAULT"
```

**Expect** 201, a uuid rather than a number, `position` at the end of the account's list, and
`is_default` true if this was the account's first vault. Confirm the uuid is what `links.self` ends
with.

## 3. Read it back, both ways

```bash
curl -s -H "Authorization: Bearer $TOKEN" -H "Accept: application/json" "$BASE/vaults" | jq
curl -s -H "Authorization: Bearer $TOKEN" -H "Accept: application/json" "$BASE/vaults/$VAULT" | jq
```

**Expect** the list in the order the account arranged (by `position`, not newest first), the same
object in both answers, and `meta` carrying exactly `page`, `per_page` and `total`. Ask for
`?per_page=999` and confirm `per_page` comes back as 100 rather than the request being refused.

## 4. Change the wording, and confirm nothing is lost quietly

```bash
curl -s -X PUT -H "Authorization: Bearer $TOKEN" -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"name":"Scranton","description":null,"colour":"#9a3f2b"}' \
  "$BASE/vaults/$VAULT" | jq
```

**Expect** 200, the new name, `description` null, the new colour, and `position`, `is_default` and
`contact_count` untouched.

Now the case that matters. Send only the name:

```bash
curl -s -X PUT -H "Authorization: Bearer $TOKEN" -H "Accept: application/json" \
  -H "Content-Type: application/json" -d '{"name":"Scranton"}' \
  "$BASE/vaults/$VAULT" | jq
```

**Expect** 422 naming both `description` and `colour`, and the vault unchanged. A 200 here means the
change endpoint is quietly resetting fields, which is the one trap this design exists to close.

## 5. Confirm the compartment

Mint a key on a second account and try the first account's vault with it:

```bash
curl -s -o /dev/null -w '%{http_code}\n' -H "Authorization: Bearer $OTHER_TOKEN" \
  -H "Accept: application/json" "$BASE/vaults/$VAULT"
```

**Expect** `404`, identical to the answer for a uuid that never existed:

```bash
curl -s -o /dev/null -w '%{http_code}\n' -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" "$BASE/vaults/00000000-0000-0000-0000-000000000000"
```

Also try a numeric address, which the API does not accept:

```bash
curl -s -o /dev/null -w '%{http_code}\n' -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" "$BASE/vaults/1"
```

**Expect** `404` for all three, and `401` with no credential at all.

## 6. Confirm the two account level decisions

With two vaults in the account, promote the second and check the first gave the title up:

```bash
curl -s -X PUT -H "Authorization: Bearer $TOKEN" -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"name":"Scranton","description":null,"colour":"#9a3f2b","is_default":true}' \
  "$BASE/vaults/$VAULT" | jq '.data.attributes.is_default'

curl -s -H "Authorization: Bearer $TOKEN" -H "Accept: application/json" "$BASE/vaults" \
  | jq '[.data[] | select(.attributes.is_default)] | length'
```

**Expect** `true`, then `1`. Any other number means the invariant broke.

Then send `"is_default": false` and confirm the count is still 1: the title is never left unheld.

Naming a structure new contacts start from is checked in the database rather than in the answer, since
the vault object does not carry it:

```bash
php artisan tinker --execute 'dump(App\Models\VaultDefaultTemplate::query()->latest("id")->first()?->toArray());'
```

## 7. Delete it, and see what went with it

Create a vault, put a contact in it through the web app, take a share link on that contact, then:

```bash
curl -s -o /dev/null -w '%{http_code}\n' -X DELETE -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" "$BASE/vaults/$VAULT"
```

**Expect** `204` with no body. Then confirm:

- the vault is gone from `GET /vaults`, and asking for it by uuid answers 404;
- the contact went with it, marked as having left with the vault;
- the share link no longer opens;
- exactly one vault is the default, or none if the account now holds no vault;
- deleting the same uuid a second time answers 404 rather than reporting a second deletion.

## 8. Read the reference

Open `/docs/api` and confirm a **Vaults** group in the sidebar holding five entries with the right verb
beside each, that the example response for each endpoint matches what the calls above actually
returned, that the eight colours are listed, and that the delete page says what leaves with the vault
and that nothing brings it back. Then `/docs/api.md` and `/docs/api/vaults-index.md` for the plain text
rendering.

## What proves the feature is done

- The five endpoints answer as [contracts/vaults.md](contracts/vaults.md) says, including every 404
  and 422 case above.
- The account journal shows the same entries for an API write as for the same write made in a browser.
- An account always has exactly one default vault while it holds a vault, and none while it holds none,
  whichever order the endpoints were called in.
- No screen changed, no migration ran, and `monica:localize --check` reports nothing missing.
