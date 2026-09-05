# Implementation Plan: Contact endpoints

**Branch**: `2026-09-04-api-contact` | **Date**: 2026-09-04 | **Spec**: [spec.md](spec.md)

## Summary

Nine endpoints put the contact screens behind the API, listed in [contracts/contacts.md](contracts/contacts.md).
Eight of the nine call an action that already exists and is already tested, so no new domain code, no migration.

The one decision worth arguing is the list. Its narrowing, its search fold and its ordering live in
`ContactsViewModel`, and Principle I forbids any surface but Blade from reading a view model. Rather than
copy fifteen lines into the controller and hope the two never drift, the query moves onto `Contact` as three
scopes and its vocabulary into two enums, which both surfaces call. [research.md](research.md) section 1 has
the rejected alternative.

## Technical Context

| | |
| --- | --- |
| Language, dependencies | PHP 8.4, Laravel 12, Sanctum. All already wired for the API group; nothing new. |
| Storage | Tables `contacts` and `files`, already migrated. No schema change, no migration. |
| Testing | PHPUnit, `php artisan test --compact` |
| Target, type | Linux server, hosted and self hosted alike. Server rendered Laravel with a versioned JSON API beside it. |
| Performance | A list page is one query plus a count, structure and avatar eager loaded, so 100 contacts stay three queries. |
| Constraints | 60 requests a minute per token. Page size 25, at most 100, clamped. The suite runs on SQLite, MySQL and PostgreSQL, so nulls last is stated rather than inherited: Postgres ranks null above everything, the other two below. |
| Scope | 9 endpoints, 5 controllers, 1 resource, 2 enums, 3 scopes, 1 reference group. Roughly 17 files, 10 of them new. |

## Constitution Check

*GATE: must pass before Phase 0. Re-checked after Phase 1 below.*

| Principle | Verdict | How this feature satisfies it |
| --- | --- | --- |
| I. Actions hold the business logic | **Pass** | Eight operations, eight existing actions, named arguments. No new class under `app/Actions` and no edit to one. The scopes exist to move query logic out of a view model onto the model, where both surfaces may have it. |
| II. Simplicity over abstraction | **Pass** | Five short controllers mirroring the five web ones, one resource, no base class, service, repository or form request. The scopes are extracted because they are non-trivial and now used twice, which is the test this rule sets. |
| III. Tested before merged | **Pass** | Five API test classes covering every endpoint, every refusal and the compartment on each. `ContactsViewModelTest` passing unchanged is the proof the screen did not move. |
| IV. Laravel conventions | **Pass** | Eloquent resource, inline validation, account scoped `firstOrFail` for the 404, config for the page bounds, kebab-case URLs, camelCase route names. Four declared deviations from `api-writer`, in [research.md](research.md) section 9. |
| V. Localized and safe | **Pass** | One new user-facing string, the refusal to delete the Me contact, reused from the exception the web path already throws. `monica:localize` runs, nine locales translated. |
| VI. Nothing changes under anybody's feet | **Pass** | Additive. The one edit to shipped behaviour is the view model calling scopes instead of inlining them, which is the same query. The `meta` envelope stays hand built, so the API promises three keys it can keep. |
| VII. A stable core | **Pass** | Contact is one of the nine fixed concepts and nothing here makes its nature personalisable. `template_version_id` reaches the one personalisation mechanism through the action that owns it. |
| VIII. Nothing disappears silently | **Pass, and it shaped the design** | Changing a name replaces, so all eight parts are required rather than letting a caller erase a nickname by omission. And `DestroyContact` is reused unchanged, so a removal keeps what the person took part in and quiets their reminders with a reason. |
| IX. Accessible to everybody | **Pass** | No screen is touched. The one rendered surface is the reference, which gains a group through the existing data driven portal and inherits markup already audited. |
| X. Everything goes through the API | **Pass** | The principle the feature exists to serve. Two gaps are named rather than hidden: a key's reach still cannot be narrowed, so any valid key can delete a contact, and a picture's URL still needs the session because no files endpoint exists. |
| XI. The written record is short | **Pass** | spec 120, plan 100, research 80, contract 60, quickstart 60, each at or under its cap. `data-model.md` is omitted: the spec's Key Entities and the contract's field list carry it, and there is no schema to describe. |

**Gate result: passes.** The deviations from `.claude/skills/api-writer/SKILL.md` are declared in
[research.md](research.md) section 9, as Principle IV requires. **Complexity Tracking** needs no entry: the
scopes and enums are extraction under Principle II's own test, and they remove a duplication rather than add one.

## Project Structure

```text
app/Enums/                     ContactGroupingEnum, ContactSortEnum                  new
app/Models/Contact.php         scopeInGrouping, scopeMatchingSearch, scopeSortedBy   edit
app/Http/Resources/            ContactResource                                       new
app/Http/Controllers/Api/      ContactController (index, show, create, destroy),     new
                               ContactNameController, ContactFavouriteController,
                               ContactAvatarController, ContactTemplateVersionController
app/Http/Controllers/App/Vaults/ContactAvatarController.php   catch the quota refusal  edit
app/ViewModels/Vaults/ContactsViewModel.php                   call the scopes          edit
routes/api.php   nine routes in the sanctum group             routes/web.php  the enum  edit
resources/docs/api/09-contacts.php                            the reference group      new
tests/Feature/Controllers/Api/Contact*ControllerTest.php      five classes             new
tests/Unit/Models/ContactTest.php, tests/Feature/Controllers/App/Docs/ApiDocsControllerTest.php  edit
```

**Structure Decision**: the shipped API layout, exactly. Controllers flat under `Api`, as `Api/VaultController`
already is for `App/Vaults/VaultController`, the resource beside the five in `app/Http/Resources`, the reference
one more numbered file. No new folder, so no amendment.

## Order of work

1. The two enums and the three scopes on `Contact`, with their model tests.
2. `ContactsViewModel` onto the scopes. `ContactsViewModelTest` must pass untouched.
3. `ContactResource`, the five controllers, the nine routes, in that order.
4. `resources/docs/api/09-contacts.php`, in the same commit as the routes: either alone fails `ApiDocsViewModelTest`, which compares the reference against the route table in both directions.
5. The five test classes, the quota fix on both avatar controllers, then the quickstart.

## The risk worth naming

No schema to migrate and one screen touched only by refactor, so the only way this does harm is by answering
differently from the browser. Four tests are the guard:

- Each of the three groupings and each of the four sorts returns what the screen returns, asserted on one
  fixture through both the view model and the endpoint.
- A name change omitting one part is refused, or `UpdateContactName` writes null and a caller loses a nickname.
- All nine addresses answer 404 for another account and for another vault of the same account, and an over
  quota upload answers 422 on both surfaces rather than 500.

## Constitution Re-check (post Phase 1)

Re-run after the contract and the quickstart were written. **Still passes**, with three things settled that the first pass could only assume.

- **Principle I held completely on the write path.** Eight operations, eight existing actions, no edit to any
  of them, which is evidence the browser path kept its logic in the right place. It belongs in the pull request.
- **Principle VIII found a real defect.** `CreateFile` throws `NotEnoughStorageLeft` and the web avatar
  controller catches nothing, so a full account uploading a picture gets a 500 today. Governance requires the
  change that discovers a conflict to fix it, so both controllers are fixed here.
- **Principle X is met with one asymmetry stated rather than hidden.** A token holder can read that a contact
  has a picture and where it lives, but not yet fetch the bytes. A files endpoint is its own change.
