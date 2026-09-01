# Implementation Plan: Response fields in the API reference

**Branch**: `2026-09-01-api-field-docs` | **Date**: 2026-09-01 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `specs/008-api-field-docs/spec.md`

## Summary

Every endpoint in the reference gains a list of the fields its answer carries: the field's full path, its
type, and a sentence saying what it is and what its name and type do not imply. The one sentence summary
and the example response both stay, because a summary, a list and an example answer three different
questions.

Two decisions carry the design. A field is named by its **path** (`data[].attributes.name`), not by a
leaf name and a depth number, which is what makes the parent legible in plain text and to a screen
reader and makes the correctness check a plain set comparison. And the **envelope is described once, in
code**: roughly 160 of the 230 rows are the same wrapper repeated, so a small builder produces them and
each group file writes only the 70 rows that actually differ.

The correctness check is the other half of the feature and it was the author's explicit choice over a
cheaper option. The suite calls all 19 endpoints and compares the documented field names against the
field names the answers actually carry, in both directions, per endpoint. Its honest limit is that it
compares names and never meanings.

## Technical Context

**Language/Version**: PHP 8.4

**Primary Dependencies**: Laravel 12. No new dependency. The docs portal, its view models and its Blade
components already exist.

**Storage**: none. No migration, no model, no query. The reference is copy held in PHP files under
`resources/docs/api`.

**Testing**: PHPUnit, `php artisan test --compact`

**Target Platform**: Linux server, self hosted and hosted alike

**Project Type**: Server rendered Laravel web application with a versioned JSON API and a documentation
portal beside it

**Performance Goals**: none that change. The reference is read from disk by `glob` and holds no queries;
adding rows to it adds nothing a reader waits for.

**Constraints**: the field list renders in both themes, stays readable zoomed to 200%, and carries
nothing by colour alone. The plain text rendering has to carry the same information as the page.

**Scale/Scope**: 19 endpoints, of which 16 answer with a body. Roughly 230 documented rows, about 70 of
them written by hand. 8 reference files edited, 1 new application class, 1 new Blade component, 1 new
test class. Roughly 15 files touched.

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principle | Verdict | How this feature satisfies it |
| --- | --- | --- |
| I. Actions hold the business logic | **Pass** | No action, and that is correct: this performs no user operation. The one class added reads nothing and writes nothing, it assembles copy for one screen, which is what a view model is. It sits in `app/ViewModels/Docs/` beside the three already there. |
| II. Simplicity over abstraction | **Pass** | One extraction, justified by the project's own rule: the envelope builder is reused in five files and is a concept worth naming, and hand writing it 16 times is how the reference would come to say sixteen different things about one wrapper. No `depth` field, because it is derivable from the path and a second source of truth is a second thing to get wrong. |
| III. Tested before merged | **Pass** | Three test targets. The check that compares the reference against real answers, which is the feature's point. A test for the envelope builder, which is not optional because a mistake in it is a mistake in 16 places. A view test for the new Blade component, which is how every component in this project is tested. |
| IV. Laravel conventions are the default | **Pass** | Blade component with `@props`, view model under an existing namespace, PHPUnit with `#[Test]` and a `#[DataProvider]`, no new folder. The reference keeps the data driven shape it already has. |
| V. Localized and safe user experience | **Pass** | No new translated string: the reference is English copy on a surface that does not go through `__()`, and a field name is a JSON key. `monica:localize --check` runs to prove nothing went missing. Nothing destructive: the feature cannot write. |
| VI. Nothing changes under anybody's feet | **Pass** | Additive. No endpoint, field, address, status or route name changes. The summary and the example responses stay where they are, so no reader loses anything they were reading yesterday. |
| VII. A stable core, unlimited personalisation | **Not applicable** | Touches no template, page, section or field of the product's personalisation mechanism. |
| VIII. Nothing disappears silently | **Pass** | Deletes nothing. And it is worth naming the inverse: the check exists precisely so that a field disappearing from an answer cannot pass unnoticed. |
| IX. Accessible to everybody (NON-NEGOTIABLE) | **Pass, and it shaped the design** | This is why a field is named by its path. Indentation alone would leave a reader who cannot see it unable to tell a field of a listed record from a field of the answer. The type is a word rather than a colour. The block is text, so it adds no keyboard trap, and it inherits the portal's tokens so it reads in both themes and at 200%. |
| X. Everything goes through the API | **Pass** | The principle requires the API to be publicly documented. Today the reference says an endpoint exists and shows one example of what it answers; a caller has to infer the rest. This closes that, and it closes it in a way that cannot rot, which is the part that makes the documentation worth trusting. |

**Gate result: passes.** One deviation to declare, per Principle IV's requirement that any deviation be
justified in the pull request:

> The `api-docs-writer` skill, like `api-writer`, was written against a different codebase and describes
> a portal built from `App\Services\ApiDocumentation` with a `permissions` key per section. This project
> has `App\ViewModels\Docs\ApiDocsViewModel` and `ApiDocsSection`, and no such key. The full table of
> those differences was recorded in [the sent emails feature](../005-sent-emails-api/research.md)
> section 4 and is not repeated. `.claude/CLAUDE.md` ranks the code already there above the skill.

## Project Structure

### Documentation (this feature)

```text
specs/008-api-field-docs/
├── plan.md              # This file
├── spec.md              # Feature specification
├── research.md          # Phase 0 output
├── data-model.md        # Phase 1 output
├── quickstart.md        # Phase 1 output
├── contracts/           # Phase 1 output
│   └── return-fields.md
├── checklists/
│   └── requirements.md
└── tasks.md             # Phase 2 output (/speckit-tasks, NOT created by /speckit-plan)
```

