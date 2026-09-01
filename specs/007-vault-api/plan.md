# Implementation Plan: Vault endpoints

**Branch**: `2026-09-01-vault-api` | **Date**: 2026-09-01 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `specs/007-vault-api/spec.md`

## Summary

Five endpoints put the vault screens behind the API. `GET /api/v1/vaults` lists the vaults of an
account in the order the account arranged them, a page at a time. `GET /api/v1/vaults/{vault}` reads
one. `POST /api/v1/vaults` opens one. `PUT /api/v1/vaults/{vault}` replaces its name, what it is for
and its colour. `DELETE /api/v1/vaults/{vault}` deletes it and everything it separates from the rest.
Both write endpoints also accept the two decisions the browser form asks alongside the wording:
whether this is the vault records land in by default, and which published contact structure new
contacts in it start from.

There is no new domain code at all. Every operation is already a named action the browser path calls,
each already refusing another account's vault and each already writing to the journal. This feature is
one resource, one controller, five routes, one reference group and their tests. The one design decision
worth arguing is that the change endpoint replaces rather than patches, which forced `colour` to be
required and `description` to be stated even when null, so that renaming a vault cannot silently reset
its colour or erase its description.

## Technical Context

**Language/Version**: PHP 8.4

**Primary Dependencies**: Laravel 12, Laravel Sanctum (token authentication, already wired for the API
group). No new dependency.

**Storage**: table `vaults`, already migrated, plus `vault_default_templates` written through an
existing action. No schema change, no migration.

**Testing**: PHPUnit, `php artisan test --compact`

**Target Platform**: Linux server, self hosted and hosted alike

**Project Type**: Server rendered Laravel web application with a versioned JSON API beside it

**Performance Goals**: a list page is one query plus a count. `contact_count` is a stored counter, so
listing vaults never aggregates over contacts.

**Constraints**: 60 requests per minute per token, the limit the rest of the authenticated API carries.
Page size 25 by default, 100 at most, clamped rather than refused. The suite must pass on SQLite, MySQL
and PostgreSQL, so no driver specific SQL, and the uuid column is compared as the string it is.

**Scale/Scope**: five endpoints, one new resource, one new controller, one reference group, five routes.
Roughly 6 files, of which 3 are new.

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principle | Verdict | How this feature satisfies it |
| --- | --- | --- |
| I. Actions hold the business logic | **Pass** | Five operations, five existing actions, called with named arguments. The controller validates, calls, and returns. It holds no rule of its own: even the order in which the three writes are applied on a create is the order the web controller already uses. |
| II. Simplicity over abstraction | **Pass** | One resource for four endpoints, with no conditional keys. No base controller, no service, no repository, no form request. Five short methods. |
| III. Tested before merged | **Pass** | One feature test class covering all five endpoints: the shapes, the paging and clamping, the compartment on each endpoint, every refusal, the default vault invariant, and the journal. The five action tests already exist and are the proof the operations were not touched. The route drift test already guards the reference in both directions. |
| IV. Laravel conventions are the default | **Pass** | Eloquent resource, inline validation, account scoped lookup with `firstOrFail` for the 404, config for the page bounds, plural kebab-case URL, camelCase route names and parameter, explicit status codes. Three deviations from the `api-writer` skill, declared below. |
| V. Localized and safe user experience | **Pass** | No new user-facing string: the reference is English copy on the same surface as the seven groups already there, and the API returns no prose of its own. `monica:localize --check` runs to prove nothing went missing. On safety, see the note under Principle VIII. |
| VI. Nothing changes under anybody's feet | **Pass** | Additive only. No existing endpoint, field, route name or screen changes. The endpoints land under the existing `v1` prefix, which is what that prefix is for. The `meta` envelope is hand built so the API promises three keys and not a dozen it can never withdraw. |
| VII. A stable core, unlimited personalisation | **Pass** | The vault is one of the nine fixed concepts, and nothing here makes its nature personalisable. `template_version_id` reaches the one personalisation mechanism through the action that owns it, and introduces no second way to attach user defined data. |
| VIII. Nothing disappears silently | **Pass, and it shaped the design** | Two places. `DestroyVault` is reused unchanged, so contacts and invented records are marked as having left with the vault rather than dropped, and the reference states what goes rather than leaving a caller to discover it. And the change endpoint requires `colour` and a stated `description` precisely because the alternative was a rename that quietly reset both. |
| IX. Accessible to everybody | **Pass** | No screen is touched. The one rendered surface is the reference page, which gains a group through the existing data driven portal, so it inherits the markup, the focus behaviour and the verb badges that were already audited. Nothing new to make accessible. |
| X. Everything goes through the API | **Pass** | This is the principle the feature exists to serve. It closes the vault screens' gap, and the reference is written in the same change rather than after it. One gap stays open and is named rather than hidden: credentials whose reach can be narrowed do not exist, so any valid key can delete a vault. |

