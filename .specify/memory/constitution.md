<!--
Sync Impact Report
Version change: 1.2.0 → 1.3.0
Rationale: `spec/spec.md` was read in full and the parts of it that govern every feature, rather
than describing one, are now here. Five principles are added, one is materially expanded, and one
section is new. Nothing is removed, renamed or renumbered, so this is a MINOR bump.

It is a large MINOR, and 2.0.0 was considered and rejected. The file until now governed only how
code is written; it now also governs what the product may and may not do. The new principles set a
bar that shipped code does not meet (there is no API surface at all, and no screen has been audited
against WCAG 2.2 AA). That is a backlog rather than an invalidated principle, and the Governance
section already says what to do when this file and the codebase disagree.

Integrated from `spec/spec.md`:
- 3.1 "Nothing changes under anybody's feet", plus section 6 on versioning and the install-is-a-copy
  rule from section 7, became Principle VI.
- 3.2 "The core stays stable", plus 5.2 on the single personalisation mechanism and 9.2 on the
  activity, became Principle VII.
- 3.3 "No silent deletion", plus the grace period from section 11, the append-only changelog from
  section 12 and the webhook deactivation from 14.4, became Principle VIII.
- 3.5 "Accessible to everybody", including the point that field types have to be accessible by
  construction because records are user-generated, became Principle IX.
- 3.6 "Everything goes through the API and can be exposed by MCP", plus the outbound half from
  section 14, became Principle X.
- 3.7 "Multilingual from day one" expanded Principle V, which previously covered only `__()`,
  `monica:localize` and the `lang/*.json` rule. Added: formats, currencies, plurals, name order,
  right to left, and the fact that user-created content stays in its author's language.
- 3.2 on mono-user, 4.1 to 4.4 on accounts, vaults, Me and private shares, 3.4 on deployment
  parity, the imprecise dates accepted in 8.2 and 9.4, and the one-entry-two-readings rule from 2.2
  and 9.1, became the new Product Invariants section.
- 3.8 on responsive then native, 3.9 on modules, 10.1 and 10.3 on reminders, section 11 on storage,
  section 12 on history, section 13 on search and 14.4 on webhook delivery went to Technology and
  Architecture Constraints.

Deliberately not integrated:
- The feature detail of sections 4 to 14 (the field type catalogue, relationship deduction and
  merging, the five reminder types, the marketplace author path, the webhook event list). Each
  describes one feature. Governance now names `spec/` as the source of truth for them, so they are
  referenced rather than copied.
- Section 15, the scope of the first version. Scope moves, and a constitution that pins it needs an
  amendment every time the plan changes.
- Section 16, the fifteen open questions. Governance points at that list instead of duplicating it,
  so there is one list and not two that can disagree.
- The module contract, which 3.9 itself says remains to be specified. What is here is the boundary a
  module may not cross, plus a gate: no module code before an amendment defines the contract.

Modified principles: none renamed, none removed, none renumbered. V expanded. VI to X added.

Modified sections:
- Product Invariants: new.
- Technology and Architecture Constraints: added storage, search, history, reminders, webhook
  delivery, modules and the native application horizon; the data scoping bullet now covers files and
  names the instance administrator capacity; the controllers bullet now treats a JSON API as
  required rather than hypothetical.
- Development Workflow and Quality Gates: accessibility, responsiveness and API coverage added to
  the definition of done.
- Governance: the relationship between this file and `spec/`, and the rule that the spec's open
  questions are answered in the spec and not by an implementation choice inside a pull request.

Follow-up TODOs, all re-verified against the working tree on 2026-08-21.

New, from this amendment:
1. `app/Actions/CreateApiKey.php` issues a Sanctum token with no abilities, while Principle X
   requires tokens whose scopes can be limited. The settings screen therefore offers a key that
   grants everything, and there is no API for it to reach. Either the scopes land or the screen
   promises something the product does not do.
2. There is no `Api` controller namespace, no `routes/api.php`, no MCP server and no webhook
   machinery. Principle X and the webhook constraints describe a target, and every capability
   shipped before they exist adds to that debt.
