# Quickstart: activity category endpoints

Proves the feature end to end against a running instance. Addresses and bodies are in
[contracts/](contracts/) and are not repeated here.

## Prerequisites

The table is new, so rebuild with `php artisan migrate:fresh` rather than patch: an edited or added
`Schema::create` never reaches a database that already ran it. Register through the interface, which
is what seeds the eight categories, then make a key at Settings, API keys.

```bash
export TOKEN=... BASE=http://localhost:8000/api/v1
export H="Authorization: Bearer $TOKEN"
```

## 1. The eight come with the account

```bash
curl -s -H "$H" "$BASE/activity-categories" | jq '.data[].attributes | {name, position, is_system, activity_type_count}'
```

Expect eight, positions 1 to 8 in the order of [data-model.md](data-model.md), every one
`is_system: true` and `activity_type_count: 0`. Ask again with `Accept-Language: fr` and the names
read in French, which is what proves the translation key path.

## 2. A group of your own

Add one, rename it, move it to the front. Expect 201 then 200 then 200, the new category placed
ninth and not supplied, and every position renumbered from 1 after the move.

## 3. Renaming a supplied group, which is allowed here

```bash
curl -s -X PUT -H "$H" -H 'Content-Type: application/json' \
  -d '{"name":"Famille et proches"}' "$BASE/activity-categories/$SUPPLIED" | jq '.data.attributes | {name, is_system}'
```

Expect 200, the new name, and `is_system` still true. Ask again with `Accept-Language: en`: the name
stays as written, because words somebody chose stand in every language. Relationship categories
refuse this same rename today, which [research.md](research.md) section 1 explains as a defect over
there rather than a decision here.

## 4. The refusals

Each must answer without changing anything:

- Delete a supplied category. **422**, naming that reason.
- Add a category whose name is only spaces. **422.**
- Any address above with another account's uuid. **404**, never 403.

Deleting a category that holds natures cannot be exercised yet: activity natures do not exist. The
guard is written and tested against a count of zero, and starts refusing the day the record lands.

## 5. Retiring, which nothing can show yet

```bash
curl -s -X PUT -H "$H" -H 'Content-Type: application/json' \
  -d '{"is_active": false}' "$BASE/activity-categories/$UUID/activation" | jq '.data.attributes.is_active'
```

Send it twice and the answer stays `false`. There is nothing to look at afterwards: no screen offers
activity natures, so the state is recorded for the picker that will read it and has no visible
effect today. Expect that rather than hunting for one.

## 6. The suite

```bash
php artisan test --compact --filter=ActivityCategory
php artisan test --compact tests/Unit/ViewModels/Docs/ApiDocsViewModelTest.php
vendor/bin/pint --dirty --format agent && vendor/bin/phpstan analyse
php artisan monica:localize --check
```

The docs test fails if a route shipped without a reference entry, or the reverse.
