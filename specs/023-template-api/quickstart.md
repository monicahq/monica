# Quickstart: proving a phase works

The endpoints are in [contracts/endpoints.md](contracts/endpoints.md). This is how to run and check them.

## Prerequisites

Herd serves the site already. Get a token the way the reference tells a reader to, so the quickstart
exercises the same path a caller does.

```bash
BASE=$(php artisan tinker --execute 'echo config("app.url");')/api/v1
TOKEN=$(php artisan tinker --execute 'echo App\Models\User::first()->createToken("quickstart")->plainTextToken;')
```

## Per phase, the one call that proves it

Each phase is done when its call answers and the suite is green. Run the calls of the earlier phases
too: they are the regression check that the later work left the reading surface alone.

```bash
# P1  the structure a record follows, nested one level down from the version
curl -s -H "Authorization: Bearer $TOKEN" "$BASE/templates" | jq '.data[0].attributes, .meta'
curl -s -H "Authorization: Bearer $TOKEN" "$BASE/templates/$UUID/versions/1" | jq '.data.attributes.pages'

# P2  build a template from nothing and take it back
curl -s -X POST -H "Authorization: Bearer $TOKEN" -H 'Content-Type: application/json' \
  -d '{"name":"Wine bottle","applies_to":"contact"}' "$BASE/templates" | jq '.data.id'

# P3  open a draft, publish it, and confirm the second open is the same draft
curl -s -X POST -H "Authorization: Bearer $TOKEN" "$BASE/templates/$UUID/versions" | jq '.data.attributes.version_number'
curl -s -X PUT -H "Authorization: Bearer $TOKEN" -H 'Content-Type: application/json' \
  -d '{"change_note":"Added the address section."}' "$BASE/templates/$UUID/versions/2/publication" | jq '.data.attributes.status'

# P4  a page and a section placed on it
curl -s -X POST -H "Authorization: Bearer $TOKEN" -H 'Content-Type: application/json' \
  -d '{"label":"Contact details"}' "$BASE/templates/$UUID/versions/2/pages" | jq '.data.id'

# P5  a field, and the section draft the change opened
curl -s -X POST -H "Authorization: Bearer $TOKEN" -H 'Content-Type: application/json' \
  -d '{"type":"text","label":"Nickname"}' "$BASE/templates/$UUID/versions/2/sections/1/fields" | jq '.data.attributes'
```

## What to check that a happy path will not show you

Three refusals carry the feature, and each is a test before it is a curl call.

- A write against a published version answers `422` and leaves the version untouched. Read the version
  back after the refusal, not just the status code.
- Anything belonging to another account answers `404` and says nothing about why. Build a second account
  in the test rather than trusting the query scope by reading it.
- `POST versions` twice in a row answers the same `version_number` both times, the second with `200`.

## The suite

```bash
php artisan test --compact tests/Feature/Controllers/Api tests/Unit/Actions
php artisan test --compact tests/Unit/ViewModels/Docs   # fails while an endpoint is undocumented
vendor/bin/pint --dirty --format agent
vendor/bin/phpstan analyse --memory-limit=2G            # CI runs level 5, and the checklist omits it
```