3. No screen has been audited against WCAG 2.2 AA. Principle IX binds every screen a change
   touches from now on; the 139 existing Blade files are unaudited.
4. Two of the spec's open questions are governed by principles here and must be answered in the
   spec before the features they concern are built: how sensitive values behave in the changelog
   (spec 12), and whether a webhook carries modified values (spec 14.5).

Still open from v1.1.0 and v1.2.0:
5. `database/migrations/2026_07_31_000001_create_magic_links_table.php` still defines `down()`,
   which Principle IV forbids for migrations this project writes.
6. `app/Actions/ResetPassword.php` still has no test.
7. Ten view models still have no test: the seven in `app/ViewModels/Auth` other than
   `RegisterViewModel`, `Settings/Account/Security/TwoFactorEnrolmentViewModel`,
   `Vaults/ContactEditViewModel` and `Vaults/SetAsideValuesViewModel`.
8. `.claude/CLAUDE.md` still states nine things this repository contradicts, and cannot be edited
   from this command: controllers "split into `App`, `Api` and `Marketing`" when only `App` exists;
   middleware "including the role gates"; `app/Http/Resources` as API transformers, when the folder
   does not exist and `app/Http/Requests` does and is unmentioned; view models that "ask the
   database nothing" plus four named marketing view models, none of which exists; `routes/api.php`
   and `routes/marketing.php`, neither of which exists; a `database/data` folder, when countries
   live in `app/Enums/CountryEnum.php`; a `tests/Browser` folder and "Pest browser tests", when Pest
   is not installed and `.claude/rules/testing.md` says PHPUnit; a frontend list omitting Turbo and
   instant.page, both of which ship; and `app/View/Components` described as "(app, guest)" when it
   holds `AccountLayout`, `AdminLayout`, `GuestLayout`, `MarketplaceLayout` and `Shell`.
-->

# Monica Constitution

## Core Principles

### I. Actions Hold the Business Logic

Every user-facing operation MUST live in a single class under `app/Actions`, one action per
operation. Controllers MUST stay thin: validate input, delegate to an action, return a response.
Jobs, commands and API controllers MUST reuse the same actions rather than re-implementing the
behavior. View models under `app/ViewModels` assemble what one screen shows. They read: from the
database, from configuration and from copy. They MUST NOT write, MUST NOT hold business logic
that belongs to an action, and MUST NOT be consumed anywhere other than Blade.

Rationale: with the logic in one named place per operation, the same rules apply whether the
request arrives from the web app, the JSON API, a queued job or an Artisan command, and each rule
has exactly one test target. A view model that only reads stays cheap to reason about, and a
screen that needs to change something reaches for the action every other surface uses.

### II. Simplicity Over Abstraction

Code MUST be written as simply as possible so any contributor can read it without tracing
indirection. Repetition of a few short, non business critical lines is preferred over extracting a
private helper. Extraction is justified only when the logic is non-trivial, reused in several
places, or is a concept worth naming. Comments MUST explain why something non-obvious is done,
never what the code does. The `final` keyword MUST NOT be used.

Rationale: this codebase is maintained by a small team and outside contributors. Premature
abstraction costs more in reading time than the duplication it removes.

### III. Tested Before Merged (NON-NEGOTIABLE)

Every model, controller, action, helper, job, mailable and view model MUST have a corresponding
test. No change ships without a test covering the behavior it adds or alters, and the full suite
MUST pass before merge. Tests MUST follow `.claude/rules/testing.md`: strict types declared,
namespaces mirroring the application, `#[Test]` attributes, snake_case names describing the
behavior, named arguments, and `RefreshDatabase` only when the database is touched. Test coverage
targets main functionality and important edge cases, not every line. Tests MUST NOT contain
comments, the method name carries the intent.

Rationale: tests are the only durable statement of what the product is supposed to do, and they
are what makes an action safe to reuse from a new surface.

### IV. Laravel Conventions Are the Default

