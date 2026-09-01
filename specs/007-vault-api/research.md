# Phase 0 Research: Vault endpoints

**Feature**: [spec.md](spec.md) | **Date**: 2026-09-01

Nothing in the Technical Context was left as NEEDS CLARIFICATION. The two questions the description
left open (whether reading is in scope, and whether the two account level decisions on the creation
form are in scope) were put to the author before the spec was written, and both were answered yes.
What follows is the set of decisions this feature has to take before code is written, each recorded
once here so it is not argued again during implementation.

## 1. The whole domain already exists

**Decision**: write no action, no model change, no migration and no factory state.

Every operation this feature exposes is already a named class the browser path calls:

| Operation | Action | Test |
| --- | --- | --- |
| Create | `app/Actions/CreateVault.php` | `tests/Unit/Actions/CreateVaultTest.php` |
| Change the wording | `app/Actions/UpdateVault.php` | `tests/Unit/Actions/UpdateVaultTest.php` |
| Delete | `app/Actions/DestroyVault.php` | `tests/Unit/Actions/DestroyVaultTest.php` |
| Promote to default | `app/Actions/SetDefaultVault.php` | `tests/Unit/Actions/SetDefaultVaultTest.php` |
| Say what contacts start from | `app/Actions/SetVaultDefaultTemplate.php` | `tests/Unit/Actions/SetVaultDefaultTemplateTest.php` |

Each already refuses a vault in another account by throwing `ModelNotFoundException`, each already
writes to the account journal, and each already has its own test. `Account::vaults()` exists
(`app/Models/Account.php:122`), `VaultFactory` exists with a `default()` state, and `Vault` already
carries a uuid, soft deletes and the casts the resource needs.

**Rationale**: Principle I says a second surface reuses the action rather than restating it, and
Principle X says the API adds nothing but transport. The whole feature is therefore a resource, a
controller, five routes, a reference group and their tests. That is the smallest thing that can
satisfy the spec, and it is why the two surfaces cannot come to disagree.

**Alternative rejected**: writing thinner API specific actions that skip the journal. That would make
the API a second implementation of the same operations, and the first bug would be a write that
appears in the browser's journal and not in the API's.

## 2. A vault is addressed by its uuid, and the lookup is explicit

**Decision**: the route parameter is the vault's uuid, constrained to the uuid shape, and the
controller looks the vault up with `$request->user()->account->vaults()->where('uuid', $uuid)->firstOrFail()`.

**Rationale**: three things point the same way.

- The model already declares `#[RouteKey('uuid')]` and the uuid exists for exactly this purpose
  (`app/Models/Concerns/HasUuids.php`: "Nothing sequential leaves the application that way"). Handing
  out the primary key would disclose how many vaults an instance holds and let one address be guessed
  from the one beside it.
- Every API controller already shipped looks its record up explicitly and scopes it to the caller
  (`EmailSentController::show`, `LogController::show`, `ApiKeyController::show`). Following them keeps
  five controllers reading the same way, and it is what the `api-writer` skill asks for.
- Implicit route model binding would also work, because `ResolvesModelInAccount` scopes to
  `Auth::user()?->account_id` and `auth:sanctum` makes that the token's owner. It was rejected anyway:
  no route in `routes/api.php` binds a model today, so it would be the first, and it depends on
  `Authenticate` running before `SubstituteBindings`, which is true for the web group but is not
  asserted anywhere for the API group. An explicit query depends on nothing and reads the same as its
  four siblings.

There is a second, quieter reason. `ResolvesModelInAccount` deliberately accepts the primary key as a
second spelling of the address, so binding would have made `/api/v1/vaults/3` resolve. The explicit
query plus the route constraint means the uuid is the only address the API has, which is what the
reference will say.

**Constraint**: `->where('vault', '[0-9a-fA-F-]{36}')`, the counterpart of the `[1-9][0-9]*` the
numeric identifiers already carry. A path that is not a uuid is a 404 from the router, before a query
runs. The constraint does not change `$route->uri()`, so the documented path stays `/vaults/{vault}`
and the drift test is unaffected.

## 3. The list is answered in the order the account arranged

**Decision**: `orderBy('position')->orderBy('id')`, not `orderByDesc('id')`.

**Rationale**: a vault carries `position`, which is the order the person put their vaults in, and it
is the order the switcher and the vault list show them in. No other record the API lists has an
equivalent, which is why the other lists answer newest first. Answering in the account's own order is
what makes a listed page match what a person sees, which is what SC-002 asks for. `id` breaks a tie so
the order is total and a page boundary cannot drop or repeat a vault.

**Alternative rejected**: newest first, for consistency with `logs` and `emails-sent`. Consistency
with a list that has no order of its own is not worth answering in an order the account did not choose.

## 4. Creation may omit a colour; a change may not

**Decision**:

| Field | Create | Change |
| --- | --- | --- |
| `name` | required | required |
| `description` | optional | present, may be null |
| `colour` | optional, defaults to the colour a vault gets when nobody picks one | required |

**Rationale**: the change endpoint replaces the wording of the vault, because that is what
`UpdateVault` does: a field it is not given is written as empty, or as the default colour. On creation
there is nothing to lose, so an absent colour taking the default is a convenience. On a change there
is something to lose, and a caller who sends only a new name would silently reset the colour and erase
the description. Principle VIII says nothing disappears silently, so the change endpoint makes the
caller state both: `colour` is required, and `description` must be present even when it is null.

This sharpened FR-009, which said only that the three fields are changeable. The spec has been amended
to say the change replaces them and to name what each field requires.

**Alternative rejected**: reading the vault's current values in the controller and using them as
defaults, so a caller can send one field. That would put behaviour in the controller that the action
does not have, so the API and the browser would treat the same request differently, and it would make
a field impossible to clear. If partial change is wanted later it belongs in the action, for both
surfaces at once.

