# Quickstart: Validating the response field documentation

**Feature**: `specs/008-api-field-docs` | **Date**: 2026-09-01

How to prove this feature works, once it is built. Shapes and rules are in
[contracts/return-fields.md](contracts/return-fields.md) and [data-model.md](data-model.md) rather than
repeated here.

## Prerequisites

- The application running locally (`composer run dev`, or `php artisan serve` with `bun run dev`).
- A migrated database. No migration is added by this feature.
- No API key needed for most of this: the reference is open to anybody, signed in or not.

## 1. Run the tests

The check that gives the feature its value, and the one most worth watching fail:

```bash
php artisan test --compact tests/Feature/Controllers/App/Docs/ApiDocsReturnFieldsTest.php
```

The rest of the portal, which must be unaffected:

```bash
php artisan test --compact \
  tests/Feature/Controllers/App/Docs/ \
  tests/Unit/ViewModels/Docs/ \
  tests/Feature/Views/Components/ApiDocs/
```

The API itself, which this feature must not have touched:

```bash
php artisan test --compact tests/Feature/Controllers/Api/
```

Then the gates:

```bash
php artisan test --compact
vendor/bin/pint --dirty --format agent
vendor/bin/phpstan analyse
php artisan monica:localize --check
```

## 2. Prove the check actually catches drift

This is the step that matters. A green suite tells you nothing until you have watched it go red for the
right reason. Do all three, and undo each before the next.

**Add a field to a response.** Put a line in `app/Http/Resources/VaultResource.php`, inside
`attributes`, say `'nonsense' => true`.

**Expect** the suite to fail on four vault endpoints, naming `data.attributes.nonsense` as something the
answer carries and the reference does not mention. A pass here means the check is not looking.

**Remove a field from the reference.** Delete one row from the vault field list in
`resources/docs/api/08-vaults.php`.

**Expect** the same failure from the other direction, naming the field nothing documents.

**Invent a field in the reference.** Add a row named `data.attributes.imaginary`.

**Expect** a failure naming a documented field no answer carries.

## 3. Prove the check cannot pass on an empty list

Change the arm for listing vaults so it arranges no vault, then run the check.

**Expect** an explicit failure saying a record has to be arranged for that endpoint, not a pass. A pass
here would mean every `data[]` row in the reference is unverified, which is the quiet failure this guard
exists to prevent.

## 4. Read the block on the page

Open `/docs/api/vaults-index` and confirm:

- The Returns summary is still there, above the rows.
- Every field of the example response beside it has a row, and no row names something the example does
  not carry.
- A row is a path, a type and a sentence. The path says its own parent: `data[].attributes.name`, not
  `name`.
- Indentation follows the path.
- Nothing in the block is told apart by colour alone.

Then check a few particular endpoints, because they are the awkward ones:

| Open | Confirm |
| --- | --- |
| `/docs/api/vaults-destroy` | a summary saying there is no body, and no rows |
| `/docs/api/api-keys-create` | `token` documented at the top of the answer, beside `data`, not inside it |
| `/docs/api/emails-sent-show` | the copy of the email documented here, and absent from `emails-sent-index` |
| `/docs/api/logs-index` | `data[].attributes.parameters` documented as an object whose keys depend on the action, with nothing under it |
| `/docs/api/status` | three flat fields, no envelope |

## 5. Read it in both themes and at 200%

Switch the theme and confirm the block is legible in both, then zoom to 200% and confirm the rows still
read and nothing is cut off. Tab through the page and confirm the block adds no trap: it is text, so
there should be nothing new to focus.

## 6. Read it as plain text

```bash
curl -s http://127.0.0.1:8000/docs/api/vaults-index.md | sed -n '/## Returns/,/## Rate limit/p'
```

**Expect** the summary, then one bullet per field, each carrying the path, the type and the sentence, in
the order of the answer. This is the rendering where the path earns its keep: there is no indentation to
lean on.

## 7. Spot check the descriptions against the code

The check compares names and never meanings, so this is the only guard on the part a reader will act on.
Take ten rows at random and confirm each against the code:

- a bounded string states the same maximum the validation rule enforces;
- a value from a fixed set lists what the enum actually holds;
- a field that can be absent says the circumstances, and they are the circumstances in the resource;
- a time says it is a count of seconds, which is what `->timestamp` returns;
- a derived field says what it derives from.

Two known traps, both found while planning: `data.attributes.action` on a log is a plain string and not
one of a fixed set, and every `*_at` field in this API is a number rather than a written date.

## What proves the feature is done

- Every field of all 16 endpoints that answer with a body is documented, and the suite proves both
  directions.
- Watched fail: a field added to a resource, a row removed from the reference, a row invented, and a list
  arranged empty.
- The three endpoints that answer with no body document no fields and say so.
- The block reads correctly on the page in both themes, at 200%, and as plain text.
- No API behaviour changed, no migration ran, and `monica:localize --check` reports nothing missing.
