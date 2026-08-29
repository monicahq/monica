# Implementation Plan: Sent emails endpoints

**Branch**: `2026-08-29-sent-emails-api` | **Date**: 2026-08-29 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `specs/005-sent-emails-api/spec.md`

## Summary

Two read-only endpoints put the sent emails screen behind the API: `GET /api/v1/emails-sent` lists
the emails an account has sent, newest sent first, a page at a time, and
`GET /api/v1/emails-sent/{emailSent}` reads one of them including the copy that left. They mirror the
journal endpoints shipped in the feature before this one, field shape for field shape.

The one piece of new domain code is the delivery state. The rule for it exists today inline in a
blade partial. It moves onto the model as an enum-backed accessor so the screen and the API cannot
disagree, and the partial is rewired to read it. Everything else is assembly: a resource, a thin
controller, two routes, a reference group, and the tests.

## Technical Context

**Language/Version**: PHP 8.4

**Primary Dependencies**: Laravel 12, Laravel Sanctum (token authentication, already wired for the
API group)

**Storage**: MySQL, table `emails_sent`, already migrated. No schema change.

**Testing**: PHPUnit, `php artisan test --compact`

**Target Platform**: Linux server, self hosted and hosted alike

**Project Type**: Server rendered Laravel web application with a versioned JSON API beside it

**Performance Goals**: A list page is one query plus a count, and its size does not grow with the
length of the email copy it lists.

**Constraints**: 60 requests per minute per token, the limit the rest of the authenticated API
carries. Page size 25 by default, 100 at most, clamped rather than refused.

**Scale/Scope**: Two endpoints, one new enum, one new resource, one new controller, one reference
group, one accessor, one partial rewired. Roughly 12 files touched.

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principle | Verdict | How this feature satisfies it |
| --- | --- | --- |
| I. Actions hold the business logic | **Pass** | No action, and that is correct. An action is one class per *user operation*, and this feature performs none: both endpoints only read. The journal and API key list endpoints likewise call no action. Nothing here writes, so there is no operation to name. |
| II. Simplicity over abstraction | **Pass** | One resource with one conditional key rather than two near-identical resources. No service layer, no repository, no base class. The controller is two short methods. |
| III. Tested before merged | **Pass** | A feature test per endpoint covering the compartment, the paging, the clamping and the three delivery states; a model test for the accessor; an enum test; a docs assertion. The route sync test already guards the reference in both directions. |
| IV. Laravel conventions are the default | **Pass** | Eloquent resource, route model scoping through the account relation, `findOrFail` for the 404, config for the page bounds, `#[Test]` PHPUnit classes. One deliberate deviation from the `api-writer` skill, recorded in [research.md](research.md) section 4 and justified below. |
| V. Localized and safe UX | **Pass** | No new user-facing string. The screen keeps its wording; the enum carries none. `monica:localize` runs to confirm nothing went missing. Nothing destructive: the feature cannot write. |
| VI. Nothing changes under anybody's feet | **Pass** | Additive only. No existing endpoint, field or route name changes. The new endpoints land under the existing `v1` prefix, which is what that prefix is for. |
| VII. A stable core, unlimited personalisation | **Not applicable** | Touches no template, page, section or field. |
| VIII. Nothing disappears silently | **Pass** | The feature deletes nothing and deactivates nothing. It makes an existing record readable. |
| IX. Accessible to everybody | **Pass** | No new screen. The one blade file touched keeps its badge exactly as it is, including the rule that the three states are three words rather than three colours. Rewiring it must not change a single rendered class or word, and the existing view test is what proves it. |
| X. Everything goes through the API | **Pass** | This is the principle the feature exists to serve. It closes the gap between the sent emails screen and the API, and the reference is written in the same change rather than after it. |

**Gate result: passes.** One deviation to declare, per Principle IV's requirement that any deviation
be justified in the pull request that introduces it:

> The `api-writer` skill requires `index` to return `AnonymousResourceCollection` from
> `{Resource}::collection($paginated)`. This controller returns a `JsonResponse` carrying a hand built
> `data` and `meta` instead, matching `Api\LogController` and `Api\ApiKeyController`. The published
> reference promises exactly `page`, `per_page` and `total`; Laravel's default paginator envelope
> emits a dozen more keys, which would be a promise made by accident and, under Principle VI,
> impossible to withdraw. `.claude/CLAUDE.md` ranks the code already there above the skill. Nine
> further rows where those two skills describe a codebase this is not are tabulated in
> [research.md](research.md) section 4.