### Source Code (repository root)

```text
app/ViewModels/Docs/
├── ApiDocsFields.php                         # new: builds the envelope rows for a record and a page
└── ApiDocsSection.php                        # edit: carry returnFields

resources/
├── docs/api/
│   ├── 02-health.php                         # edit: three flat rows
│   ├── 03-authentication.php                 # edit: token and expiry, and nothing for the sign out
│   ├── 04-profile.php                        # edit: 12 attributes, twice
│   ├── 05-api-keys.php                       # edit: 5 attributes, plus token beside the envelope
│   ├── 06-logs.php                           # edit: 7 attributes, one of them opaque
│   ├── 07-emails-sent.php                    # edit: 8 listed, 9 read singly
│   └── 08-vaults.php                         # edit: 8 attributes, four endpoints
└── views/
    ├── app/docs/api/markdown.blade.php       # edit: the rows as bullets under Returns
    └── components/api-docs/
        ├── endpoint.blade.php                # edit: call the new component
        └── returns.blade.php                 # new: the summary and the rows

tests/
├── Feature/Controllers/App/Docs/
│   └── ApiDocsReturnFieldsTest.php           # new: the check, 19 endpoints
├── Feature/Views/Components/ApiDocs/
│   └── ReturnsTest.php                       # new
└── Unit/ViewModels/Docs/
    ├── ApiDocsFieldsTest.php                 # new
    └── ApiDocsSectionTest.php                # edit or new: returnFields is read
```

`01-getting-started.php` is not edited: its sections are guide prose rather than endpoints, and the view
model already excludes them from anything compared against the routes.

No new top-level folder, so no constitutional amendment is needed. Every file lands in a folder the
Technology and Architecture Constraints section already names.

**Structure Decision**: the portal's existing shape is followed exactly. The reference stays data in PHP
files, the rendering stays Blade components under `components/api-docs`, and the assembling stays in
`app/ViewModels/Docs`. There is no frontend half to this feature and no second project.

## Implementation Notes

### Order of work

1. `ApiDocsSection` carries `returnFields`, and `ApiDocsFields` builds the envelope. Nothing renders yet.
2. The Blade component, and the Markdown bullets. Both render an empty list as nothing, so the portal is
   unchanged until a reference file has rows.
3. The check, written against **one** group of endpoints and watched to fail for a group with no rows
   yet. Writing the check before most of the reference is deliberate: it is what tells the person
   writing 70 descriptions whether they have missed a field, on every save.
4. The seven reference files, group by group, each one green before the next.
5. The component test, the builder test, and the pinned counts in the existing docs tests.

### The one thing that must not be relaxed

The check must be watched failing, three ways, before it is trusted: a field added to a resource, a row
removed from the reference, and a row invented in the reference. A check nobody has seen go red is a
check nobody knows is wired up. Step 2 of [quickstart.md](quickstart.md) is that exercise and the task
list makes it a task rather than a suggestion.

### The traps found while planning

- **A list whose page is empty proves nothing.** Every `data[]` row goes unverified and the check passes.
  It has to fail explicitly instead, naming the endpoint.
- **`data.attributes.action` on a log is a plain string, not one of a fixed set.** The model casts
  `parameters`, `changes` and `source`, and deliberately not `action`. A description that promises a
  fixed set here would be wrong.
- **Every moment in time in this API is a count of seconds**, not a written date. Sixteen rows will say
  so, and every one of them has to.
- **One field holds whatever an action recorded.** `data.attributes.parameters` on a log has different
  keys for different actions, so it is marked as not looked inside and the check stops there. It should
  be the only field in the reference carrying that marker, and that is worth asserting rather than
  assuming.

### What the check cannot do

It compares names. A description that is plausible and wrong passes. That limit is stated in the
contract, in the pull request, and in the checklist that governs how the 70 descriptions get written. It
is the reason step 7 of the quickstart exists.

## Complexity Tracking

*No entry required.* One class is extracted and the reason is in the constitution check and in
[research.md](research.md) section 3. Nothing else departs from the constitution.

## Constitution Re-check (post Phase 1 design)

Re-run after [data-model.md](data-model.md), [contracts/return-fields.md](contracts/return-fields.md)
and [quickstart.md](quickstart.md) were written. **Still passes**, with four things the design settled
that the first pass could only assume:

- **Principle IX decided the data model.** Naming a field by its path rather than by a leaf name and a
  depth number began as an accessibility requirement and turned out to be the right answer for two other
  reasons: the plain text rendering has no indentation to lean on, and the check becomes a set comparison
  at any depth. When the accessible answer is also the simplest one, it is worth saying so in the pull
  request.
- **The spec was wrong about depth and has been corrected.** It assumed one level of nesting; reading the
  resources showed every record is wrapped, so a value on a listed record is three levels down. The
  assumption in the spec now says so. Had it stood, the field list would have been designed for a shape
  the API does not have.
- **Principle II gained an extraction and lost a field.** The envelope builder is added, on the project's
  own rule about non-trivial logic reused in several places. In the same pass the `depth` number the
  design carries was dropped, because the path already determines it. One thing added, one removed, and
  the reference file gets simpler rather than richer.
- **Principle III gained a requirement the first pass did not see.** Two answers make the check
  degenerate rather than fail: an endpoint with no body, where both sets are legitimately empty, and a
  list with an empty page, where the check would compare nothing and pass. The second is a silent hole
  and is now an explicit failure with its own task.

No new dependency, no schema change, no new folder, and no change to what any endpoint returns. The
Complexity Tracking section stays empty.
