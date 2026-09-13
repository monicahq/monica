# Quickstart: relationship category and type endpoints

Proves the feature end to end against a running instance. Addresses and bodies are in
[contracts/](contracts/) and are not repeated here.

## Prerequisites

Install, migrate, register through the interface, then make a key at Settings, API keys.

```bash
export TOKEN=... BASE=http://localhost:8000/api/v1
export H="Authorization: Bearer $TOKEN"
```

A new account is seeded with five categories and ten types, so there is nothing to set up first.

## 1. The supplied vocabulary reads

```bash
curl -s -H "$H" "$BASE/relationship-categories" | jq '.data[].attributes.name, .meta.total'
curl -s -H "$H" "$BASE/relationship-types" | jq '.data[].attributes | {name, role_a}'
```

Expect five categories in position order, ten types, and `Parent / Child` carrying
`system_behavior: "genealogy_parentage"`. Ask again with `Accept-Language: fr`: the supplied names
read in French, which is what proves the translation key path.

## 2. A vocabulary of your own

Add a category, add a directed type under it, reword the type, then move it, feeding each answer's
`id` to the next call. Expect 201, 201, 200, 200, the type placed last in its category, and every
position renumbered from 1 after the move.

## 3. The refusals, which are the point

Each of these must answer without changing anything:

- Rename a supplied category. **422.**
- Delete a category that still holds a type. **422**, naming how many.
- Add a second type with a name the account already carries. **422.**
- Send `is_symmetric` against `Parent / Child`. **422**, because it is supplied.
- Delete `Parent / Child`. **422**, because it is supplied.
- Write a relationship on your own type, then delete the type. **422**, in use.
- Any address above with another account's uuid. **404**, never 403.

## 4. Retiring, which is the answer deletion is refused with

```bash
curl -s -X PUT -H "$H" -H 'Content-Type: application/json' \
  -d '{"is_active": false}' "$BASE/relationship-types/$UUID/activation" | jq '.data.attributes.is_active'
```

Send it twice and the answer stays `false`. Open the relationship form: the type is gone from the
picker and every relationship written with it still reads. Retire its category instead and the whole
group leaves the picker, which no screen can do today.

## 5. The suite

```bash
php artisan test --compact --filter=Relationship
php artisan test --compact tests/Unit/ViewModels/Docs/ApiDocsViewModelTest.php
vendor/bin/pint --dirty --format agent && vendor/bin/phpstan analyse
php artisan monica:localize --check
```

The docs test fails if a route shipped without a reference entry, or the reverse.