**Colour validation**: `Rule::in(VaultColourEnum::values())`, the web controller's rule.

That rule compares case sensitively, so `#4F8A49` is refused before the operation underneath has a
chance to lowercase it. Discovered while running the first phase's tests, where a case tolerant
assertion failed. Two ways out, and the tolerant one was rejected: normalising the input in the
controller would put behaviour there that the operation does not have, and would make the API accept a
request the browser refuses. The eight values are exact, in lower case, on both surfaces. The
reference says so, and a test asserts that capitals are refused rather than quietly accepted.

## 5. The two account level decisions are applied after the vault is written

**Decision**: both endpoints accept `is_default` and `template_version_id`, and both call the same two
actions the web controller calls, in the same order and under the same conditions:

- `is_default` only when the request says true. A false or absent value changes nothing, because
  exactly one vault holds the title while the account holds a vault and there is nothing to hand it to.
- `template_version_id` only when the request carries one, validated with the web controller's rule,
  which asks the `templates` table for a published version of an active contact template in the
  caller's account rather than trusting the number.

**Rationale**: each is a decision about the account rather than a property of the vault, which is why
each is its own action with its own journal entry. Applying them after the write is what the browser
does, and doing it in the same order means the journal reads the same whichever surface was used.

**What is deliberately not read back**: the resource does not carry which structure new contacts start
from. A vault's default is held per vault *and per kind of record* in `vault_default_templates`, so one
field on a vault would describe only the contact case and misrepresent the rest. Reading those defaults
is its own endpoint in its own change. The asymmetry is real, it is documented in the reference, and it
is preferable to a field that answers a narrower question than its name suggests.

## 6. Deleting is soft, final from the API, and says so

**Decision**: `DELETE` reuses `DestroyVault` unchanged, answers 204 with no body, and the reference
states plainly what leaves with the vault.

`DestroyVault` already takes the contacts and the invented records with it, marks them as having left
with the vault, gives back the counts they held, revokes every private link into the compartment
permanently, and hands the default title to the first vault left. The last vault of an account may go,
leaving the account in the state it started in. None of that changes here.

**Rationale**: the product requires a destructive action to be confirmed before it is performed, and
that obligation belongs to a screen. An API call is itself the confirmation, so there is no second
step, and the honest equivalent of the warning is a reference that says what goes. `RestoreVault`
exists and the deletion is soft, so nothing is lost, but nothing in the product calls it from any
surface yet, so the reference tells a caller to treat this as final rather than implying an undo the
product does not offer.

## 7. What the reference needs

**Decision**: one new file, `resources/docs/api/08-vaults.php`, holding five sections.

The reference is data rather than prose: one PHP file per sidebar group under `resources/docs/api`,
loaded by `glob` in filename order, so a new group is a new file and nothing else.
`Tests\Unit\ViewModels\Docs\ApiDocsViewModelTest` compares the documented endpoints against
`routes/api.php` in both directions, so the file and the routes have to land in the same commit or the
suite fails twice. `ApiDocsSection` supplies `pathParams`, `queryParams`, `bodyParams`, `body`,
`returns`, `responseStatus`, `response` and `examplePath`, which is everything these five endpoints
need; no change to the portal itself.

`ApiDocsControllerTest` asserts the sidebar group names, so it gains one line for `Vaults`.

## 8. Where this deviates from the two API skills

`.claude/skills/api-writer/SKILL.md` and `api-docs-writer` were written against a different codebase.
`.claude/CLAUDE.md` ranks the code that is already there above the skills, so the code wins. The full
table was recorded in [the previous API feature](../005-sent-emails-api/research.md) section 4 and is
not repeated. Two rows apply again here, and one is new:

| The skill says | This feature does | Why |
| --- | --- | --- |
| `index` returns `AnonymousResourceCollection` from `Resource::collection($paginated)` | returns `JsonResponse` with a hand built `data` and `meta` | The reference promises exactly `page`, `per_page` and `total`. Laravel's paginator envelope emits a dozen more keys, which would be a promise made by accident, and Principle VI makes a promise impossible to withdraw. Matches all three lists already shipped. |
| `config('app.maximum_items_per_page')` | `config('monica.api_maximum_items_per_page')` | The config key this application actually has. |
| Constrain numeric identifiers with `[1-9][0-9]*` | constrains the uuid shape instead | The identifier is a uuid, for the reasons in section 2. The spirit of the rule is kept: the router refuses an address of the wrong shape before a query runs. |

The skill's test list also names cases for owners, editors and viewers. An account carries no role and
holds exactly one person, so those cases have nothing to test. The compartment this feature proves is
the account boundary, on all five endpoints.

## 9. Localization

No new user-facing string. The reference files under `resources/docs/api` are written in English and do
not go through `__()`, which is the existing choice for that surface. `php artisan monica:localize`
still runs, as the checklist requires, to prove nothing went missing rather than to add anything.

## 10. Risks

- **The reference and the routes must land together.** Either one alone fails
  `ApiDocsViewModelTest` in one of its two directions. They belong in the same commit.
- **`UpdateVault` writes an absent field as empty.** Section 4 is the answer, and the test that proves
  it is a change that sends every field and a change that omits `colour` and is refused. If either is
  missing, the trap is back.
- **`is_default` must never leave the title unheld.** `SetDefaultVault` takes it from whoever had it
  inside a transaction, so the only way to break the invariant is to call something else. The test
  asserts the count of default vaults in the account after each write, not just the flag on the vault
  that was written.
- **Nothing else.** No schema change, no new dependency, no new folder, no screen touched, so nothing
  a person sees can regress.
