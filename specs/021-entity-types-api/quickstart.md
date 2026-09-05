# Quickstart: entity type endpoints

## Prerequisites

An account and an API key minted at Settings, API. No vault: a type belongs to the account. Fields and bodies are in [contracts/entity-types.md](contracts/entity-types.md).

```bash
export MONICA_TOKEN=... MONICA_BASE=http://localhost:8000/api/v1
alias api='curl -sS -H "Authorization: Bearer $MONICA_TOKEN" -H "Accept: application/json"'
```

## The run

```bash
# 1. Two names alone. Expect 201, a derived slug, position last, is_active true, the defaults. Note the id.
api -X POST "$MONICA_BASE/entity-types" -d name_singular=Animal -d name_plural=Animals

# 2. A second one, choosing everything. Expect 201 and the slug you asked for.
api -X POST "$MONICA_BASE/entity-types" -d name_singular="Wine bottle" -d name_plural="Wine bottles" \
  -d slug=cellar -d icon=key -d colour=%239a6a24 -d description="What is in the cellar." -d is_active=0

# 3. Read one back, and list them. Expect the same object, and position order in the list.
api "$MONICA_BASE/entity-types/$TYPE" && api "$MONICA_BASE/entity-types"

# 4. Correct the first. Every field must be sent. Expect 200, new wording, the same slug.
api -X PUT "$MONICA_BASE/entity-types/$TYPE" -d name_singular=Pet -d name_plural=Pets \
  -d description= -d icon=profile -d colour=%234f8a49

# 5. Stop it being offered, then offer it again. Expect is_active to follow, position unmoved.
api -X PUT "$MONICA_BASE/entity-types/$TYPE/activation" -d is_active=0
api -X PUT "$MONICA_BASE/entity-types/$TYPE/activation" -d is_active=1

# 6. Move it behind the other one. Expect position 2, and the other renumbered to 1.
api -X PUT "$MONICA_BASE/entity-types/$TYPE/position" -d position=2

# 7. Delete it. Expect 204, then 404 on the same address.
api -X DELETE "$MONICA_BASE/entity-types/$TYPE" && api "$MONICA_BASE/entity-types/$TYPE"
```

## What must fail

| Try | Expect |
| --- | --- |
| Any of the seven addresses with another account's uuid, with a deleted type's uuid, or with a slug in place of the uuid | 404, never 403 |
| Step 1 again with `slug=cellar`, and again with the slug of the type deleted at step 7 | 422 both times: a deleted type does not release its slug |
| `icon=sparkle`, `colour=#ff0000`, `slug="Wine Bottles"` | 422 naming the field |
| Step 4 without `description` in the body | 422, and the description still there |
| Deleting a type after creating a record of it in the browser | 422 naming the count, and the type still there |
| `per_page=500`, then no `Authorization` header | 200 with `meta.per_page` of 100, then 401 |

## The suite

```bash
php artisan test --compact tests/Feature/Controllers/Api tests/Unit/ViewModels/Docs \
  tests/Feature/Controllers/App/Docs tests/Feature/Controllers/App/Settings
vendor/bin/pint --dirty --format agent && vendor/bin/phpstan analyse --memory-limit=2G && php artisan monica:localize --check
```

`ApiDocsViewModelTest` compares the reference against the route table both ways, so a route with no
documentation, or documentation with no route, fails the suite.