**Gate result: passes.** Three deviations to declare, per Principle IV's requirement that any deviation
be justified in the pull request that introduces it. All three are from
`.claude/skills/api-writer/SKILL.md`, which was written against a different codebase, and
`.claude/CLAUDE.md` ranks the code already there above the skill:

> 1. `index` returns a `JsonResponse` carrying a hand built `data` and `meta`, not an
>    `AnonymousResourceCollection`. The reference promises exactly `page`, `per_page` and `total`;
>    Laravel's paginator envelope emits a dozen more keys, which under Principle VI is a promise made
>    by accident and impossible to withdraw. All three lists already shipped do it this way.
> 2. The page bounds are read from `config('monica.api_maximum_items_per_page')`, which is the key this
>    application has.
> 3. The identifier is constrained to the uuid shape rather than to `[1-9][0-9]*`, because a vault is
>    addressed by its uuid. The spirit of the rule is kept: the router refuses an address of the wrong
>    shape before a query runs.

The reasoning behind each, and the rows of the table that no longer need restating, are in
[research.md](research.md) section 8.

## Project Structure

### Documentation (this feature)

```text
specs/007-vault-api/
├── plan.md              # This file
├── spec.md              # Feature specification
├── research.md          # Phase 0 output
├── data-model.md        # Phase 1 output
├── quickstart.md        # Phase 1 output
├── contracts/           # Phase 1 output
│   └── vaults.md
├── checklists/
│   └── requirements.md
└── tasks.md             # Phase 2 output (/speckit-tasks, NOT created by /speckit-plan)
```

### Source Code (repository root)

```text
app/
└── Http/
    ├── Controllers/Api/
    │   └── VaultController.php               # new: index, show, create, update, destroy
    └── Resources/
        └── VaultResource.php                 # new

routes/
└── api.php                                   # edit: five routes in the sanctum group

resources/
└── docs/api/
    └── 08-vaults.php                         # new: the reference group, five sections

tests/
├── Feature/Controllers/Api/
│   └── VaultControllerTest.php               # new
└── Feature/Controllers/App/Docs/
    └── ApiDocsControllerTest.php             # edit: assert the new sidebar group
```

Three new files, two edited. No new top-level folder, so no constitutional amendment is needed, and
every file lands in a folder the Technology and Architecture Constraints section already names. Nothing
under `app/Actions`, `app/Models`, `database/` or `resources/views` is touched.

**Structure Decision**: the existing API layout is followed exactly, mirroring the web controller's
place under `Api` instead of `App`, with the resource in `app/Http/Resources` beside the four already
there and the reference group as one more numbered file under `resources/docs/api`. There is no
frontend half to this feature and no second project.

## Implementation Notes

### Order of work

1. `VaultResource`, since everything else answers with it.
2. `VaultController`, five methods.
3. The five routes.
4. `resources/docs/api/08-vaults.php`. This must land in the same commit as the routes: either one
   alone fails `ApiDocsViewModelTest`, which compares the reference against the route table in both
   directions.
5. `VaultControllerTest`, plus the one line in `ApiDocsControllerTest`.

### The routes