Features MUST be built the way Laravel documents them. Any deviation MUST be justified in the pull
request that introduces it. `.claude/rules/code-style.md` is binding: one class, interface, trait
or enum per file with no exception including tests, typed properties and return types instead of
docblock annotations, array notation for validation rules, kebab-case public URLs and view files,
camelCase route names and parameters, early returns with the unhappy path first, and no `else`.
Migrations written by this project MUST define only `up()`; migrations published by the framework
or by a package are left as they ship. The `env()` helper MUST NOT be used outside `config/`.

Rationale: a predictable structure means a contributor who knows Laravel already knows this
codebase, and it keeps static analysis and tooling effective.

### V. Localized and Safe User Experience

Every user-facing string MUST be translatable through `__()`. Translations MUST be produced by
running `php artisan monica:localize` and translating everything it reports as missing, in every
configured locale, of which there are nine today. Keys in `lang/*.json` MUST NOT be edited by hand.
No text may be hardcoded in one language, and that covers what is easy to forget: date and number
formats, currencies, plurals, and the order of given name and family name, which varies by culture.
Screens MUST work in a right to left language. Content a user creates carries the language its
author wrote it in: it MUST NOT be machine translated or presented as though it were localized,
while everything the product ships with MUST be translated into every configured locale. Any
destructive action in the UI MUST warn the user before it is performed.

Rationale: the product ships in every configured locale at once, so an untranslated string is a
broken screen for part of the user base, and unconfirmed destruction is data loss.

### VI. Nothing Changes Under Anybody's Feet (NON-NEGOTIABLE)

No change made by another person, or by the system, may alter what somebody already sees without
them explicitly accepting it.

- A published version of a template or a section is frozen. A modification creates a new version
  beside it, with its own change note. Pages, page order and the placement of sections on pages are
  part of the frozen version. The rule cascades: renaming a value list option elsewhere in the
  product MUST NOT change the behavior of a version published yesterday. A draft, by contrast, stays
  freely editable, because freezing happens at publication and not at every micro-adjustment.
- A record points at a precise version and stays on it until somebody moves it. Nothing follows
  "the latest version" implicitly. Moving a record MUST snapshot the previous state first, and that
  snapshot MUST be restorable.
- An installed marketplace item is a copy. It MUST NOT update itself. The installer is told, reads
  the change note, and chooses whether to apply it or stay where they are.
- Changing a time zone, a preference or a default setting MUST NOT retroactively alter what is
  already fixed. A reminder rule freezes its reference zone when it is created.

Rationale: personalisation this deep is only safe if it cannot surprise anybody. A structure that
moves under a record is data loss the user never asked for, and a forced synchronisation is somebody
else editing your data.

### VII. A Stable Core, Unlimited Personalisation

Nine concepts are fixed and MUST NOT be made personalisable in their nature: Account, Vault,
Contact, Activity, Relationship, Group, the Me identity, Reminders and the Changelog. Fixed means
the concept and not its content. An Activity is always something that happened on a date, possibly
with people, while the list of natures and the fields each one carries is entirely configurable. A
Contact always carries a structured name, in all its components, while gender, date of birth and
description are ordinary configurable fields that happen to be supplied by default.

Everything else is built from one mechanism: Template, Page, Section, Field. A feature MUST NOT
introduce a second parallel way to attach user defined data, and personalisation MUST NOT require a
schema change: a field somebody invents at runtime is data, never a column.

Rationale: the product uses the core concepts transversally, for timelines, inactivity, search and
statistics, so they cannot be user defined without those features becoming unreliable. One
personalisation mechanism means there is one thing for a user to learn and one thing for us to test.

### VIII. Nothing Disappears Silently

When something stops applying, the product MUST deactivate it, explain why, and keep the way back
open. It MUST NOT make the data behind it disappear without saying so. A field removed from a
structure deactivates the values it held rather than dropping them. A reminder rule whose source
field is gone is deactivated and says why, instead of vanishing. A value list option is deactivated
and never deleted out from under the records using it. A deleted contact cancels its future
deadlines and gets them back if it is restored. A webhook subscription that keeps failing is
deactivated with a notification and a way to switch it back on. A file detached from a record MUST
survive a grace period long enough for a restored snapshot to recover it. The changelog is append
only and MUST NOT be modified or deleted except under an explicit retention policy.

