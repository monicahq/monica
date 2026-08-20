<!--
Sync Impact Report
Version change: none (unpopulated template) → 1.0.0
Rationale: first ratified constitution, all placeholder tokens replaced with project rules.

Modified principles:
- [PRINCIPLE_1_NAME] → I. Actions Hold the Business Logic
- [PRINCIPLE_2_NAME] → II. Simplicity Over Abstraction
- [PRINCIPLE_3_NAME] → III. Tested Before Merged (NON-NEGOTIABLE)
- [PRINCIPLE_4_NAME] → IV. Laravel Conventions Are the Default
- [PRINCIPLE_5_NAME] → V. Localized and Safe User Experience

Added sections:
- Technology and Architecture Constraints (was [SECTION_2_NAME])
- Development Workflow and Quality Gates (was [SECTION_3_NAME])
- Governance (rules populated)

Removed sections: none.

Follow-up TODOs: none. Values were derived from `.claude/CLAUDE.md`,
`.claude/rules/code-style.md`, `.claude/rules/testing.md`, `composer.json`,
`phpstan.neon`, `pint.json` and `.github/workflows/`.
-->

# Monica Constitution

## Core Principles

### I. Actions Hold the Business Logic

Every user-facing operation MUST live in a single class under `app/Actions`, one action per
operation. Controllers MUST stay thin: validate input, delegate to an action, return a response.
Jobs, commands and API controllers MUST reuse the same actions rather than re-implementing the
behavior. View models under `app/ViewModels` assemble what one screen shows from copy,
configuration and URLs, they MUST NOT query the database, read files, or be consumed anywhere
other than Blade.

Rationale: with the logic in one named place per operation, the same rules apply whether the
request arrives from the web app, the JSON API, a queued job or an Artisan command, and each rule
has exactly one test target.

### II. Simplicity Over Abstraction

Code MUST be written as simply as possible so any contributor can read it without tracing
indirection. Repetition of a few short, non business critical lines is preferred over extracting a
private helper. Extraction is justified only when the logic is non-trivial, reused in several
places, or is a concept worth naming. Comments MUST explain why something non-obvious is done,
never what the code does. The `final` keyword MUST NOT be used.

Rationale: this codebase is maintained by a small team and outside contributors. Premature
abstraction costs more in reading time than the duplication it removes.

### III. Tested Before Merged (NON-NEGOTIABLE)

Every model, controller, action, helper, job, mailable, service and view model MUST have a
corresponding test. No change ships without a test covering the behavior it adds or alters, and
the full suite MUST pass before merge. Tests MUST follow `.claude/rules/testing.md`: strict types
declared, namespaces mirroring the application, `#[Test]` attributes, snake_case names describing
the behavior, named arguments, and `RefreshDatabase` only when the database is touched. Test
coverage targets main functionality and important edge cases, not every line. Tests MUST NOT
contain comments, the method name carries the intent.

Rationale: tests are the only durable statement of what the product is supposed to do, and they
are what makes an action safe to reuse from a new surface.

### IV. Laravel Conventions Are the Default

Features MUST be built the way Laravel documents them. Any deviation MUST be justified in the pull
request that introduces it. `.claude/rules/code-style.md` is binding: one class, interface, trait
or enum per file with no exception including tests, typed properties and return types instead of
docblock annotations, array notation for validation rules, kebab-case public URLs and view files,
camelCase route names and parameters, early returns with the unhappy path first, and no `else`.
Migrations MUST define only `up()`. The `env()` helper MUST NOT be used outside `config/`.

Rationale: a predictable structure means a contributor who knows Laravel already knows this
codebase, and it keeps static analysis and tooling effective.

### V. Localized and Safe User Experience

Every user-facing string MUST be translatable through `__()`. Translations MUST be produced by
running `php artisan monica:localize` and translating everything it reports as missing, in every
configured locale. Keys in `lang/*.json` MUST NOT be edited by hand. Any destructive action in the
UI MUST warn the user before it is performed.

Rationale: the product ships in every configured locale at once, so an untranslated string is a
broken screen for part of the user base, and unconfirmed destruction is data loss.

## Technology and Architecture Constraints

- Backend: PHP 8.4 or later on the current major version of Laravel. Frontend: Blade, Tailwind
  CSS, Alpine Ajax and Alpine.js. Introducing another frontend framework or a client-side router
  is out of scope.
- The application layout under `app/` (`Actions`, `Models`, `Http/Controllers` split into `App`,
  `Api` and `Marketing`, `Http/Middleware`, `Http/Resources`, `Jobs`, `Mail`, `Enums`, `Helpers`,
  `ViewModels`, `View/Components`) is fixed. New code goes into an existing folder, a new
  top-level folder requires an amendment.
- Data access MUST be scoped. An action reached by a user who is not in the vault, or who lacks
  the required role, MUST fail as if the record did not exist, and that failure MUST be covered by
  a test. The same applies to the JSON API.
- The suite MUST pass on SQLite, MySQL and PostgreSQL. Database-specific SQL is therefore not
  allowed unless it is guarded per driver.
- Static analysis runs at PHPStan level 5 over `app/`, `routes/` and `database/`. Findings are
  fixed, not ignored, unless the ignore is justified in the pull request.

## Development Workflow and Quality Gates

- Every task starts on a new branch cut from an up-to-date `main`, named `YYYY-MM-DD-{name}`.
- Commits and pull request titles MUST follow Conventional Commits, and the subject MUST read as
  one short sentence (`feat: delete last vault in account`, not a description of the mechanics).
- Claude Code MUST NOT be mentioned in pull request descriptions, pull request comments or issue
  comments.
- Before a change is considered done: tests exist and pass, `php artisan monica:localize` has been
  run and the reported strings translated in every locale, Pint formatting is clean, and PHPStan
  reports nothing new.
- CI (semantic title check, static analysis, tests across the database matrix) is the gate. A red
  pipeline blocks merge.
- When an implementation is unclear, ask for clarification before writing code rather than guessing
  at intent.

## Governance

This constitution supersedes other conventions and habits. Where it conflicts with a comment, a
past pull request or an older document, this file wins.

Amendments are proposed as a pull request that changes this file, states what changed and why, and
lists any code or documentation that must follow. An amendment is adopted when that pull request
is merged. Guidance that is operational rather than governing (naming details, test helpers, style
minutiae) belongs in `.claude/CLAUDE.md` and `.claude/rules/`, which are read at development time
and MUST stay consistent with the principles above.

Versioning follows semantic versioning. MAJOR for removing or redefining a principle in a way that
invalidates existing practice, MINOR for a new principle or materially expanded guidance, PATCH for
clarifications and wording that change no obligation.

Compliance is reviewed at pull request time. A reviewer MUST be able to point at the action, the
test and the translations for the change under review. Complexity that steps outside these
principles MUST be justified in the pull request or removed.

**Version**: 1.0.0 | **Ratified**: 2026-08-20 | **Last Amended**: 2026-08-20