## Project Structure

### Documentation (this feature)

```text
specs/005-sent-emails-api/
├── plan.md              # This file
├── spec.md              # Feature specification
├── research.md          # Phase 0 output
├── data-model.md        # Phase 1 output
├── quickstart.md        # Phase 1 output
├── contracts/           # Phase 1 output
│   └── emails-sent.md
├── checklists/
│   └── requirements.md
└── tasks.md             # Phase 2 output (/speckit-tasks, NOT created by /speckit-plan)
```

### Source Code (repository root)

```text
app/
├── Enums/
│   └── EmailDeliveryStatusEnum.php          # new: bounced, delivered, sent
├── Models/
│   └── EmailSent.php                        # edit: delivery_status accessor
├── Http/
│   ├── Controllers/Api/
│   │   └── EmailSentController.php          # new: index, show
│   └── Resources/
│       └── EmailSentResource.php            # new

routes/
└── api.php                                  # edit: two routes in the sanctum group

resources/
├── docs/api/
│   └── 07-emails-sent.php                   # new: the reference group
└── views/app/settings/account/logs/
    └── _email-sent-row.blade.php            # edit: read the accessor, render unchanged

tests/
├── Feature/Controllers/Api/
│   └── EmailSentControllerTest.php          # new
├── Feature/Controllers/App/Docs/
│   └── ApiDocsControllerTest.php            # edit: assert the new sidebar group
├── Unit/Enums/
│   └── EmailDeliveryStatusEnumTest.php      # new
└── Unit/Models/
    └── EmailSentTest.php                    # edit: accessor cases

database/factories/
└── EmailSentFactory.php                     # edit: delivered() and bounced() states
```

No new top-level folder, so no constitutional amendment is needed. Every file lands in a folder the
Technology and Architecture Constraints section already names.

## Implementation Notes

### Order of work

The accessor comes first, because both the resource and the blade partial read it. The reference
group must land in the same commit as the routes, or `ApiDocsViewModelTest` fails in both directions.

### The route names and URLs

```php
Route::get('emails-sent', [EmailSentController::class, 'index'])->name('emailsSent');
Route::get('emails-sent/{emailSent}', [EmailSentController::class, 'show'])
    ->where('emailSent', '[1-9][0-9]*')
    ->name('emailsSent.show');
```

Plural kebab-case URL, camelCase route name and parameter, numeric constraint on the identifier. The
name matches the web route already called `settings.emailsSent.index`, and the pair sits inside the
existing `auth:sanctum` group so it inherits the throttle and the locale middleware without saying so.

### The one risk worth naming

Rewiring `_email-sent-row.blade.php` is the only edit that can regress something a person sees.
The accessor must return the same state the inline `match` returns for all three cases, bounced
winning over delivered, and the partial must keep its own tone and wording. The existing view test is
the guard; if it does not already cover all three badges, the task list adds the missing cases before
the partial is touched, not after.

## Complexity Tracking

*No entry required.* Nothing in this design departs from the constitution, and the single deviation
from the `api-writer` skill is declared under the Constitution Check gate above with its rationale.

## Constitution Re-check (post Phase 1 design)

Re-run after `data-model.md`, `contracts/emails-sent.md` and `quickstart.md` were written. **Still
passes**, with three things the design settled that the first pass could only assume:

- **Principle II holds under the contract.** The contract needs one resource, not two. `body` is the
  only field that differs between the two endpoints, and it is one conditional key rather than a
  second class.
- **Principle X is met field for field.** The data model lists five columns deliberately withheld
  (`account_id`, `user_id`, `uuid`, `updated_at`, and `body` on list rows only), each with a reason.
  Everything the screen shows a person is in the contract, which is what SC-001 asks for.
- **Principle III gained one requirement.** The design surfaced that the blade partial's three-state
  mapping has no test today, so rewiring it would be unguarded. The tasks must add those cases before
  the partial is touched. This is recorded in the plan's risk note and as step 5 of the quickstart.

No new complexity, no new dependency, no schema change, and no top-level folder. The Complexity
Tracking section stays empty.