Rationale: silent loss is the failure a user cannot detect, cannot report and cannot undo. Anything
we deactivate can be restored; anything we delete quietly is gone, and the user finds out later.

### IX. Accessible to Everybody (NON-NEGOTIABLE)

WCAG 2.2 AA is a delivery condition for every screen, not a pass at the end of a project. Every
screen a change touches MUST be operable with the keyboard alone, announced correctly by a screen
reader, free of information carried by colour alone, contrast compliant, focused visibly at all
times, readable zoomed to 200%, and free of animation when the system asks for reduced motion.

Because records are generated from structures users invent, accessibility MUST be built into each
field type once and for all, so that every template somebody creates renders accessibly without
anybody auditing it. The builder is in scope too: creating a template, navigating its pages and
moving sections between them MUST be doable with the keyboard and with a screen reader.

Rationale: you cannot make a generated screen accessible by hand. Either the field type produces an
accessible rendering by construction, or every template a user creates becomes a problem nobody can
fix on their behalf.

### X. Everything Goes Through the API

Everything that can be done in the interface MUST be doable through the API. Not only reading and
writing records: creating a template, adding and ordering pages, moving sections between them,
changing a value list, publishing to the marketplace, installing a structure, accepting an update,
managing relationships and reminders, uploading a file and reading the changelog. A capability is
not complete until its API coverage exists and is tested.

The API is publicly documented, versioned, authenticated by tokens whose scopes can be limited, and
rate limited on the hosted version. The MCP server exposes the API and MUST NOT bypass it or hold
authorisation rules of its own. Webhooks are the outbound counterpart: if an action exists, it must
be listenable from outside. No artificial intelligence is built into the product; users connect the
tool of their choice through MCP, the API or webhooks.

Rationale: the API is a permanent test of the product's completeness, it is what the native
applications will consume, and a capability that exists only inside a Blade screen is a capability
the rest of the product cannot reach.

## Product Invariants

These are facts about the shape of the product. A change MUST NOT bend them, and reversing one is
an amendment.

- Erica is mono-user. An account is one person and one sign in identity. There are no account roles,
  invitations, members, permissions between users, shared vaults or real time collaboration, and no
  change may reintroduce them. The instance administrator capacity is not a counterexample: it
  governs the instance, meaning moderation, accounts and storage, and never the contents of somebody
  else's vaults.
- A vault is a compartment, not a permission boundary. A contact, entity or activity belongs to
  exactly one vault, and moving data between vaults is an explicit, recorded action.
- Every account has exactly one Me contact. It is a real contact, carrying a template, fields,
  activities and relationships, but it MUST NOT be deleted or merged into another contact, and it
  MUST stay stably identifiable by the API, MCP, imports and modules.
- Sharing is a revocable read only link, never collaboration. A share is scoped to one record or one
  vault, protected by an unguessable token, optionally given an expiry and a password, and
  journalled. Sensitive fields, journal narratives, the modification history and technical data are
  excluded by default, and the default is closed: a new kind of content is outside shares until
  somebody decides otherwise.
- The journal and the directory are one gesture seen from two sides. Recording a fact once MUST fill
  both the day it happened and the records of the people involved. A feature MUST NOT ask the user to
  enter the same thing twice.
- Imprecision is a first class state. A date may be approximate, a year may be unknown, a period may
  have several beginnings and endings, and a relationship may be marked probable or uncertain. The
  product MUST NOT force a precision the user does not have, and MUST NOT invent one to satisfy a
  column.
- Human relationships are a capability of contacts in their own right, not a field type, and are
  deliberately not extended to custom entities. The link between a contact and their dog stays an
  ordinary relation field.
- Self hosted and hosted run the same product. Hosted differences are limited to quotas, rate
  limits, marketplace moderation and which modules are approved. There is no subscription and no
  notion of a plan; full access on the hosted version is a one time purchase. A feature MUST NOT
  exist on one deployment only.

## Technology and Architecture Constraints

- Backend: PHP 8.4 or later on the current major version of Laravel. Frontend: Blade, Tailwind
  CSS, Alpine.js, Alpine Ajax, Turbo and instant.page. Introducing a single page application
  framework, or replacing the server rendered Blade screens with a client rendered equivalent, is
  out of scope.