```php
Route::get('vaults', [VaultController::class, 'index'])->name('vaults');
Route::get('vaults/{vault}', [VaultController::class, 'show'])->where('vault', '[0-9a-fA-F-]{36}')->name('vaults.show');
Route::post('vaults', [VaultController::class, 'create'])->name('vaults.create');
Route::put('vaults/{vault}', [VaultController::class, 'update'])->where('vault', '[0-9a-fA-F-]{36}')->name('vaults.update');
Route::delete('vaults/{vault}', [VaultController::class, 'destroy'])->where('vault', '[0-9a-fA-F-]{36}')->name('vaults.destroy');
```

They sit inside the existing `auth:sanctum` group, so they inherit `throttle:60,1` and `set.locale`
without saying so. The group prefixes the names with `api.v1.`. The constraint does not change
`$route->uri()`, so the documented path stays `/vaults/{vault}`.

### The lookup

```php
$vault = $request->user()->account->vaults()->where('uuid', $uuid)->firstOrFail();
```

Explicit, account scoped, and 404 by exception, which is how the four API controllers already shipped
read a record. Implicit route model binding would also work and was rejected;
[research.md](research.md) section 2 says why, including the reason it would have made
`/api/v1/vaults/3` resolve.

### The two decisions applied after the write

Both write methods do what `App\Http\Controllers\App\Vaults\VaultController` does, in the same order: the vault is written
first, then `SetVaultDefaultTemplate` runs if the request carried a `template_version_id`, then
`SetDefaultVault` runs if the request said `is_default` is true. A false or absent `is_default` calls
nothing, because the title is never left unheld.

### The risk worth naming

There is no screen to regress and no schema to migrate, so the only way this feature can do harm is by
answering differently from the browser. Three tests are the guard, and none of them is optional:

- A change that omits `colour` or `description` must be refused. If it is accepted, `UpdateVault` will
  write the default colour and an empty description, and a caller renaming a vault loses both.
- After every write, the account must hold exactly one default vault, asserted as a count across the
  account rather than as a flag on the vault that was written. A test on the flag alone passes while two
  vaults hold the title.
- Each of the five endpoints must answer 404 for a vault in another account, indistinguishable from a
  uuid that named nothing.

## Complexity Tracking

*No entry required.* Nothing in this design departs from the constitution. The three deviations from
the `api-writer` skill are declared under the Constitution Check gate with their rationale, and two of
them are the behaviour of the three lists already shipped rather than new choices.

## Constitution Re-check (post Phase 1 design)

Re-run after [data-model.md](data-model.md), [contracts/vaults.md](contracts/vaults.md) and
[quickstart.md](quickstart.md) were written. **Still passes**, with four things the design settled that
the first pass could only assume:

- **Principle I is stronger than expected.** The design needs no new class under `app/Actions` at all,
  and no edit to one. Five operations, five existing actions, five existing action tests. That is the
  clearest evidence available that the browser path kept its logic in the right place, and it is worth
  saying in the pull request.
- **Principle VIII did real work.** Writing the contract surfaced that `UpdateVault` writes an absent
  field as empty, which would have made a rename erase a description and reset a colour. The change
  endpoint now requires all three fields. This sharpened FR-009 in the spec, which had said only that
  the three were changeable, and it is recorded as FR-009a.
- **Principle X is met field for field, with one asymmetry stated rather than hidden.** The data model
  lists four columns deliberately withheld (`id`, `account_id`, `last_visited_at`, `deleted_at`), each
  with a reason, and one field a caller may write but not read: which structure new contacts start
  from. That default is held per vault *and* per kind of record, so a single field on a vault would
  answer a narrower question than its name suggests. Reading it is its own endpoint in its own change,
  and the contract says so.
- **Principle III gained one requirement.** The default vault invariant cannot be proved by asserting a
  boolean on the vault that was just written, because the failure mode is two vaults holding the title
  at once. The tests assert the count across the account after each write. This is in the plan's risk
  note and in step 6 of the quickstart.

No new complexity, no new dependency, no schema change, no new folder, and no screen touched. The
Complexity Tracking section stays empty.