- The layout of the repository is fixed. New code goes into an existing folder. A new top-level
  folder, under `app/` or anywhere else described here, requires an amendment.
  - `app/`: `Actions` (one class per user operation, and where most of the application lives),
    `Console/Commands`, `Contracts`, `Enums`, `Exceptions`, `Helpers`, `Http` (holding
    `Controllers`, `Middleware` and `Requests`), `Interfaces`, `Jobs`, `Mail`, `Models`,
    `Providers`, `Rules`, `View/Components` (the layout components) and `ViewModels`.
  - `resources/`: `views` (`app` for the signed in screens, `components` for shared UI, `layouts`
    and `partials` for the shell, `mail` for emails, `shared` for the screens a private link
    opens), `css` for the Tailwind theme and `js` for the Alpine setup.
  - `routes/`: `web.php` for the signed in application, `auth.php` for the credentials screens,
    `console.php` for scheduled work.
  - `database/`: `migrations`, `factories` and `seeders`.
  - `lang/`: one JSON file per locale, written by `php artisan monica:localize` and never by hand.
  - `tests/`: `Unit` mirroring the application namespace, `Feature` for controllers and views, and
    `Fixtures` for any class a test needs. The suite runs on PHPUnit. Pest is not installed, and
    adding it is an amendment.
  - `spec/`: the written product specification. It holds no code.
- `app/Http/Controllers` holds one namespace today, `App`, for the signed in application. Principle
  X makes a JSON API part of the product rather than a possibility, so `Api` gets its own namespace
  beside it, its own route file and its own request and resource classes when it is built, as would
  a public marketing site under `Marketing`. Until that day nothing is written as though those
  exist, and any capability added to `App` MUST keep its logic in an action so that the API
  controller, when it arrives, adds nothing but transport.
- Data access MUST be scoped, and the compartmentalisation is by account, then by vault. An action
  reached by a user who is not in the account, or not in the vault, that holds the record MUST fail
  as if the record did not exist, and that failure MUST be covered by a test. The same applies to
  every surface added later, the JSON API included. The product has no concept of a role inside a
  vault; if one is introduced, it falls under this same rule.
- Files are compartmentalised the same way. No public path, file name or predictable identifier may
  let the separation be crossed, and downloads, previews, queued processing, exports, modules and
  private links MUST all apply it. Storage goes through the filesystem provider configured in
  Laravel: no code may assume a local disk or a particular cloud service, and attaching, detaching,
  restoring, computing a quota, detecting orphans and deleting after the grace period MUST behave
  identically whatever the driver.
- History is two mechanisms with two purposes, and they MUST NOT be collapsed into one. A record
  snapshot captures the complete state of a record at a moment, notably before a structure change,
  and exists so somebody can go back. The changelog answers who changed what, when, from which
  value and from which interface, across every object and not only records.
- A reminder has four distinct parts that MUST stay separate: the source it comes from, the rule
  that computes it, the occurrence that falls due, and the notification that reaches the user.
  Postponing, skipping, completing and deactivating the rule are four different actions with four
  different effects. Occurrence times are local, not absolute.
- Search MUST cover fields a user invented yesterday, with no configuration on their part, both as
  free tolerant search and as structured filtering. Indexing is asynchronous: saving a record stays
  instantaneous and the index catches up behind it.
- Webhook delivery is a background concern. A slow or broken endpoint MUST NOT slow down a save.
  Sends retry with spacing before being abandoned, every event carries a unique identifier so a
  recipient can recognise a duplicate, the send log is consultable by the user who owns the
  subscription, and repeated failure deactivates the subscription with an explanation.
- The community module system extends the product only through explicitly provided extension points.
  A module MUST NOT bypass the API, accessibility, compartmentalisation, history or update
  compatibility. The hosted version runs only modules we approve and operate. The contract, meaning
  format, lifecycle, permissions, compatibility and distribution, is not specified yet, so no module
  code lands before an amendment defines it.
- The first release is the responsive web application, genuinely usable on desktop, tablet and
  phone. Native iOS in Swift and Android in Kotlin come later, and they shape the work now: the API
  has to be complete, push notifications have to exist on the server side, and quick capture is a
  use case of its own rather than a shrunken desktop screen.
- The suite MUST pass on SQLite, MySQL and PostgreSQL. Database-specific SQL is therefore not
  allowed unless it is guarded per driver.
- Static analysis runs through Larastan at PHPStan level 5 over `app/`, `routes/` and `database/`.
  Findings are fixed, not ignored, unless the ignore is justified in the pull request.

## Development Workflow and Quality Gates

- Every task starts on a new branch cut from an up-to-date `main`, named `YYYY-MM-DD-{name}`.
- Commits and pull request titles MUST follow Conventional Commits, and the subject MUST read as
  one short sentence naming the outcome, not the mechanics. Write
  `feat: delete last vault in account`, not `feat: allow deleting the last vault of an account`.
  Write `feat: better header buttons`, not
  `feat: draw the top bar controls so they read against the bar`.
- Documentation, README files and any prose this project writes MUST NOT use dashes as
  punctuation, neither the em dash nor the hyphen. Rephrase with a period, a comma or parentheses.
- Claude Code MUST NOT be mentioned in pull request descriptions, pull request comments or issue
  comments.
- Before a change is considered done: tests exist and pass, `php artisan monica:localize` has been
  run and the reported strings translated in every locale, Pint formatting is clean, and PHPStan
  reports nothing new.
- Also before a change is considered done, for any screen it touches: the screen is operable with
  the keyboard alone and with a screen reader, no information is carried by colour alone, focus is
  visible, it stays readable zoomed to 200%, and it works on desktop, tablet and phone. A reviewer
  who cannot tell MUST ask rather than assume.
- A change that adds a capability states where its API coverage is. While there is no `Api`
  namespace, the answer is that the logic sits in an action and the controller adds nothing but
  transport, so the API can be added later without rework.
- CI is the gate: `semantic.yml` checks the pull request title, `static.yml` runs Pint and PHPStan,
  and `tests.yml` runs the suite across the three database connections. A red pipeline blocks
  merge.
- When an implementation is unclear, ask for clarification before writing code rather than guessing
  at intent.

## Governance

This constitution supersedes other conventions and habits. Where it conflicts with a comment, a
past pull request or an older document, this file wins. Where it conflicts with what the codebase
demonstrably does, that is a defect in one of the two, and the pull request that discovers it MUST
say which and fix it rather than leave both standing.

`spec/` is the product's source of truth for what Erica is and what it does: `spec/spec.md` for the
product itself, and the numbered files in `spec/models/` for each object's rules. This file governs
how the product is built and which of the spec's rules are non-negotiable. Where the two touch the
same subject, the spec decides what the product does and this file decides how it is built, and
neither may be contradicted silently. The open questions the spec lists MUST be answered in the
spec before code depends on them: an implementation choice buried in a pull request is not an answer
to a question the product has not settled.

Amendments are proposed as a pull request that changes this file, states what changed and why, and
lists any code or documentation that must follow. An amendment is adopted when that pull request
is merged. Guidance that is operational rather than governing (naming details, test helpers, style
minutiae, and how an assistant should converse) belongs in `.claude/CLAUDE.md` and
`.claude/rules/`, which are read at development time and MUST stay consistent with the principles
above. The layout of the repository is governed here and nowhere else: `.claude/CLAUDE.md` MUST
point at this file rather than describe the tree a second time, because two descriptions of one
tree is how the first one went stale.

Versioning follows semantic versioning. MAJOR for removing or redefining a principle in a way that
invalidates existing practice, MINOR for a new principle or materially expanded guidance, PATCH for
clarifications and wording that change no obligation.

Compliance is reviewed at pull request time. A reviewer MUST be able to point at the action, the
test and the translations for the change under review, and to say which of the ten principles the
change had to satisfy. Complexity that steps outside these principles MUST be justified in the pull
request or removed.

**Version**: 1.3.0 | **Ratified**: 2026-08-20 | **Last Amended**: 2026-08-21
