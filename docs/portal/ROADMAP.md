# Documentation portal roadmap

This roadmap plans the product documentation for Monica: which pages to write, for whom, in what order,
and on what evidence. It is a plan, not the documentation. It replaces the roadmap of 2026-08-06, which
described an earlier product (an HR tool with many equal users per account, no API and no billing) and
must not be used as a source for anything.

Writers build pages from the briefs in [Page briefs](#page-briefs), following `.claude/skills/writer/SKILL.md`.
Where this roadmap and the writer skill disagree, see [Writer skill conflicts](#writer-skill-conflicts).

---

## Scope and evidence

### Audit record

| Item | Value |
| --- | --- |
| Audit date | 2026-09-29 |
| Revision | `ba888b57` on branch `2026-09-27-doc` (main plus documentation tooling commits) |
| Working tree | Clean. `vendor/` and a local `.env` were created for inspection only; both are ignored by git |
| Scope | Whole portal: end users, instance operators, integration developers, contributors |
| Release applicability | Nothing here is known to be released. `config/monica.php` reports version `3.0.0`, but there are no git tags, no changelog and no release workflow. Treat every capability as "in the current code", not "shipped" |

**Verification performed.** Static inspection of routes, controllers, actions, view models, views,
policies, jobs, notifications, enums, configuration, `lang/en.json`, migrations, seeders and test names.
Four parallel audits (everyday features; access, settings and billing; connected tools; operating an
instance) were each spot checked against the source before being used. Claims marked **confirmed in
code** below were read in the source by the author of this roadmap.

**Verification not performed.** No test was run and no screen was opened. PHP became unavailable on the
audit machine partway through, so `php artisan route:list`, the test suite and the application could not
run. Every test file named in this roadmap is evidence of intent, not a passing check. Every UI label
comes from `lang/en.json` or Blade source and must be checked on screen before publication.

### Audiences

| Audience | Who | Enters from | Primary needs |
| --- | --- | --- | --- |
| New user | Just signed up on monicahq.com or on an instance somebody runs | The welcome letter, the `/docs` landing page, search engines | First success: understand vaults and contacts, record a first person, set a first reminder |
| Returning user | Uses Monica weekly | In-app help links, search | A task done, an exact answer (a limit, a setting, what an email means) |
| Hosted customer | Uses monicahq.com | Billing screen, limit messages | What the free offer holds, what full access changes, cancelling |
| Instance operator | Installs and runs an instance, often also its administrator | GitHub README, `/docs` | Install, configure, schedule, back up, upgrade, administer |
| Integration developer | Scripts, assistants, phone sync | `/docs/api`, `/docs/mcp`, `/docs/carddav` (all exist) | Exact reference, auth, limits |
| Contributor | Wants to translate or send code | GitHub | Set up, conventions, where to start |

There is one person per account. Monica has no roles, members or invitations
(`database/migrations/0001_01_01_000000_create_users_table.php`, `users.account_id` unique; constitution,
Product Invariants). The only elevated capacity is the **instance administrator**, who governs the
instance (accounts, storage, moderation, configuration) and never sees vault contents
(`specs/001-admin-space/spec.md`). No page may describe collaboration, members, teammates or shared vaults.

### Deployment assumptions

Hosted and self hosted run the same code. The differences, all driven by `MONICA_SELF_HOSTED`
(`config/monica.php`), are:

| Behaviour | Hosted (`self_hosted=false`) | Self hosted (`self_hosted=true`) |
| --- | --- | --- |
| Free offer caps | 1 vault, 30 contacts, 100 MB storage by default (`Account::isMetered`, `VaultPolicy`, `ContactPolicy`, `CreateFile`) | None |
| Billing screen and menu entry | Shown | Menu entry hidden, screen says the instance is self hosted |
| MCP rate limit | 60 per minute | None (`AppServiceProvider::configureRateLimiting`) |
| API rate limit | 60 per minute per user | Same |

An instance administrator can change most `monica.*` values at runtime from `/admin/configuration`, and
a stored value overrides the environment (`InstanceSettingEnum`, `AppServiceProvider::applyInstanceSettings`).
**Consequence for every user page:** write numbers as defaults ("by default, five minutes"), and prefer
pointing at the number the screen or email itself states. Several screens already print the configured
value (`Remember me for :count days`, the magic link email).

### Sources inspected

- Instructions: `.claude/CLAUDE.md`, `.claude/rules/*`, `.claude/skills/writer/SKILL.md`,
  `.claude/skills/writer-analyst/SKILL.md`, `.specify/memory/constitution.md`.
- Product: `routes/{web,auth,api,administrator,ai,console}.php`, `app/Http/Controllers/**`, `app/Actions`,
  `app/ViewModels`, `app/Policies`, `app/Jobs`, `app/Notifications`, `app/Enums`, `app/Mcp`, `app/Http/Dav`,
  `app/Console/Commands`, `config/{monica,localization,services,passkeys,turnstile}.php`, `.env.example`,
  `database/seeders`, `lang/en.json`, `resources/views/app/**`, `resources/views/shared/**`.
- Existing documentation: `resources/docs/api/*.php`, `app/ViewModels/Docs/*`, `resources/views/app/docs/**`,
  `docs/FEATURES.md`, `docs/STRIPE.md`, `README.md`, `specs/*` (intent only).
- Operations: `Dockerfile`, `compose.yaml`, `docker-entrypoint.sh`, `.env.docker`, `composer.json` scripts,
  `.github/workflows/*`.

### Shared product facts

Briefs reference these instead of repeating them. Defaults are from `config/monica.php` and code; an
asterisk means an administrator can change the value from the admin space.

**The model.** An account holds one user. A user signs in; the account is created with a vault named
**Personal** (`CreateAccount::createFirstVault`) and, on the `high` queue, seeded with value lists,
8 life event categories, 40 life event types, relationship categories and types, and the **Basic
contact** and **Basic animal** templates, which also create the **Animals** profile type
(`CreateAccount::seedTheAccount`). A **vault** is a compartment, not a permission boundary. A **contact**
lives in exactly one vault. A **profile** (code: `Entity`) is a non-person record such as a pet, of a
**profile type** (code: `EntityType`). Every contact and profile follows a **template**, whose published
**versions** are frozen; a record stays on its version until the user applies a newer one
(constitution VI; `MoveRecordToStructureVersion`, which snapshots first).

**Vocabulary (UI label, code name).** Vault (`Vault`); contact, "Add somebody" (`Contact`); partial contact
(`is_partial`, filter `names-only`); Favourites, Archived (`ContactGroupingEnum`); template (`Template`,
`Structure`, `StructureVersion` Draft / Published / Retired); page (`StructureVersionPage`); section
(`Section`); field (`Field`, `FieldValue`, `FieldValueRow` for repeater rows); "Kinds of profile", profile
type (`EntityType`); profile (`Entity`); "Answers to questions no longer asked" (set aside values); merge
(`MergeContacts`, `ContactMerge`); sharing link (`ShareLink`, `/s/{token}`); life event (`LifeEvent`);
relationship (`Relationship`); reminder (`Reminder`) and its dates (`ReminderOccurrence`); "I remembered",
"I forgot" (`ReminderAnswerEnum`); full access, free offer (`Account::hasFullAccess`, `isOnFreeOffer`).
The **Me contact** exists in the model and the API but has no screen that creates it (discrepancy D10).

**Numbers.**

| Fact | Default | Where |
| --- | --- | --- |
| Minimum password length | 8 on every form | `Password::min(8)` in controllers (the stricter production default in `AppServiceProvider` is never applied, D17) |
| Remember me | 30 days* | `remember_duration_days` |
| Sign in link validity, use | 5 minutes*, once | `magic_link_duration_minutes`; can be switched off* (`magic_link_enabled`) |
| Email confirmation link | 60 minutes | `auth.verification.expire` |
| Password reset link | 60 minutes, one request per 60 seconds | `config/auth.php` |
| Failed sign ins before lockout | 5 per address and IP | `AttemptLogin::MAX_ATTEMPTS` |
| Request limits on link, reset, confirmation resend, two factor challenge | 6 per minute | `routes/auth.php` |
| Recovery codes | 8 codes of 10 characters, each once | `app/Helpers/RecoveryCodes.php` |
| Password confirmation lasts | 3 hours | `AUTH_PASSWORD_TIMEOUT` |
| Profile or contact photo | JPEG, PNG, WebP, up to 5 MB | profile and avatar controllers |
| Largest file | 10 MB* | `max_file_size_in_kilobytes` |
| Free offer (hosted) | 1 vault*, 30 contacts* (Me and partial contacts not counted), 100 MB* stamped at account creation | `free_*` keys |
| Full access storage (hosted) | 10 GB* | `paid_storage_limit_in_bytes` |
| Reminder dates generated ahead | 90 days* | `reminder_generation_window_in_days` |
| Notification delivery | every 5 minutes, 3 attempts* | `routes/console.php`, `notification_maximum_attempts` |
| Channel switched off after | 5 failures* | `notification_channel_failure_threshold` |
| Channel proof code | 6 digits, 10 minutes, 5 tries, 3 sends | `notification_proof_code_*` |
| In-app notifications kept | 12 months* | `notification_retention_months` |
| Detached files kept | 30 days* | `orphaned_file_grace_period_in_days` |
| API and CardDAV rate limit | 60 per minute per user, shared | `RateLimiter::for('api')` |
| API page size | 25 default, 100 maximum* | `api_*_items_per_page` |
| Sharing link expiry choices | 7 days, 30 days, 3 months, never | share link form |
| Interface languages | 31 locales | `config/localization.php`, one `lang/*.json` each |

---

## Coverage and gaps

### Coverage matrix

Status uses the analyst scale: implemented, partial, planned, unknown. Coverage today is "none" for every
user and operator topic, because no user guide exists. Priority: P0 first publication, P1 second wave,
P2 later.

| Reader goal or capability | Status | Evidence | Existing coverage | Proposed pages | Priority |
| --- | --- | --- | --- | --- | --- |
| Understand what Monica is and how it is organised | implemented | constitution, `CreateAccount` | `/docs` card text only (inaccurate, D1) | `portal.introduction`, `concepts.model` | P0 |
| First success: record a first person | implemented | `CreateContact`, `ContactController` | none | `start.first-contact` | P0 |
| Create account, confirm address | implemented | `RegistrationController`, `EmailVerificationController` | none | `start.create-account` | P0 |
| Sign in: password, link, passkey, outside provider | implemented | `routes/auth.php` | none | `signin.*` | P0 |
| Reset a forgotten password | implemented, **suspected broken** | `PasswordResetLinkController` (D2) | none | `signin.forgot-password` | P0, blocked |
| Vaults: create, default, default template, delete | implemented | `VaultController`, `DestroyVault` | none | `vaults.*` | P0 |
| Contacts: add, edit sections, name, photo, favourite, delete | implemented | `app/Http/Controllers/App/Vaults/Contact*` | none | `contacts.*` | P0 |
| Contact list: filter, sort, search, columns | implemented | `ContactListColumnController`, `Contact::scopeMatchingSearch` | none | `contacts.list` | P1 |
| Archive and restore contacts | not offered (the Archived filter was removed in #293) | `ArchiveContact` unused (D6), no restore (D5) | none | gap noted in `contacts.delete` | none |
| Relationships | implemented | `ContactRelationshipController` | none | `relationships.*` | P0 |
| Life events and timeline | implemented | `ContactLifeEventController`, `RestoreLifeEvent` | none | `life-events.*` | P1 |
| Reminders: create, answer, overview | implemented | `ContactReminderController`, `VaultReminderController` | none | `reminders.*` | P0 |
| Notification channels: email, in app, Telegram, text message | implemented (Telegram, SMS depend on instance) | `NotificationChannelController` | none | `notifications.*` | P1 |
| Notification preferences (quiet hours, digest) | partial (API only) | `api/v1/notification-preferences` | API reference | mention in `notifications.channels`; no web page | P2 |
| Duplicates, merge, undo | implemented | `DetectDuplicateContacts`, `MergeContacts`, `UndoContactMerge` | none | `duplicates.*` | P1 |
| Sharing links | implemented (contacts only) | `ContactShareLinkController` | none | `sharing.*` | P1 |
| vCard export of one contact | implemented | `ExportContactVCard` | none | `export.vcard` | P1 |
| Export everything | planned (`SettingsViewModel` url null) | D11 | none | gap noted in `export.vcard` | none |
| Import contacts from a file | planned on web, implemented only via CardDAV | `ImportVCard` via `AddressBookBackend` (D4) | none | gap noted; CardDAV guide owns device sync | none |
| Phone and computer sync (CardDAV, CalDAV) | implemented | `app/Http/Dav`, `DavServiceProvider` | `/docs/carddav` (good) | link only; `integrations.overview` | P1 |
| Templates: copy, build, publish, apply to contacts | implemented | `settings.templates.*`, `PublishStructureVersion` | `/docs/mcp` explains drafts for assistants only | `templates.*` | P1 |
| Field types | implemented (21), picker lists ~10 unavailable | `FieldTypeEnum`, `FieldCatalogueViewModel` (D9) | none | `reference.field-types` | P1 |
| Profile types and profiles | partial (add and list only in web) | `EntityController` | none | `profiles.*` | P2 |
| Relationship types, life event types, value lists | implemented | settings controllers | MCP docs only | `customise.*` | P2 |
| Marketplace | partial (browse only; no install; publishing closed) | `MarketplaceController`, template share page copy | none | `marketplace.overview` | P2 |
| Welcome letter | implemented; the getting started screen was removed (#276) | `HomeController` (D3) | none | covered by `start.first-contact` | none |
| In-app notifications (bell) | implemented | `NotificationController` | none | `notifications.inbox` | P1 |
| Preferences: language, formats, time zone | implemented | `PreferenceController`, `TimezoneController` | none | `account.preferences` | P1 |
| Profile: name, email, photo | implemented; email change sends no confirmation automatically (D12) | `Profile/*` | none | `account.profile` | P1 |
| Two factor, recovery codes, passkeys, password, outside providers | implemented | `SecurityController` and friends | none | `security.*` | P0 |
| API keys, connected assistants | implemented | `ApiKeyController`, `ConnectedAssistantController` | API reference covers keys; `/docs/mcp` covers assistant revocation after #293 | `security.api-keys`, `security.assistants` | P1 |
| Logs and emails sent | implemented | `Logs/*`, `UserActionEnum` | none | `account.logs` | P1 |
| Security emails (what each means) | implemented | `app/Notifications/*` | none | `reference.emails`, `security.suspicious-activity` | P0 |
| Delete the account | implemented | `DestroyAccount` | none | `account.delete` | P1 |
| Free offer, full access, subscribe, cancel, invoices | implemented (hosted) | `Billing/*`, `OfferKindEnum` | `docs/STRIPE.md` (operator) | `billing.*` | P1, needs sources |
| API | implemented, v1 | `routes/api.php` | `/docs/api` complete by route; D13, D15, D16 fixed in #293 | revise existing | P0 corrections |
| MCP | implemented, OAuth and keys | `routes/ai.php`, `app/Mcp` | `/docs/mcp`, OAuth documented in #293 | revise existing | P0 corrections |
| Install an instance | implemented (Docker development setup, `composer setup`) | `Dockerfile`, `compose.yaml` | README partial and inaccurate (D18, D19) | `hosting.*` | P1, partly blocked |
| Configure an instance, admin configuration screen | implemented | `config/monica.php`, `InstanceSettingController` | none | `hosting.configuration`, `admin.configuration` | P1 |
| Workers, scheduler | implemented | `routes/console.php`, queue names | Dockerfile only | `hosting.background-work` | P1 |
| First administrator, admin space | implemented | `MakeAdministratorCommand`, `routes/administrator.php` | none | `admin.*` | P1 |
| Backups, restore | no tooling | none | none | `hosting.backups` (guidance) | P1 |
| Upgrades | no release process | none | none | `hosting.upgrade` | P2, blocked |
| Monitoring | implemented (`/up`, `/api/status`, Sentry) | `CheckDatabaseIsReady`, `StatusController` | API reference covers status | `hosting.monitoring` | P2 |
| Contributing, licence | AGPL-3.0-or-later; no CONTRIBUTING | `LICENSE.md`; README boilerplate (D19) | none | `contributing.overview` | P2, blocked |

### Exclusions

| Topic | Why it gets no page |
| --- | --- |
| Members, invitations, roles, shared vaults, collaboration | Do not exist and are forbidden by the constitution. `concepts.model` states the one person rule once |
| Journals, groups, tasks | No model exists. The vault dashboard shows empty Tasks, Reminders and Files cards (D7); do not document them |
| Global search, search in field values | Does not exist. `contacts.list` says search covers names and nicknames |
| Archiving, restoring a deleted contact, importing a file on the web, export everything, installing from the marketplace, publishing to the marketplace, a profile's own page | Not built. Each is stated as a limit on the page a reader would look for it |
| The 190 activity log actions | No reader need for an inventory; the log prints a sentence for each. `account.logs` explains how to read it |
| Screenshot link, local sign in shortcut | Development only |
| A standalone FAQ | Questions have natural homes in how-to, reference or troubleshooting |

### Discrepancies to resolve

These are contradictions between shipped copy, existing documentation and code. They are not
documentation work, but several block a page. The status column links the pull request that fixes
one or the issue that tracks it. Pull request #293 was open, not merged, when this was written.

| ID | Finding | Evidence | Blocks or affects | Status |
| --- | --- | --- | --- | --- |
| D1 | `/docs` "Using Monica" card promises "journals and sharing a vault with someone"; neither exists, and its link is null | `DocsViewModel::areas()` | Portal launch | #274 |
| D2 | Password reset email probably cannot be built: Laravel's default notification needs a `password.reset` route, the app names it `auth.password.edit` and sets no `createUrlUsing`. Tests fake notifications. Confirmed in code, not run | `routes/auth.php:65`, `PasswordResetLinkController` | `signin.forgot-password` | #275 |
| D3 | New accounts never see Getting started (a vault is created at sign up, and the screen shows only when there is none); its first step is "Create your first vault" | `HomeController`, `CreateAccount::createFirstVault` | `start.first-contact` routes readers to the vault instead | #276 |
| D4 | Getting started promises "Import a CSV or a vCard file"; no web import exists | `lang/en.json` line 610 | `start.first-contact` must not repeat it | #277 |
| D5 | Contact delete dialog says "they can be restored"; nothing restores a contact. Confirmed in code | `lang/en.json` line 1691, no route or action | `contacts.delete` | #278 |
| D6 | "Archived" filter exists, but `ArchiveContact` is called from nowhere. Confirmed in code | `app/Actions/ArchiveContact.php` | `contacts.list` | fixed in #293 (filter removed) |
| D7 | Vault dashboard Tasks, Reminders, Files cards are hard-coded placeholders, though reminders and files exist; only tasks are unbuilt; "Open tasks" always 0 | `VaultDashboardViewModel::unbuiltSections()` | `vaults.overview` screenshots | #279 |
| D8 | `CreateShareLink` docblock says a link can cover a whole vault; only contact links exist | `app/Actions/CreateShareLink.php` | `sharing.*` | #280 |
| D9 | Field type picker lists about 10 types as "Not available yet" | `FieldCatalogueViewModel` | `reference.field-types` lists only available types | #281 |
| D10 | The Me contact has no screen that creates it; API and MCP only read it | `CreateContact(isMe: true)` only in seeder | `concepts.model` | #282 |
| D11 | Settings home offers "Export everything" and a "Notifications" row with null links; the Notifications row describes reminder lead times that do not exist | `SettingsViewModel` | `export.vcard`, `notifications.channels` | #283 |
| D12 | Changing the sign in email unconfirms it but sends no confirmation email; the screen promises one | `UpdateUserProfile` | `account.profile` | #284 |
| D13 | API docs say "Sixty requests per minute, per token" in 134 places; the limiter counts per user. Confirmed in code | `resources/docs/api/*.php`, `AppServiceProvider` | `/docs/api` | fixed in #293 |
| D14 | MCP docs say OAuth is not offered and ChatGPT cannot connect; OAuth is implemented and tested (`McpOAuthTest`). Connected assistants revocation undocumented. Confirmed in code | `McpDocsViewModel` line 198 | `/docs/mcp`, `security.assistants` | fixed in #293 |
| D15 | API pagination guide says no endpoint returns a list; about 25 do. Confirmed in code | `resources/docs/api/01-getting-started.php:86` | `/docs/api` | fixed in #293 |
| D16 | OpenAPI document is cached forever although the docs say it is built on request | `OpenApiDocumentController`, `BuildOpenApiDocument` | `/docs/api` | fixed in #293 (cache removed) |
| D17 | Production password rule (12 characters, mixed case) is defined but no form uses it | `AppServiceProvider` | `reference.limits` states 8 | #285 |
| D18 | Docker setup defects: healthcheck polls port 8000 inside a container Apache serves on 80, Apache runs as a non-root user, cron written for `www-data`, redirections passed as literal arguments in the queue service, README says three services where compose has four. Confirmed in part in code | `compose.yaml`, `Dockerfile` | `hosting.install-docker` | #286 |
| D19 | README ends in Laravel boilerplate (MIT licence, Laravel security contact); the project is AGPL-3.0-or-later; no CONTRIBUTING or SECURITY file | `README.md:171-175`, `LICENSE.md` | `contributing.overview` | #287 |
| D20 | Remember me is dropped when two factor is on (challenge logs in without it). Confirmed in code | `TwoFactorChallengeController:51` | `signin.password` | fixed in #293 |
| D21 | A deactivated user's API keys, CardDAV and OAuth tokens are not checked for `is_active`. Confirmed in code (no check found) | `routes/api.php`, `routes/ai.php` | `admin.accounts` | fixed in #293 |
| D22 | Two factor screen says a code is asked "every time you sign in"; passkey sign in skips it | `SecurityController` view copy | `security.two-factor` | #288 |
| D23 | "You signed in without a password" email says "using a link sent by email" but is also sent for passkey and outside provider sign ins | `NewLoginDetected` | `reference.emails` | #289 |
| D24 | "Emails sent" empty state promises password change emails; a password change sends none, and the verification and reset emails are not recorded | `Logs/*` views, `VerifyEmail` lacks `HasEmailType` | `account.logs` | #290 |
| D25 | `composer dev` runs `queue:listen` without `--queue`, so `high` and `low` work is never processed with a non sync queue | `composer.json` | `hosting.background-work`, `contributing.overview` | #291 |
| D26 | Telegram, text message (Vonage) and Microsoft sign in packages are dev dependencies, so the production image cannot offer them | `composer.json` require-dev | `hosting.notification-channels`, `hosting.sign-in-options` | #292 |

### Hosted commitments that need an authoritative source

No page may state these from code or configuration. Each needs a written statement from whoever runs
monicahq.com. Until then the page links to the screen that shows the live value, or omits the claim.

| Claim | Where it appears | Needed for |
| --- | --- | --- |
| Prices, currencies, tax included | Stripe catalogue; billing screen says "every price includes tax" | `billing.full-access` |
| No refund on cancellation or deletion | Account deletion copy | `billing.cancel`, `account.delete` |
| "We keep no copy" after deletion (backups) | Account deletion copy | `account.delete` |
| Retention of logs and email copies (currently indefinite) | `app/Models/Log.php` | `account.logs`, `concepts.privacy` |
| "We never send marketing emails" | Registration screen | `reference.emails` |
| Free offer numbers on monicahq.com | Admin configurable | `billing.free-offer` |
| Terms and privacy policy | `MONICA_TERMS_URL`, `MONICA_PRIVACY_URL` | `concepts.privacy` |

---

## Information architecture

### Entry points

`/docs` already exists (`DocsController`, `DocsViewModel::areas()`) with four cards. Keep it as the front
door and change it as follows:

| Card | Today | Proposed |
| --- | --- | --- |
| Using Monica | No link; inaccurate description (D1) | Links to `portal.introduction`. Description: contacts, relationships, life events, reminders and templates |
| API | `/docs/api` | Unchanged (D13, D15, D16 corrected in #293) |
| MCP | `/docs/mcp` | Unchanged (D14 corrected in #293) |
| CardDAV and CalDAV | `/docs/carddav` | Unchanged |
| Run your own instance | Does not exist | New card to `hosting.introduction` |

Each audience enters differently:

- **New users** arrive from the welcome letter or the landing card and follow **Start here** in order.
- **Returning users** arrive from search, from in-app help links (portal work item F6) or from an email,
  and land directly on a how-to, a reference page or a troubleshooting page. No page requires them to
  have read Start here.
- **Hosted customers** arrive from a limit message or the billing screen at **Plans and billing**.
- **Operators** arrive at **Run your own instance** from the README.
- **Developers** keep the existing references; `integrations.overview` helps them choose.

### Navigation tree

Sections are grouped by what the reader is doing, not by Diátaxis type. The type of each page is in its
brief. Folder numbers follow the writer skill.

```
Using Monica (portal root)
1  portal.introduction                     Navigation
2  Start here
   1 start.introduction                    Navigation
   2 start.create-account                  How-to
   3 start.first-contact                   Tutorial
   4 concepts.model                        Explanation
3  People
   1 contacts.introduction                 Navigation
   2 contacts.add                          How-to
   3 contacts.edit                         How-to
   4 contacts.list                         How-to
   5 contacts.photo                        How-to
   6 contacts.delete                       How-to
   7 profiles.add                          How-to
4  Relationships and life events
   1 relationships.introduction            Navigation
   2 relationships.add                     How-to
   3 relationships.edit                    How-to
   4 life-events.record                    How-to
   5 life-events.timeline                  Explanation
5  Reminders and notifications
   1 reminders.introduction                Navigation
   2 reminders.create                      How-to
   3 reminders.answer                      How-to
   4 reminders.how-they-work               Explanation
   5 notifications.channels                How-to
   6 notifications.inbox                   How-to
6  Vaults
   1 vaults.introduction                   Navigation
   2 vaults.create                         How-to
   3 vaults.settings                       How-to
   4 vaults.delete                         How-to
7  Keeping records tidy
   1 duplicates.introduction               Navigation
   2 duplicates.review                     How-to
   3 duplicates.merge                      How-to
8  Sharing, export and sync
   1 sharing.introduction                  Navigation
   2 sharing.create-link                   How-to
   3 sharing.what-a-link-shows             Reference
   4 export.vcard                          How-to
   5 integrations.overview                 Explanation
9  Templates and customisation
   1 templates.introduction                Navigation
   2 templates.versions                    Explanation
   3 templates.build-your-own              Tutorial
   4 templates.change                      How-to
   5 templates.apply-new-version           How-to
   6 templates.import-export               How-to
   7 customise.relationship-types          How-to
   8 customise.life-event-types            How-to
   9 customise.profile-types               How-to
   10 customise.value-lists                How-to
   11 marketplace.overview                 Explanation
10 Your account and security
   1 account.introduction                  Navigation
   2 signin.password                       How-to
   3 signin.magic-link                     How-to
   4 signin.passkey                        How-to
   5 signin.provider                       How-to
   6 signin.forgot-password                How-to
   7 security.two-factor                   How-to
   8 security.recovery-codes               How-to
   9 security.passkeys                     How-to
   10 security.password                    How-to
   11 security.api-keys                    How-to
   12 security.assistants                  How-to
   13 security.suspicious-activity         How-to
   14 account.profile                      How-to
   15 account.preferences                  How-to
   16 account.logs                         How-to
   17 account.delete                       How-to
   18 concepts.privacy                     Explanation
11 Plans and billing (hosted)
   1 billing.introduction                  Navigation
   2 billing.free-offer                    Reference
   3 billing.full-access                   How-to
   4 billing.cancel                        How-to
12 Troubleshooting
   1 troubleshoot.introduction             Navigation
   2 troubleshoot.cannot-sign-in           How-to
   3 troubleshoot.no-email                 How-to
   4 troubleshoot.reminder-not-received    How-to
   5 troubleshoot.limit-reached            How-to
13 Reference
   1 reference.introduction                Navigation
   2 reference.glossary                    Reference
   3 reference.field-types                 Reference
   4 reference.emails                      Reference
   5 reference.limits                      Reference
14 Run your own instance
   1 hosting.introduction                  Navigation
   2 hosting.requirements                  Reference
   3 hosting.install-docker                How-to
   4 hosting.install-server                How-to
   5 hosting.background-work               How-to
   6 hosting.configuration                 Reference
   7 hosting.email                         How-to
   8 hosting.storage                       How-to
   9 hosting.sign-in-options               How-to
   10 hosting.notification-channels        How-to
   11 admin.first-administrator            How-to
   12 admin.overview                       Explanation
   13 admin.configuration                  How-to
   14 admin.accounts                       How-to
   15 admin.moderation                     How-to
   16 hosting.backups                      How-to
   17 hosting.upgrade                      How-to
   18 hosting.monitoring                   How-to
   19 contributing.overview                How-to
```

### Learning order and canonical ownership

Learning dependencies are acyclic: `start.create-account`, then `start.first-contact`, then
`concepts.model` as optional reading. Every other page lists at most one or two true prerequisites and
treats the rest as optional links.

| Subject | Canonical page | Others link, never repeat |
| --- | --- | --- |
| Account, vault, contact, profile, Me, one person rule | `concepts.model` | everything |
| Frozen template versions | `templates.versions` | `contacts.edit`, `templates.*`, `vaults.settings` |
| Every default number | `reference.limits` | every page that states a number states it once and links |
| What each email means | `reference.emails` | `security.suspicious-activity`, `troubleshoot.no-email` |
| Reminder timing, channels, digests | `reminders.how-they-work` | `reminders.*`, `notifications.*`, troubleshooting |
| Phone sync | `/docs/carddav` (existing) | `integrations.overview`, `security.api-keys` |
| API and MCP details | `/docs/api`, `/docs/mcp` (existing) | `security.api-keys`, `security.assistants` |
| Environment and admin setting precedence | `hosting.configuration` | `admin.configuration`, all hosting pages |

### Changes from the previous roadmap

No page from the previous roadmap was ever written, so there are no public URLs to redirect. Its ids were
proposals only. This roadmap reuses those whose meaning still holds so earlier drafts stay recognisable:
`portal.introduction`, `signin.password`, `signin.magic-link`, `signin.forgot-password`,
`security.two-factor`, `security.recovery-codes`, `security.password`, `security.api-keys`,
`security.suspicious-activity`, `account.delete`, `troubleshoot.*`, `reference.glossary`,
`reference.limits`, `hosting.introduction`, `hosting.configuration`, `hosting.email`, `hosting.backups`,
`hosting.upgrade`. Every other previous id is retired, including `concepts.permissions` (the flat
permission model it described never matched the current product) and `getting.what-works-today`.

### Paths, URLs and locales

- **Filesystem.** `docs/portal/en/` holds the English pages, using the writer skill's `N-` folder and file
  ordering beneath it. A locale folder is not in the writer skill today; see conflict W6. Paths in the
  briefs are provisional until the renderer (F1) exists, and nothing here assumes a frontmatter field
  beyond `id`, `title`, `slug` and `section`.
- **Public URLs.** `/docs/{locale}/{section}/{slug}`, matching the writer skill's `@doc` example. Locale
  codes never collide with the existing `/docs/api`, `/docs/mcp` and `/docs/carddav`.
- **Locales.** The interface ships 31 locales; the developer references are English only by design.
  Write English first. Do not translate until a section has been stable through one release. A page
  missing in the reader's locale falls back to English with a one line notice (F5).

---

## Portal functionality

Content work cannot publish without F1. The rest are ordered by reader impact.

| ID | Capability | Current support | Gap | Priority | Acceptance criterion |
| --- | --- | --- | --- | --- | --- |
| F1 | Render `docs/portal/{locale}` Markdown with frontmatter, `@doc(id)`, `:::note`, `:::warning`, `::::steps`, `:::step`, `::screenshot` | None. The directives exist only in the writer skill; `resources/views/components/docs/{step,steps,notice}` exist as Blade components for the MCP and CardDAV pages | Parser, routes, layout | P0 | Every directive in the writer skill renders; an unknown `@doc` id fails a test; duplicate ids fail a test |
| F2 | Navigation from folder order, section index pages, previous and next | None | Sidebar and index | P0 | Sidebar order matches `N-` prefixes; keyboard and screen reader operable (constitution IX) |
| F3 | Stable heading anchors | MCP and CardDAV pages have them | Portal pages | P0 | Every `##` heading has a stable id that survives retitling of other headings |
| F4 | Search with excerpts | API sidebar filter only (label and method) | Full text over portal pages | P1 | Searching "recovery code" returns `security.recovery-codes` first with an excerpt |
| F5 | Locale fallback notice | None | Fallback logic | P1 | A `fr_FR` reader of an untranslated page sees the English page and a notice |
| F6 | Help links from screens | None | A small component linking a screen to a page id | P1 | At least the reminders, templates, sharing and security screens link to their page |
| F7 | Applicability markers (hosted only, self hosted only, administrator only) | None | A convention inside the page body, not new frontmatter, unless the renderer adds a field deliberately | P1 | Every billing and admin page carries one |
| F8 | Report a problem with this page | None | Link to an issue form or a feedback endpoint | P2 | Each page footer links to it with the page id prefilled |
| F9 | `llms.txt` and Markdown views for portal pages | Exists for the developer references | Add portal pages | P2 | `llms.txt` lists every portal page |
| F10 | Last reviewed date | None | Must be written by a person at review, never generated from git | P2 | Date appears only when set in source |

Not recommended now: an assistant inside the portal, interactive sandboxes, a new publishing platform.
Nothing in the audit shows a need that F1 to F6 do not meet.

---

## Page briefs

Each brief carries the fields the analyst skill requires, compressed. **Identity** gives the id, working
title, provisional path under `docs/portal/en/`, and disposition (all are new). **Evidence** names the
source to recheck; a writer must recheck it, because the code moves. Shared facts are in
[Shared product facts](#shared-product-facts) and are not repeated.

Readiness: **ready** (static evidence and tests exist, behaviour not contested), **needs verification**
(a runtime check or a decision is required first), **blocked** (a named discrepancy or missing source
must be resolved).

Examples: use the seeded example account (`database/seeders/ExampleAccountSeeder.php`: the Friends cast,
`.example` addresses) for scenarios and screenshots, so no page shows real data. The reader in examples
is "you", recording the people around them.

### 1. Portal root

#### `portal.introduction` Welcome to the Monica guide
- **Identity:** `1-introduction.md`, section `portal`. Navigation page. All audiences.
- **Need and success:** a first visitor knows in under a minute what Monica does and which section to open.
- **Applicability:** all deployments.
- **Scope:** Monica as a personal relationship manager for one person; the five things it holds (people,
  relationships, life events, reminders, and the templates that shape them); links by audience: Start here,
  Troubleshooting, Run your own instance, developer references. Excludes the model in depth (`concepts.model`).
- **Links:** out to every section index.
- **Evidence:** `DocsViewModel::llmsText()` wording ("open source personal relationship manager"), constitution VII.
- **Examples and assets:** none.
- **Acceptance:** no claim of collaboration, journals or tasks; every section reachable in one click.
- **Delivery:** P0, ready, needs F1 and F2.

### 2. Start here

#### `start.introduction` Start here
- **Identity:** `2-start-here/1-introduction.md`. Navigation. New users.
- **Need and success:** the reader sees the three steps and what they will have at the end: an account,
  a first contact with a reminder, and the model in their head.
- **Applicability:** all.
- **Scope:** list the pages in order with one line each. Excludes content of those pages.
- **Links:** the three pages below.
- **Evidence:** none beyond the pages.
- **Examples and assets:** none.
- **Acceptance:** under 150 words.
- **Delivery:** P0, ready.

#### `start.create-account` Create your account
- **Identity:** `2-start-here/2-create-your-account.md`. How-to. Somebody signing up.
- **Need and success:** the reader has an account, has confirmed their address, has read the welcome
  letter and is looking at the **Personal** vault.
- **Applicability:** all. Turnstile check appears only if the instance enabled it. Passkey sign up needs
  HTTPS. Outside provider sign up appears only if the instance configured one.
- **Scope:** fields and rules (disposable addresses refused, terms required); the passkey alternative and
  what happens if the passkey prompt is skipped; confirming the address and resending (link lasts an hour);
  the welcome letter and **Let me in**; landing in the Personal vault. A note that templates and types are
  prepared in the background right after sign up, so the first minute may show "This vault has no template
  for new contacts to start from", which resolves itself. Excludes signing in later (`signin.*`).
- **Links:** prerequisite none; next `start.first-contact`; optional `concepts.model`.
- **Evidence:** `RegistrationController`, `RegistrationPasskeyController`, `CreateAccount`, `WelcomeController`,
  `EmailVerificationController`; tests `RegistrationControllerTest`, `RegistrationPasskeyControllerTest`.
- **Examples and assets:** screenshots of the form and the welcome letter from a fresh account.
- **Acceptance:** states that one account holds one person, so nobody else joins it; does not mention
  invitations; the delay note is verified on a real instance with a database queue.
- **Delivery:** P0, needs verification (the seeding delay message at runtime).

#### `start.first-contact` Record your first person
- **Identity:** `2-start-here/3-record-your-first-person.md`. Tutorial. New users.
- **Skill learned:** how a contact is organised in pages and sections, and how dates become reminders.
- **Need and success:** by the end the reader has added Rachel Green to the Personal vault, filled her
  birthday and an email, linked her to one other person, and seen a yearly birthday reminder appear.
- **Applicability:** all. Hosted free offer counts this contact toward 30.
- **Scope:** fixed sequence: open the vault, **Add somebody**, fill the name, save, open the Overview,
  edit **Personal details** to add a birthday, edit **Contact information** to add an email, add a
  relationship, see the reminder in the Overview and in the vault's **Reminders**. Checkpoints after each
  save (the toast, the Overview block). Excludes alternatives (partial contacts, templates, columns).
- **Links:** prerequisite `start.create-account`; after: `concepts.model`, `reminders.create`,
  `contacts.introduction`.
- **Evidence:** `CreateContact`, `ContactSectionController`, `SetFieldValues::makeTheRule` (a Date or Recurring
  date field creates the reminder rule), `CreateRelationship`; tests `ContactControllerTest`,
  `ContactSectionControllerTest`. Verify on a real account that a birthday creates a reminder by default
  in the Basic contact template.
- **Examples and assets:** Rachel Green and Ross Geller from the example cast; four screenshots at the checkpoints.
- **Acceptance:** every checkpoint is visible on a fresh account; does not send the reader to the Getting
  started screen (D3) or promise import (D4).
- **Delivery:** P0, needs verification (birthday to reminder behaviour on Basic contact).

#### `concepts.model` How Monica is organised
- **Identity:** `2-start-here/4-how-monica-is-organised.md`. Explanation. All users.
- **Conceptual question:** where does everything live, and what belongs to what?
- **Need and success:** the reader can say what an account, a vault, a contact, a profile, a template and
  the Me contact are, and why a vault is a compartment rather than a door you lock others out with.
- **Applicability:** all.
- **Scope:** one person per account and no members; vaults as compartments (one default, moving data is
  explicit); contacts and profiles (people versus pets and other records); templates give every record its
  pages, sections and fields; the core concepts that do not change versus what you can personalise
  (constitution VII); the Me contact exists in the model, but no screen creates it yet (D10); the
  instance administrator never sees your vaults. Diagram of account → vaults → contacts and profiles,
  each pointing at a template version. Distinguish documented rationale (constitution) from analysis.
- **Links:** optional background for every page; related `templates.versions`, `concepts.privacy`.
- **Evidence:** constitution Product Invariants and VII; `Account`, `Vault`, `Contact`, `Entity` models.
- **Examples and assets:** one diagram (accessible SVG with text alternative).
- **Acceptance:** no procedure steps; every term links to its how-to.
- **Delivery:** P0, ready.

### 3. People

Shared: every page here happens inside a vault at `/vaults/{vault}/contacts`. The free offer (hosted)
counts contacts across all vaults; partial contacts and Me are not counted.

#### `contacts.introduction` People
- **Identity:** `3-people/1-introduction.md`. Navigation. Users.
- **Need and success:** reader finds the task among adding, editing, listing, photos, deleting and profiles.
- **Scope, links, evidence:** index of this section; link `concepts.model`. **Acceptance:** one line per page.
- **Applicability:** all. **Examples:** none. **Delivery:** P0, ready.

#### `contacts.add` Add a person
- **Identity:** `3-people/2-add-a-person.md`. How-to. Users who know what a contact is.
- **Need and success:** a person exists in the right vault, with the right template, as a full or partial contact.
- **Applicability:** hosted free offer limit; the vault's default template.
- **Scope:** the form fields (prefix to nickname, **Written as** name order, template choice, partial,
  favourite, **Create another one after this**); what a partial contact is and when to use one (somebody you
  know only by name, often created from a relationship); the contact limit message and where to go
  (`troubleshoot.limit-reached`); duplicates are checked in the background after saving. Excludes editing
  sections (`contacts.edit`).
- **Links:** prerequisite none; related `contacts.edit`, `duplicates.review`, `templates.introduction`.
- **Evidence:** `CreateContact`, `ContactPolicy::create`, `ContactController`; `ContactControllerTest`.
- **Examples and assets:** Monica Geller added as a favourite; screenshot of the form.
- **Acceptance:** limit behaviour stated with a link to `reference.limits`, not a hard-coded number.
- **Delivery:** P0, ready.

#### `contacts.edit` Change what you know about somebody
- **Identity:** `3-people/3-change-what-you-know.md`. How-to. Users.
- **Need and success:** the reader edits one section, a name or a repeated item, and knows why the page
  shows the fields it shows.
- **Applicability:** all.
- **Scope:** pages as tabs (Overview, template pages, Relationships); editing a section saves only that
  section; repeaters (several emails or phones, each with a label; newest last); required and unique field
  errors; changing the name, and that old addresses of the page stop working while the permanent one
  keeps working; "Answers to questions no longer asked" and how answers come back when a field returns; the
  "Version :number is ready" banner, linking to `templates.apply-new-version`. Excludes template editing.
- **Links:** prerequisite `contacts.add`; optional `templates.versions`.
- **Evidence:** `ContactSectionController`, `SetFieldValues`, `UpdateContactName`, `SetAsideValueController`,
  `ContactStructureVersionController`; tests of each.
- **Examples and assets:** Chandler Bing gets a second phone; screenshot of a section in edit mode.
- **Acceptance:** says nothing is lost when a field disappears from a template (constitution VIII),
  verified by `SetAsideValueControllerTest`.
- **Delivery:** P0, ready.

#### `contacts.list` Find people in a vault
- **Identity:** `3-people/4-find-people.md`. How-to. Returning users.
- **Need and success:** the reader finds somebody fast and sets the list up the way they like it.
- **Applicability:** all.
- **Scope:** filters (Everybody, Favourites, Archived, Partial contacts); sort options and that the choice
  is remembered for you; search covers names and nicknames only, across one vault; **Customise view**
  columns, stored for the vault, **Reset to defaults**. There is no Archived list: it was removed in
  #293 because nothing archives a contact yet (D6).
- **Links:** related `contacts.add`, `duplicates.review`.
- **Evidence:** `ContactController::index`, `UpdateContactListView`, `UpdateContactListColumns`,
  `Contact::scopeMatchingSearch`; `ContactListColumnControllerTest`.
- **Examples and assets:** screenshot of the column editor.
- **Acceptance:** no promise of global search or field search.
- **Delivery:** P1, ready once #293 is merged.

#### `contacts.photo` Add a photo to a contact
- **Identity:** `3-people/5-add-a-photo.md`. How-to. Users.
- **Need and success:** a contact shows a photo; the reader knows it counts toward storage.
- **Applicability:** hosted storage limit.
- **Scope:** accepted formats and size; removal asks first; storage counting. Excludes your own profile
  photo (`account.profile`).
- **Links:** related `reference.limits`.
- **Evidence:** `ContactAvatarController`, `UpdateContactAvatar`, `CreateFile`; `ContactAvatarControllerTest`.
- **Examples and assets:** none needed.
- **Acceptance:** storage error message quoted from `lang/en.json`.
- **Delivery:** P1, ready.

#### `contacts.delete` Delete a contact
- **Identity:** `3-people/6-delete-a-contact.md`. How-to. Users.
- **Need and success:** the reader deletes a contact knowing exactly what goes and what stays.
- **Applicability:** the Me contact cannot be deleted.
- **Scope:** `:::warning` before the action; what goes (the contact, its sharing links revoked, its
  reminders stopped); what stays (life events and relationships with other people); there is no way to
  restore a deleted contact from the product today, despite the dialog (D5). Excludes merging (`duplicates.merge`).
- **Links:** related `duplicates.merge`, `sharing.create-link`.
- **Evidence:** `DestroyContact`, `ContactPolicy::delete`; `ContactControllerTest`.
- **Examples and assets:** none.
- **Acceptance:** recovery claim matches the code at the time of writing.
- **Delivery:** P0, blocked on D5 (either the copy changes or a restore exists; the page must state the truth).

#### `profiles.add` Keep a record of a pet or anything else
- **Identity:** `3-people/7-profiles.md`. How-to. Users.
- **Need and success:** the reader adds an animal profile and links it to its caregivers.
- **Applicability:** all; the Animals profile type is seeded.
- **Scope:** the vault sidebar entry per active profile type; **Add :name**; the Basic animal template
  (Animal details, Caregivers, Care, Notes); that a profile has no page of its own yet, so it cannot be
  opened, edited or deleted from the web (partial). Excludes creating profile types (`customise.profile-types`).
- **Links:** optional `concepts.model`.
- **Evidence:** `EntityController`, `CreateEntity`, `SeedAnimalTemplate`; `EntityControllerTest`.
- **Examples and assets:** Marcel the monkey, cared for by Ross Geller.
- **Acceptance:** limitation stated plainly.
- **Delivery:** P2, needs verification (what a user can do with a profile after creation).

### 4. Relationships and life events

#### `relationships.introduction` Relationships and life events
- **Identity:** `4-relationships-and-life-events/1-introduction.md`. Navigation. Users.
- **Need, scope, links:** index; one sentence on the difference (a relationship links two people, a life
  event records something that happened, possibly to several people). **Applicability:** all.
- **Evidence, examples, acceptance:** none beyond the pages. **Delivery:** P0, ready.

#### `relationships.add` Link two people
- **Identity:** `4-relationships-and-life-events/2-link-two-people.md`. How-to. Users.
- **Need and success:** two contacts are linked by a named relationship, visible on both records.
- **Applicability:** all.
- **Scope:** **Add relationship**; choosing the type and **Swap the two roles**; choosing an existing
  person or creating a partial contact, including **I do not know their name**; family relationships feed
  the family tree on the Overview. Excludes editing and types.
- **Links:** prerequisite `contacts.add`; related `customise.relationship-types`.
- **Evidence:** `ContactRelationshipController`, `CreateRelationship`, seeded types; `ContactRelationshipControllerTest`.
- **Examples and assets:** Ross is Emma's parent; screenshot of the form.
- **Acceptance:** says one relationship appears on both records.
- **Delivery:** P0, ready.

#### `relationships.edit` Change or end a relationship
- **Identity:** `4-relationships-and-life-events/3-change-or-end.md`. How-to. Users.
- **Need and success:** the reader records a start or end date, marks it ended, or removes it knowing
  neither person is deleted.
- **Applicability:** all.
- **Scope:** **Started on**, **Ended on**, **This relationship has ended**; removing takes the link off both
  records. `:::warning` not needed (no data loss beyond the link), a plain sentence suffices.
- **Links:** prerequisite `relationships.add`.
- **Evidence:** `UpdateRelationship`, `DestroyRelationship`.
- **Examples and assets:** Ross and Rachel, ended.
- **Acceptance:** wording of the removal confirmation quoted.
- **Delivery:** P1, ready.

#### `life-events.record` Record something that happened
- **Identity:** `4-relationships-and-life-events/4-record-a-life-event.md`. How-to. Users.
- **Need and success:** a life event with a type, a date and the people involved appears on each of their timelines.
- **Applicability:** all; types can be switched off by the user.
- **Scope:** the two step panel; summary, date, people, place, duration, description, and the type's own
  fields; changing the type drops answers the new type does not ask for (quote the warning); deleting
  removes it from every timeline and offers **Undo**. Excludes types (`customise.life-event-types`).
- **Links:** related `life-events.timeline`.
- **Evidence:** `ContactLifeEventController`, `ContactLifeEventRestorationController`; `ContactLifeEvent*ControllerTest`.
- **Examples and assets:** Chandler and Monica's wedding with both of them listed.
- **Acceptance:** the undo window verified at runtime (toast lifetime).
- **Delivery:** P1, needs verification (undo behaviour).

#### `life-events.timeline` How the timeline works
- **Identity:** `4-relationships-and-life-events/5-the-timeline.md`. Explanation. Users.
- **Conceptual question:** why does one event appear on several people, and what is shown in what order?
- **Scope:** one event recorded once and seen from every person in it (constitution: the journal and the
  directory are one gesture); newest first; categories. Distinguish the constitution's stated intent from
  what the timeline currently shows.
- **Applicability, links:** all; related `life-events.record`, `relationships.add`.
- **Evidence:** constitution Product Invariants; `ContactViewModel` timeline methods.
- **Examples and assets:** none. **Acceptance:** no steps. **Delivery:** P2, ready.

### 5. Reminders and notifications

Shared: occurrences are generated daily for the next 90 days; due notifications are sent every five
minutes; digests hourly at the account's digest hour; each reminder keeps its own time zone
(`routes/console.php`, constitution VI). Channels are In the app, Email, Telegram and Text message; the
last two exist only when the instance configured them.

#### `reminders.introduction` Reminders and notifications
- **Identity:** `5-reminders-and-notifications/1-introduction.md`. Navigation. Users.
- **Scope, links:** index. **Applicability:** all. **Evidence:** none. **Examples:** none.
- **Need and acceptance:** reader distinguishes a reminder (what) from a channel (how it reaches you). **Delivery:** P0, ready.

#### `reminders.create` Set a reminder
- **Identity:** `5-reminders-and-notifications/2-set-a-reminder.md`. How-to. Users.
- **Need and success:** a reminder exists on a contact with the right date, repetition, time and zone.
- **Applicability:** all.
- **Scope:** from the contact's Overview or **Reminders** tab; **What it should say**, **Date**,
  **How often** (Once, Weekly, Monthly, Yearly, Every so many), **Local time**, **Time zone**; nothing is
  sent ahead of the day; reminders created automatically from a date field (birthday); sending a test by
  email. Excludes channels.
- **Links:** related `reminders.how-they-work`, `notifications.channels`.
- **Evidence:** `ContactReminderController`, `CreateContactReminder`, `ReminderOccurrenceTestNotificationController`;
  `ContactReminderControllerTest`.
- **Examples and assets:** a yearly reminder for Phoebe's birthday.
- **Acceptance:** states the time zone is fixed when the reminder is created.
- **Delivery:** P0, ready.

#### `reminders.answer` Answer a reminder
- **Identity:** `5-reminders-and-notifications/3-answer-a-reminder.md`. How-to. Users.
- **Need and success:** the reader marks a date **I remembered** or **I forgot** and understands what stops being sent.
- **Applicability:** all.
- **Scope:** where dates wait (Overdue, Today, Upcoming; the vault's **Coming up**, **Sent**, **History**);
  the answer is final and cancels unsent notifications for that date; deleting a reminder drops unanswered
  dates and keeps answered ones (`:::warning`).
- **Links:** prerequisite `reminders.create`.
- **Evidence:** `AnswerReminderOccurrence`, `VaultReminderController`, `ReminderController`; tests.
- **Examples and assets:** screenshot of the vault's Coming up list.
- **Acceptance:** "answer is final" verified in `ReminderOccurrenceAnswerControllerTest`.
- **Delivery:** P0, ready.

#### `reminders.how-they-work` How reminders reach you
- **Identity:** `5-reminders-and-notifications/4-how-reminders-reach-you.md`. Explanation. Users.
- **Conceptual question:** when exactly will I be told, on which channel, and what can delay it?
- **Scope:** a reminder rule versus its dates; generation ahead of time; delivery every few minutes and
  retries; quiet hours and digests (set through the API only today); time zones frozen at creation; a
  channel switched off after repeated failures with an email saying so; deleted contacts stop their reminders.
- **Applicability:** operators may change the numbers; link `reference.limits`.
- **Links:** related `troubleshoot.reminder-not-received`, `notifications.channels`.
- **Evidence:** `GenerateDueReminderOccurrences`, `DeliverDueNotifications`, `SendNotificationDigests`,
  `NotificationPreference`; `tests/Unit/Jobs`.
- **Examples and assets:** a timeline diagram of one reminder from rule to delivery.
- **Acceptance:** no procedure steps; every number links to `reference.limits`.
- **Delivery:** P1, ready.

#### `notifications.channels` Choose how you are notified
- **Identity:** `5-reminders-and-notifications/5-notification-channels.md`. How-to. Users.
- **Need and success:** a channel is connected, proven and tested.
- **Applicability:** Telegram and Text message only on instances that configured them (D26); text
  messages may cost the operator.
- **Scope:** `/settings/account/notifications`; connecting Telegram (**Connect Telegram** opens the bot);
  text message with the country code and the six digit proof code; **Send a test**; **Cancel this
  connection**; automatic switch off. Excludes digests and quiet hours (API only, mention in one line with a
  link to the API reference).
- **Links:** related `reminders.how-they-work`.
- **Evidence:** `NotificationChannelController`, `ChannelProofController`, `ChannelTestController`; `Notifications/*Test`.
- **Examples and assets:** screenshot of the channels screen.
- **Acceptance:** channel error messages checked in a non English locale (they are not translated today).
- **Delivery:** P1, needs verification (Telegram flow on a configured instance).

#### `notifications.inbox` Read notifications in the app
- **Identity:** `5-reminders-and-notifications/6-in-app-notifications.md`. How-to. Users.
- **Need and success:** the reader finds and clears in-app notifications.
- **Scope:** the bell, **Mark every one as read**, retention. **Applicability:** all.
- **Links:** related `notifications.channels`. **Evidence:** `NotificationController`, `NotificationReadController`.
- **Examples:** none. **Acceptance:** retention links to `reference.limits`. **Delivery:** P1, ready.

### 6. Vaults

#### `vaults.introduction` Vaults
- **Identity:** `6-vaults/1-introduction.md`. Navigation. Users.
- **Need and success:** reader understands when a second vault helps (for example family versus work) and
  finds the task. **Scope:** index. **Applicability:** hosted free offer holds one vault.
- **Links:** `concepts.model`. **Evidence:** none. **Examples:** none. **Acceptance:** does not describe
  dashboard Tasks or Files cards (D7). **Delivery:** P0, ready.

#### `vaults.create` Create a vault
- **Identity:** `6-vaults/2-create-a-vault.md`. How-to. Users.
- **Need and success:** a new vault exists with a name, colour and default template.
- **Applicability:** hosted free offer: the Personal vault uses the only free slot.
- **Scope:** **New vault**, fields, **Make this the default vault**, **Default template**; the limit message.
- **Links:** related `billing.free-offer`, `vaults.settings`.
- **Evidence:** `VaultController`, `CreateVault`, `VaultPolicy::create`; `VaultControllerTest`.
- **Examples and assets:** a "Work" vault. **Acceptance:** limit stated as a default. **Delivery:** P0, ready.

#### `vaults.settings` Change a vault's name, colour and default template
- **Identity:** `6-vaults/3-vault-settings.md`. How-to. Users.
- **Need and success:** vault details and the default template for each kind of record are set.
- **Scope:** vault settings; one default template per kind of record, published versions only; deleting a
  template clears defaults that point at it. **Applicability:** all.
- **Links:** optional `templates.versions`. **Evidence:** `VaultSettingsController`, `SetVaultDefaultTemplate`.
- **Examples:** none. **Acceptance:** default template behaviour matches `VaultDefaultTemplateControllerTest`.
- **Delivery:** P1, ready.

#### `vaults.delete` Delete a vault
- **Identity:** `6-vaults/4-delete-a-vault.md`. How-to. Users.
- **Need and success:** the reader deletes a vault knowing it cannot be undone.
- **Applicability:** all.
- **Scope:** `:::warning` first: nothing restores a vault; every contact and profile in it goes, every
  sharing link in it stops working; the default passes to another vault; deleting the last vault leads to
  the Getting started screen.
- **Links:** related `export.vcard` (export first).
- **Evidence:** `DestroyVault`; `VaultControllerTest`.
- **Examples:** none. **Acceptance:** quotes the dialog. **Delivery:** P0, ready.

### 7. Keeping records tidy

#### `duplicates.introduction` Keeping records tidy
- **Identity:** `7-keeping-records-tidy/1-introduction.md`. Navigation. Users.
- **Scope:** index; when duplicates arise (phone sync, typing a person twice). **Applicability:** all.
- **Links, evidence, examples, acceptance:** minimal. **Need:** routing. **Delivery:** P1, ready.

#### `duplicates.review` Review possible duplicates
- **Identity:** `7-keeping-records-tidy/2-review-possible-duplicates.md`. How-to. Users.
- **Need and success:** the reader reviews suggestions and dismisses pairs that are two different people.
- **Applicability:** all.
- **Scope:** **Possible duplicates**; **Look for duplicates** runs in the background; detection also runs
  nightly and after each new contact; matching on name likeness and identifying fields; **Two different
  people** is permanent; Me is never suggested.
- **Links:** next `duplicates.merge`.
- **Evidence:** `ContactDuplicateController`, `DetectDuplicateContacts`, `DismissDuplicateCandidate`; tests.
- **Examples and assets:** two Phoebe Buffay records (Phoebe and Ursula as a true non duplicate).
- **Acceptance:** background timing described without a promised delay.
- **Delivery:** P1, ready.

#### `duplicates.merge` Merge two contacts
- **Identity:** `7-keeping-records-tidy/3-merge-two-contacts.md`. How-to. Users.
- **Need and success:** two records become one, with the reader choosing where they disagree, and the
  reader knows how to undo it.
- **Applicability:** Me cannot be absorbed.
- **Scope:** from **Merge** in the contact menu or a duplicate pair; choosing values, **Keep it once**,
  **Keep both entries**; answers with no matching field are set aside; the absorbed record's old address
  points to the kept one; **Undo the merge**.
- **Links:** prerequisite `duplicates.review` optional.
- **Evidence:** `PreviewContactMerge`, `MergeContacts`, `UndoContactMerge`, `AbsorbedContactController`; `ContactMergeControllerTest`.
- **Examples and assets:** screenshot of a disagreement choice.
- **Acceptance:** undo limits verified at runtime (after later edits to the kept record).
- **Delivery:** P1, needs verification (undo after subsequent edits).

### 8. Sharing, export and sync

#### `sharing.introduction` Sharing, export and sync
- **Identity:** `8-sharing-export-and-sync/1-introduction.md`. Navigation. Users.
- **Need and success:** reader picks the right tool: a read only link for a person, a vCard file, or
  continuous sync with a phone. **Applicability:** all.
- **Scope, links:** index; link `/docs/carddav`. **Evidence, examples:** none.
- **Acceptance:** says sharing is never collaboration. **Delivery:** P1, ready.

#### `sharing.create-link` Share a contact with a link
- **Identity:** `8-sharing-export-and-sync/2-share-a-contact.md`. How-to. Users.
- **Need and success:** a link exists with a name, an optional password, an expiry and chosen sections;
  the reader copied it; the reader can revoke it.
- **Applicability:** contacts only (D8).
- **Scope:** **Share** in the contact menu; fields; the address is shown once (`:::warning`: copy it now);
  sensitive fields off by default; every opening is logged, including wrong passwords; revoking is
  immediate and final; deleting the contact or the vault revokes its links.
- **Links:** related `sharing.what-a-link-shows`.
- **Evidence:** `ContactShareLinkController`, `CreateShareLink`, `SharedContactController`; tests.
- **Examples and assets:** a link to Joey's page for a birthday organiser, 7 days, with a password.
- **Acceptance:** states the link is read only.
- **Delivery:** P1, ready.

#### `sharing.what-a-link-shows` What a sharing link shows
- **Identity:** `8-sharing-export-and-sync/3-what-a-link-shows.md`. Reference. Users.
- **Inventory:** every kind of content, marked shown, optional or never: sections chosen, sensitive fields
  (optional), reminders, history, files, repeated blocks (never, per the form), relationships and life
  events (verify). Plus what the recipient sees when the link has expired or been revoked.
- **Applicability:** all. **Links:** from `sharing.create-link`.
- **Evidence:** share link form view, `resources/views/shared/*`, `SharedContactViewModel`.
- **Examples and assets:** screenshot of the public page. **Acceptance:** every row verified by opening a link.
- **Need:** a person about to share needs certainty on exposure. **Delivery:** P1, needs verification.

#### `export.vcard` Export a contact as a vCard
- **Identity:** `8-sharing-export-and-sync/4-export-a-vcard.md`. How-to. Users.
- **Need and success:** a `.vcf` file is downloaded and opens in another address book.
- **Applicability:** all.
- **Scope:** **Export as vCard**; sensitive fields included; one contact at a time; exporting everything
  and importing a file are not available on the web yet (D4, D11); continuous sync lives in the CardDAV guide.
- **Links:** related `integrations.overview`.
- **Evidence:** `ExportContactVCard`; `ContactVCardControllerTest`.
- **Examples:** none. **Acceptance:** the "sensitive fields included" warning is present. **Delivery:** P1, ready.

#### `integrations.overview` Connect Monica to other tools
- **Identity:** `8-sharing-export-and-sync/5-connect-other-tools.md`. Explanation. Users and developers.
- **Conceptual question:** which of CardDAV, the API or MCP fits my goal, and what access does each give?
- **Scope:** phone and computer sync (CardDAV and CalDAV, two way address book per vault, read only
  calendars); scripts (API keys, optional sensitive fields scope); assistants (MCP with a key or OAuth);
  one shared rate limit per user; everything acts as you. Links to the three references, which own the detail.
- **Applicability:** hosted MCP is rate limited, self hosted is not.
- **Links:** related `security.api-keys`, `security.assistants`.
- **Evidence:** `DavServiceProvider`, `routes/api.php`, `routes/ai.php`, `ApiKeyScopeEnum`.
- **Examples:** none. **Acceptance:** repeats no reference detail. **Delivery:** P1, ready once #293 is merged.

### 9. Templates and customisation

Shared: templates live under **Settings**, **Templates**. System templates ("Supplied with Monica") are
copied, never edited. A template has at most one draft; publishing needs a change note and freezes the
version; records move only when the user applies the new version.

#### `templates.introduction` Templates and customisation
- **Identity:** `9-templates-and-customisation/1-introduction.md`. Navigation. Users who want to shape their records.
- **Need and success:** reader knows what can be customised (templates, relationship types, life event
  types, profile types, value lists) and where to start. **Applicability:** all.
- **Scope, links:** index; `templates.versions` as recommended reading. **Evidence, examples:** none.
- **Acceptance:** under 200 words. **Delivery:** P1, ready.

#### `templates.versions` Why templates have versions
- **Identity:** `9-templates-and-customisation/2-why-templates-have-versions.md`. Explanation. Users.
- **Conceptual question:** why does changing a template not change my contacts, and how do they catch up?
- **Scope:** draft, published, retired; records stay on their version; applying a version takes a snapshot
  first and keeps set aside answers; the constitution's rationale (nothing changes under anybody's feet),
  cited as documented intent.
- **Applicability:** all. **Links:** related `templates.apply-new-version`, `contacts.edit`.
- **Evidence:** constitution VI; `PublishStructureVersion`, `MoveRecordToStructureVersion`, `CreateRecordSnapshot`.
- **Examples and assets:** diagram of two contacts on versions 1 and 2. **Acceptance:** no steps.
- **Delivery:** P1, ready.

#### `templates.build-your-own` Build your own contact template
- **Identity:** `9-templates-and-customisation/3-build-your-own-template.md`. Tutorial. Users new to templates.
- **Skill learned:** copy, edit a draft, publish, and apply to a contact.
- **Need and success:** the reader copies Basic contact, adds a "How we met" section with a text field and
  a date field, publishes version 2 with a change note, and applies it to one contact.
- **Checkpoints:** the copy appears under **Your templates**; the draft shows the new section; the
  published version appears in **Version history**; the contact shows the banner and then the section.
- **Applicability:** all.
- **Scope:** fixed sequence; no alternatives; keyboard route through the builder mentioned (constitution IX).
- **Links:** prerequisite `templates.versions` optional; next `templates.change`.
- **Evidence:** `settings.templates.*` routes, `DuplicateTemplate`, `PublishStructureVersion`; `Settings/Account/Templates/*` tests.
- **Examples and assets:** Rachel meets the new section; five screenshots at the checkpoints.
- **Acceptance:** walked end to end on a fresh account before publication.
- **Delivery:** P1, needs verification (runtime walk through).

#### `templates.change` Change a template
- **Identity:** `9-templates-and-customisation/4-change-a-template.md`. How-to. Users.
- **Need and success:** a page, section or field is added, moved, changed or removed in a draft and published.
- **Scope:** open or create the draft; pages, sections, fields, child fields in a repeater (no repeater
  inside a repeater); identifying fields for duplicate detection; publishing refuses empty pages; turning a
  template off versus deleting (refused while any record uses it). Links to `reference.field-types`.
- **Applicability:** system templates cannot be changed, only copied.
- **Links:** prerequisite `templates.build-your-own` optional.
- **Evidence:** template builder controllers, `CreateStructureVersionDraft`, `DeactivateTemplate`.
- **Examples:** none. **Acceptance:** deletion refusal message quoted. **Delivery:** P1, ready.

#### `templates.apply-new-version` Move a contact to a new template version
- **Identity:** `9-templates-and-customisation/5-apply-a-new-version.md`. How-to. Users.
- **Need and success:** a contact is on the new version, or deliberately stays.
- **Scope:** the banner, **Apply version**, **Stay on version**; snapshot and **Revert** from **Version
  history**; answers set aside. **Applicability:** all.
- **Links:** optional `templates.versions`. **Evidence:** `ContactStructureVersionController`; test of the same name.
- **Examples:** none. **Acceptance:** revert path verified at runtime. **Delivery:** P1, needs verification.

#### `templates.import-export` Share a template as a file
- **Identity:** `9-templates-and-customisation/6-import-and-export.md`. How-to. Users.
- **Need and success:** a template or section is exported to a file and imported into another account.
- **Scope:** what the file holds and never holds ("No contacts, no notes…"); import check step.
- **Applicability:** all. **Links:** related `marketplace.overview`.
- **Evidence:** template and section export and import routes and controllers.
- **Examples:** none. **Acceptance:** file format not documented beyond what the screen says.
- **Delivery:** P2, needs verification (import conflict behaviour).

#### `customise.relationship-types` Add your own relationship types
- **Identity:** `9-templates-and-customisation/7-relationship-types.md`. How-to. Users.
- **Need and success:** a new type or category exists, ordered, or an unused one is switched off or deleted.
- **Scope:** seeded categories and types; family types feed the family tree; switching off versus deleting.
- **Applicability:** all. **Links:** related `relationships.add`.
- **Evidence:** `Settings/Account/RelationshipTypes` controllers and tests. **Examples:** "Roommate".
- **Acceptance:** deletion constraints quoted. **Delivery:** P2, ready.

#### `customise.life-event-types` Add your own life event types
- **Identity:** `9-templates-and-customisation/8-life-event-types.md`. How-to. Users.
- **Need and success:** a type exists in a category with the fields it asks for.
- **Scope:** categories and types; switching on and off; a type's fields are a small template.
- **Applicability:** all. **Links:** related `life-events.record`.
- **Evidence:** `Settings/Account/LifeEventTypes`. **Examples:** "Thanksgiving dinner".
- **Acceptance:** field editor steps verified. **Delivery:** P2, needs verification.

#### `customise.profile-types` Create a kind of profile
- **Identity:** `9-templates-and-customisation/9-profile-types.md`. How-to. Users.
- **Need and success:** a new profile type appears in each vault's sidebar.
- **Scope:** **Create a profile type**; built in types cannot be deleted; switching off hides adding.
- **Applicability:** all. **Links:** related `profiles.add`. **Evidence:** `Settings/Account/EntityTypes`.
- **Examples:** "Plants". **Acceptance:** states profiles have no own page yet. **Delivery:** P2, ready.

#### `customise.value-lists` Change the choices a list offers
- **Identity:** `9-templates-and-customisation/10-value-lists.md`. How-to. Users.
- **Need and success:** a list gains, reorders or retires a value without breaking records that use it.
- **Scope:** values are switched off, never deleted from under records (constitution VIII).
- **Applicability:** all. **Links:** related `templates.change`. **Evidence:** value list controllers.
- **Examples:** a "Category" value. **Acceptance:** retirement behaviour verified. **Delivery:** P2, needs verification.

#### `marketplace.overview` The template marketplace
- **Identity:** `9-templates-and-customisation/11-marketplace.md`. Explanation. Users.
- **Conceptual question:** what is the marketplace and what can I do with it today?
- **Scope:** a public catalogue of templates and sections; browsing and filters; installing and publishing
  are not open yet (quote the share page); an installed item would be a copy that never updates itself
  (constitution VI, stated as intent).
- **Applicability:** listings are moderated by the instance's administrators.
- **Links:** related `templates.import-export`, `admin.moderation`.
- **Evidence:** `MarketplaceController`, template share page copy.
- **Examples:** none. **Acceptance:** nothing promised as available that has no route.
- **Delivery:** P2, ready.

### 10. Your account and security

Shared: sensitive changes ask you to confirm your password or passkey, which then lasts three hours by
default.

#### `account.introduction` Your account and security
- **Identity:** `10-your-account-and-security/1-introduction.md`. Navigation. Users.
- **Need and success:** the reader finds sign in, security, profile, preferences, logs and deletion.
- **Scope:** index; what is protected by default (hashed passwords, rate limits, warning emails, single use
  links). **Applicability:** all. **Links:** all pages. **Evidence:** none. **Examples:** none.
- **Acceptance:** no page duplicated here. **Delivery:** P0, ready.

#### `signin.password` Sign in with your password
- **Identity:** `…/2-sign-in-with-your-password.md`. How-to. Users.
- **Need and success:** the reader is signed in and understands **Remember me**, lockout and the generic refusal.
- **Applicability:** remember me length set by the instance.
- **Scope:** the form; remember me; the same refusal for a wrong password, a deactivated user or an unknown
  address, and why; lockout after repeated failures; every failure emails the owner; the two factor step.
- **Links:** related `signin.magic-link`, `security.two-factor`, `reference.emails`.
- **Evidence:** `LoginController`, `AttemptLogin`; `LoginControllerTest`.
- **Examples:** none. **Acceptance:** remember me with two factor verified on a running instance (D20, fixed in #293).
- **Delivery:** P0, ready once #293 is merged.

#### `signin.magic-link` Sign in with a link sent by email
- **Identity:** `…/3-sign-in-with-a-link.md`. How-to. Users.
- **Need and success:** the reader signs in without a password.
- **Applicability:** can be switched off by the instance (then the option is absent).
- **Scope:** request, open, done; once only; short lifetime named in the email; any device; the neutral
  confirmation; an email after signing in this way; two factor still applies.
- **Links:** related `reference.emails`. **Evidence:** `MagicLinkController`; `MagicLinkControllerTest`.
- **Examples:** none. **Acceptance:** numbers as defaults. **Delivery:** P0, ready.

#### `signin.passkey` Sign in with a passkey
- **Identity:** `…/4-sign-in-with-a-passkey.md`. How-to. Users.
- **Need and success:** the reader signs in with a passkey from their device.
- **Applicability:** needs HTTPS; the device and browser must support passkeys.
- **Scope:** **Sign in with a passkey**; no two factor code is asked after a passkey (and why a passkey
  already proves two things); a warning email arrives. Excludes adding passkeys (`security.passkeys`).
- **Links:** related `security.passkeys`. **Evidence:** `laravel/passkeys` routes, `HandlePasskeyLogin`; `tests/Feature/Auth/Passkey*`.
- **Examples:** none. **Acceptance:** the two factor statement matches D22 resolution. **Delivery:** P0, ready.

#### `signin.provider` Sign in with GitHub, Google, Microsoft or Facebook
- **Identity:** `…/5-sign-in-with-a-provider.md`. How-to. Users.
- **Need and success:** the reader signs in through a provider, or connects one to an existing account.
- **Applicability:** only providers the instance configured appear.
- **Scope:** first sign in creates an account without a password; refusal when the address already has an
  account (connect from settings instead); one provider per user; disconnecting needs a password or passkey.
- **Links:** related `security.password`. **Evidence:** `SsoController` (both), `SignInWithProvider`; tests.
- **Examples:** none. **Acceptance:** refusal messages quoted. **Delivery:** P1, ready.

#### `signin.forgot-password` Choose a new password when you forgot it
- **Identity:** `…/6-forgot-your-password.md`. How-to. Users.
- **Need and success:** the reader sets a new password from an emailed link.
- **Applicability:** accounts with a password.
- **Scope:** request, email, new password, sign in; neutral message; link lasts an hour; the sign in link as
  the faster alternative.
- **Links:** related `signin.magic-link`, `troubleshoot.no-email`.
- **Evidence:** `PasswordResetLinkController`, `NewPasswordController`, `ResetPassword` action.
- **Examples:** none. **Acceptance:** the whole flow completed on a real instance with mail.
- **Delivery:** P0, **blocked on D2** (resolved when a reset email is received and its link works).

#### `security.two-factor` Turn on two factor authentication
- **Identity:** `…/7-two-factor-authentication.md`. How-to. Users.
- **Need and success:** two factor is on, the app is paired, recovery codes are saved; the reader knows how
  to turn it off and what that costs.
- **Applicability:** all.
- **Scope:** why it helps (brief, one paragraph); **Turn on two-factor authentication**, pair, prove it
  worked; nothing changes until the code is accepted; the challenge after password or link; not after a
  passkey; recovery codes shown next; turning it off forgets the secret and codes (`:::warning`). No email
  is sent for these changes.
- **Links:** next `security.recovery-codes`.
- **Evidence:** `TwoFactorController`, `TwoFactorChallengeController`; tests.
- **Examples and assets:** screenshot of the pairing screen with a fake QR code.
- **Acceptance:** passkey statement consistent with D22. **Delivery:** P0, ready.

#### `security.recovery-codes` Keep and renew your recovery codes
- **Identity:** `…/8-recovery-codes.md`. How-to. Users with two factor on.
- **Need and success:** codes saved away from the phone; the reader knows how to renew them.
- **Scope:** eight codes, once each; **I have saved them**; **Get new codes** invalidates the old set
  immediately (`:::warning`); whether the codes stay visible later must be checked (the recovery codes
  page shows flashed codes once).
- **Applicability:** two factor on. **Links:** prerequisite `security.two-factor`.
- **Evidence:** `RecoveryCodeController`, `RecoveryCodes` helper; `RecoveryCodeControllerTest`.
- **Examples:** none. **Acceptance:** visibility after leaving the page verified at runtime.
- **Delivery:** P0, needs verification.

#### `security.passkeys` Add and remove passkeys
- **Identity:** `…/9-passkeys.md`. How-to. Users.
- **Need and success:** a passkey is added, renamed or removed; the reader is not locked out.
- **Applicability:** HTTPS.
- **Scope:** add, rename, remove; emails on add and remove; removing the last passkey on an account with no
  password warns (`:::warning`); removing the password entirely (needs a passkey).
- **Links:** related `signin.passkey`, `security.password`.
- **Evidence:** passkey routes, `EnsurePasswordIsConfirmedWhenThereIsOne`; passkey tests.
- **Examples:** none. **Acceptance:** lock out warnings quoted. **Delivery:** P1, ready.

#### `security.password` Change, set or remove your password
- **Identity:** `…/10-your-password.md`. How-to. Users.
- **Need and success:** password changed, set for the first time (provider accounts) or removed (passkey accounts).
- **Scope:** the three operations and their preconditions; **Last changed**; emails sent on set and remove,
  none on change; other sessions are not signed out.
- **Applicability:** all. **Links:** related `security.suspicious-activity`.
- **Evidence:** `SecurityController`, password controllers; tests. **Examples:** none.
- **Acceptance:** "other sessions" behaviour verified. **Delivery:** P0, needs verification.

#### `security.api-keys` Create an API key
- **Identity:** `…/11-api-keys.md`. How-to. Users and developers.
- **Need and success:** a key exists, copied once, for a script, an assistant or a phone.
- **Applicability:** all.
- **Scope:** name, **Personal script** or **AI Assistant** (a label only), the sensitive fields scope; shown
  once (`:::warning`); no expiry; revoking is immediate; the same key is the password for CardDAV. API detail
  lives in `/docs/api`.
- **Links:** related `integrations.overview`, `/docs/api`, `/docs/carddav`.
- **Evidence:** `ApiKeyController` (web), `ApiKeyScopeEnum`; tests.
- **Examples:** none. **Acceptance:** no request examples on this page. **Delivery:** P1, ready.

#### `security.assistants` Disconnect an assistant
- **Identity:** `…/12-connected-assistants.md`. How-to. Users.
- **Need and success:** the reader sees assistants connected through OAuth and disconnects one.
- **Scope:** what a connected assistant is; consent screen when connecting; disconnect takes effect at once.
- **Applicability:** all. **Links:** related `/docs/mcp`.
- **Evidence:** `ConnectedAssistantController`, `McpOAuthTest`. **Examples:** none.
- **Acceptance:** consistent with the corrected `/docs/mcp` (D14). **Delivery:** P1, ready once #293 is merged.

#### `security.suspicious-activity` An email says somebody signed in
- **Identity:** `…/13-when-something-looks-wrong.md`. How-to. Worried users.
- **Need and success:** the reader identifies the email, decides whether it was them, and secures the account.
- **Scope:** organised by email subject (failed attempt, signed in without a password, new place, passkey
  added or removed, password removed, provider disconnected); for each: what it means, what to do; the
  new place check compares one address with the previous one, nothing more.
- **Applicability:** all. **Links:** related `reference.emails`, `security.password`, `security.two-factor`.
- **Evidence:** `app/Notifications/*`, `CheckLastLogin`. **Examples:** none.
- **Acceptance:** subjects match `lang/en.json`. **Delivery:** P0, ready (wording of D23 noted).

#### `account.profile` Change your name, email address and photo
- **Identity:** `…/14-your-profile.md`. How-to. Users.
- **Need and success:** details changed; a new email address confirmed.
- **Scope:** name; email change asks for password confirmation and unconfirms the address, and today the
  reader must press **Send the email again** themselves (D12); photo formats and removal.
- **Applicability:** all. **Links:** related `start.create-account`.
- **Evidence:** `Profile/*` controllers, `UpdateUserProfile`; tests. **Examples:** none.
- **Acceptance:** email step matches the code at writing time. **Delivery:** P1, blocked on a D12 decision.

#### `account.preferences` Choose your language, formats and time zone
- **Identity:** `…/15-preferences.md`. How-to. Users.
- **Need and success:** interface language, time, date and number formats, link style and time zone set.
- **Scope:** each option and its choices; saves on selection; **Use my device** for the time zone; the
  locale chosen on the sign in screen lasts only for the session; changing the time zone does not move
  existing reminders.
- **Applicability:** all. **Links:** related `reminders.how-they-work`.
- **Evidence:** `PreferenceController`, `TimezoneController`, `config/localization.php`. **Examples:** none.
- **Acceptance:** which screens honour each format verified at runtime. **Delivery:** P1, needs verification.

#### `account.logs` Read your activity and the emails sent to you
- **Identity:** `…/16-logs.md`. How-to. Users.
- **Need and success:** the reader reads the activity log and checks whether an email was sent and delivered.
- **Scope:** activity with its source (web, API, CardDAV, MCP) and address; **Load more**; emails sent with
  their state (Delivered, Bounced, Marked as spam, On its way) and that the state is filled only when the
  instance reports delivery; stored copies carry no links; not every email is recorded (D24).
- **Applicability:** delivery states depend on the instance's mail provider.
- **Links:** related `troubleshoot.no-email`, `concepts.privacy`.
- **Evidence:** `Logs/*` controllers, `UserActionEnum`, `EmailSent`; tests. **Examples:** none.
- **Acceptance:** retention not stated until a source exists. **Delivery:** P1, ready.

#### `account.delete` Delete your account
- **Identity:** `…/17-delete-your-account.md`. How-to. Users.
- **Need and success:** the account is deleted with full knowledge of the consequences.
- **Applicability:** hosted subscriptions are cancelled immediately.
- **Scope:** `:::warning` first; typing your address to confirm; everything goes, including logs and email
  copies; a running subscription ends at once without a refund (hosted, pending source); what happens if
  the payment provider does not answer (nothing is deleted); export first.
- **Links:** related `export.vcard`, `billing.cancel`.
- **Evidence:** `AccountController`, `DestroyAccount`; `AccountControllerTest`.
- **Examples:** none. **Acceptance:** refund and backup claims only with authoritative sources.
- **Delivery:** P1, blocked on hosted sources (refund, backups).

#### `concepts.privacy` What Monica keeps and who can see it
- **Identity:** `…/18-privacy.md`. Explanation. Privacy conscious users.
- **Conceptual question:** what is stored about me and the people I record, and who can reach it?
- **Scope:** one person per account; administrators govern the instance, not vault contents; sensitive
  fields and what excludes them (sharing links, API keys without the scope); hashed passwords, encrypted
  secrets, hashed link tokens; activity log and email copies; sharing links as the only way out; hosted
  versus self hosted responsibility. Retention only with sources.
- **Applicability:** all. **Links:** related `account.logs`, `sharing.what-a-link-shows`.
- **Evidence:** constitution invariants, `specs/001-admin-space`, models. **Examples:** none.
- **Acceptance:** each claim traceable. **Delivery:** P1, blocked on hosted sources for retention.

### 11. Plans and billing (hosted)

Shared: billing applies to metered instances only. Prices and currencies come from the live catalogue;
pages never print a price.

#### `billing.introduction` Plans and billing
- **Identity:** `11-plans-and-billing/1-introduction.md`. Navigation. Hosted customers.
- **Need and success:** the reader knows there is a free offer and one thing above it, full access.
- **Scope:** index; self hosted instances meter nobody. **Applicability:** hosted.
- **Links, evidence:** constitution Product Invariants. **Examples:** none. **Acceptance:** no tiers or trial mentioned.
- **Delivery:** P1, ready.

#### `billing.free-offer` What the free offer holds
- **Identity:** `11-plans-and-billing/2-free-offer.md`. Reference. Hosted customers.
- **Inventory:** vaults, contacts (what counts), storage, largest file; where usage is shown (Billing
  screen); what happens at the limit for each.
- **Applicability:** hosted. **Links:** related `troubleshoot.limit-reached`.
- **Evidence:** `VaultPolicy`, `ContactPolicy`, `CreateFile`, billing view model. **Examples:** none.
- **Need:** a customer at a limit wants the exact rule. **Acceptance:** numbers match monicahq.com's statement.
- **Delivery:** P1, blocked on the authoritative free offer statement.

#### `billing.full-access` Get full access
- **Identity:** `11-plans-and-billing/3-full-access.md`. How-to. Hosted customers.
- **Need and success:** the reader subscribes monthly or yearly, or buys lifetime access, and sees the caps lifted.
- **Scope:** the three offers; checkout; currency choice; upgrading from monthly to yearly (no charge that
  day, renewal date kept); invoices and card through the payment portal; access withdrawn after a refund or dispute.
- **Applicability:** hosted. **Links:** related `billing.cancel`.
- **Evidence:** `Billing/*` controllers, `OfferKindEnum`, `docs/STRIPE.md`; billing tests.
- **Examples:** none. **Acceptance:** no price printed; tax statement only with a source.
- **Delivery:** P1, blocked on hosted sources (tax, refunds).

#### `billing.cancel` Stop your subscription
- **Identity:** `11-plans-and-billing/4-stop-your-subscription.md`. How-to. Hosted customers.
- **Need and success:** renewal stopped; the reader knows access lasts until the paid period ends and how to resume.
- **Scope:** **Stop the subscription renewing?**, **Keep the subscription**; what happens when access ends
  (back to the free offer, nothing deleted: verify what happens to data above the caps).
- **Applicability:** hosted. **Links:** related `billing.free-offer`.
- **Evidence:** `SubscriptionController`, `SubscriptionCancellationController`, `EndAccess`. **Examples:** none.
- **Acceptance:** behaviour above caps verified. **Delivery:** P1, needs verification.

### 12. Troubleshooting

#### `troubleshoot.introduction` Troubleshooting
- **Identity:** `12-troubleshooting/1-introduction.md`. Navigation. Anyone with a problem.
- **Scope:** symptoms in the reader's words, each linking to a page here or elsewhere. **Applicability:** all.
- **Need, links, evidence, examples, acceptance:** routing only. **Delivery:** P0, ready.

#### `troubleshoot.cannot-sign-in` I cannot sign in
- **Identity:** `12-troubleshooting/2-cannot-sign-in.md`. How-to. Users.
- **Branches:** credentials refused; too many attempts; link no longer works; code not right or took too
  long; passkey not offered (no HTTPS); provider button missing; account deactivated by an administrator
  (same message; contact whoever runs the instance).
- **Completion check:** the reader is signed in or knows whom to contact.
- **Applicability:** all. **Links:** `signin.*`, `security.recovery-codes`.
- **Evidence:** auth controllers, `EnsureUserIsActive`. **Examples:** none.
- **Acceptance:** every message quoted from `lang/en.json`. **Delivery:** P0, ready.

#### `troubleshoot.no-email` I am not receiving emails
- **Identity:** `12-troubleshooting/3-no-email.md`. How-to. Users, with an operator part.
- **Branches:** spam and delay; wrong address; check **Emails sent** (no entry means never sent);
  operator checklist: worker serving `high`, mail configured, not writing to the log.
- **Completion check:** the email arrives, or the reader has a concrete message for the operator.
- **Applicability:** all. **Links:** `account.logs`, `hosting.background-work`, `hosting.email`.
- **Evidence:** notification queues, `EmailSent`. **Examples:** none. **Acceptance:** D24 exceptions stated.
- **Delivery:** P0, ready.

#### `troubleshoot.reminder-not-received` A reminder did not arrive
- **Identity:** `12-troubleshooting/4-reminder-not-received.md`. How-to. Users.
- **Branches:** date not yet generated or already answered; channel switched off after failures; quiet
  hours or digest; time zone; reminder deactivated because its contact was deleted or its field removed;
  instance scheduler not running (operator).
- **Completion check:** a test notification arrives.
- **Applicability:** all. **Links:** `reminders.how-they-work`, `notifications.channels`.
- **Evidence:** reminder jobs, `ChannelDisabled`. **Examples:** none. **Acceptance:** each branch has a check.
- **Delivery:** P1, ready.

#### `troubleshoot.limit-reached` I hit a limit
- **Identity:** `12-troubleshooting/5-limit-reached.md`. How-to. Hosted customers and users of metered instances.
- **Branches:** vault, contact, storage, file size, rate limit for scripts.
- **Completion check:** the reader freed space, got full access, or asked the operator.
- **Applicability:** metered instances. **Links:** `billing.free-offer`, `reference.limits`.
- **Evidence:** policy denial messages. **Examples:** none. **Acceptance:** messages quoted.
- **Delivery:** P1, blocked with `billing.free-offer`.

### 13. Reference

#### `reference.introduction` Reference
- **Identity:** `13-reference/1-introduction.md`. Navigation. All.
- **Scope, links:** index; the developer references linked. **Applicability, evidence, examples:** none.
- **Need and acceptance:** routing. **Delivery:** P1, ready.

#### `reference.glossary` Words Monica uses
- **Identity:** `13-reference/2-glossary.md`. Reference. All.
- **Inventory:** every term in the vocabulary table of [Shared product facts](#shared-product-facts),
  plus full access, free offer, administrator, sensitive field, identifying field, set aside answers,
  sharing link. Alternate words readers search for: "custom field" (field), "group" (vault is not a group),
  "pet" (profile).
- **Applicability:** all. **Links:** each term to its canonical page. **Evidence:** `lang/en.json` labels.
- **Examples:** none. **Need:** vocabulary lookup. **Acceptance:** one definition per term. **Delivery:** P1, ready.

#### `reference.field-types` Field types
- **Identity:** `13-reference/3-field-types.md`. Reference. Template builders.
- **Inventory:** the 21 `FieldTypeEnum` cases, by picker label, with what each stores, whether it can repeat,
  whether it can drive a reminder (Date, Recurring date), whether it counts toward storage (File, Image,
  Profile photo), and options. Exclude "Not available yet" types (D9).
- **Applicability:** all. **Links:** from `templates.change`.
- **Evidence:** `FieldTypeEnum`, `FieldCatalogueViewModel`, field type tests. **Examples:** none.
- **Need:** a builder choosing a type. **Acceptance:** count equals available enum cases at writing time.
- **Delivery:** P1, ready.

#### `reference.emails` Emails Monica sends
- **Identity:** `13-reference/4-emails.md`. Reference. Users with an email in hand.
- **Inventory:** every notification in `app/Notifications` with its subject, trigger and page to read next:
  confirm your address, sign in link, failed sign in, signed in without a password, new place, passkey added
  or removed, password set or removed, provider disconnected, full access unlocked or ended, channel proof
  code, channel test, channel switched off, reminder due, digest, marketplace listing approved or rejected,
  password reset.
- **Applicability:** some only on hosted or with channels configured. **Links:** `security.suspicious-activity`.
- **Evidence:** `app/Notifications/*`, `lang/en.json`. **Examples:** none.
- **Need:** a reader holding a subject line. **Acceptance:** list equals the notifications directory.
- **Delivery:** P0, ready (reset email row blocked on D2).

#### `reference.limits` Limits and defaults
- **Identity:** `13-reference/5-limits.md`. Reference. All.
- **Inventory:** the Numbers table in [Shared product facts](#shared-product-facts), each row marked
  "an administrator can change this" or "fixed".
- **Applicability:** hosted values need the authoritative statement. **Links:** `hosting.configuration`.
- **Evidence:** as cited in the table. **Examples:** none. **Need:** exact numbers.
- **Acceptance:** every number rechecked against code on the day of publication. **Delivery:** P0, ready.

### 14. Run your own instance

Shared: audience is operators comfortable with a shell. Every page states "operators" at the top (F7).
The same person is usually the instance administrator.

#### `hosting.introduction` Run your own instance
- **Identity:** `14-run-your-own-instance/1-introduction.md`. Navigation. Operators.
- **Need and success:** the operator knows the pieces (web, database, queue worker, scheduler, mail,
  storage) and the order of the pages.
- **Scope:** self hosting runs the same product without caps; the licence (AGPL-3.0-or-later).
- **Applicability:** self hosted. **Links:** all hosting pages. **Evidence:** `LICENSE.md`, `config/monica.php`.
- **Examples:** none. **Acceptance:** no claim of a published image. **Delivery:** P1, ready.

#### `hosting.requirements` Requirements
- **Identity:** `…/2-requirements.md`. Reference. Operators.
- **Inventory:** PHP 8.4 or later; extensions intl, sodium, openssl, mbstring, gd, the database driver;
  databases (SQLite, MySQL, MariaDB, PostgreSQL, with CI running MySQL only); Bun and Node for building
  assets; HTTPS for passkeys; a public HTTPS address for Telegram.
- **Applicability:** self hosted. **Evidence:** `composer.json`, `composer.lock`, `.github/workflows/tests.yml`.
- **Links, examples:** none. **Need:** can my server run it? **Acceptance:** database support statement agreed with maintainers.
- **Delivery:** P1, needs verification (supported database list).

#### `hosting.install-docker` Install with Docker
- **Identity:** `…/3-install-with-docker.md`. How-to. Operators.
- **Need and success:** a running instance from `compose.yaml`, with worker and scheduler.
- **Scope:** services, environment, first start (`monica:setup --deploy` and `--build` run by the entrypoint),
  volumes to persist (`storage`, the database), creating the first administrator.
- **Applicability:** the compose file is described as development only; no published image.
- **Links:** next `admin.first-administrator`. **Evidence:** `Dockerfile`, `compose.yaml`, `docker-entrypoint.sh`.
- **Examples:** none. **Acceptance:** followed on a clean machine, healthcheck green.
- **Delivery:** P1, **blocked on D18** and on a decision whether a production image will exist.

#### `hosting.install-server` Install on a server
- **Identity:** `…/4-install-on-a-server.md`. How-to. Operators.
- **Need and success:** Monica served by a web server with PHP, database migrated, assets built.
- **Scope:** clone, `composer install --no-dev`, environment, `php artisan monica:setup --deploy` then
  `--build`, web server document root, HTTPS, trusted proxies; optional packages for Telegram, text messages
  and Microsoft sign in.
- **Applicability:** self hosted. **Links:** next `hosting.background-work`.
- **Evidence:** `composer.json` scripts, `SetupApplicationCommand`. **Examples:** none.
- **Acceptance:** followed on a clean server. **Delivery:** P1, needs verification.

#### `hosting.background-work` Run the queue worker and scheduler
- **Identity:** `…/5-queue-and-scheduler.md`. How-to. Operators.
- **Need and success:** a worker drains `high,default,low` in that order and the scheduler runs every minute.
- **Scope:** what depends on it (every email, logs, account seeding, reminders, duplicate detection, file
  purging); the scheduled jobs and their times; a process supervisor; symptoms when missing.
- **Applicability:** any non sync queue. **Links:** related `troubleshoot.no-email`.
- **Evidence:** `routes/console.php`, `onQueue` usages, `Dockerfile` `queue.sh` and crontab. **Examples:** a supervisor config.
- **Acceptance:** notes that `composer dev` does not serve `high` and `low` (D25). **Delivery:** P1, ready.

#### `hosting.configuration` Configuration reference
- **Identity:** `…/6-configuration.md`. Reference. Operators.
- **Inventory:** every key of `config/monica.php` with its environment variable, default, effect for users,
  and whether the admin screen overrides it; the non Monica variables an operator must set (app, database,
  mail, queue, session, storage, Stripe, Sentry, Turnstile, SSO, Telegram, Vonage, trusted proxies,
  Passport keys); precedence (stored admin value beats environment; revert restores the environment);
  instance settings are encrypted with `APP_KEY`, so changing the key makes them unreadable.
- **Applicability:** self hosted. **Links:** related `admin.configuration`.
- **Evidence:** `config/*.php`, `.env.example`, `InstanceSettingEnum`, `AppServiceProvider::applyInstanceSettings`.
- **Examples:** none. **Need:** exact settings. **Acceptance:** list equals config keys at writing time; no secret values.
- **Delivery:** P1, ready.

#### `hosting.email` Send email from your instance
- **Identity:** `…/7-email.md`. How-to. Operators.
- **Need and success:** mail leaves the instance; optionally delivery states come back.
- **Scope:** SMTP or any Laravel mailer; Resend with its webhook at `/resend/webhook` and its secret for
  delivery states; from address; the log mailer in development.
- **Applicability:** self hosted. **Links:** related `account.logs`.
- **Evidence:** `config/mail.php`, Resend listeners, `ResendWebhookTest`. **Examples:** none.
- **Acceptance:** a delivered state seen on the Emails sent screen. **Delivery:** P1, needs verification.

#### `hosting.storage` Store files locally or on S3
- **Identity:** `…/8-storage.md`. How-to. Operators.
- **Need and success:** uploads persist on local disk or an S3 compatible bucket.
- **Scope:** `FILESYSTEM_DISK`, S3 variables, the orphan sweep and its grace period, upload size versus PHP limits.
- **Applicability:** self hosted. **Links:** related `hosting.backups`.
- **Evidence:** `config/filesystems.php`, `PurgeOrphanedFiles`. **Examples:** none.
- **Acceptance:** tested against one S3 compatible service. **Delivery:** P2, needs verification.

#### `hosting.sign-in-options` Configure sign in options
- **Identity:** `…/9-sign-in-options.md`. How-to. Operators.
- **Need and success:** providers, passkeys, sign in links and the human check are set as the operator wants.
- **Scope:** provider credentials and callback URLs; Microsoft needs an extra package (D26); passkeys need
  HTTPS and use the host of the app URL; sign in links on or off and their lifetime; Turnstile and its
  strictness; registration cannot be closed (no switch exists).
- **Applicability:** self hosted. **Links:** related `signin.*`.
- **Evidence:** `config/services.php`, `config/passkeys.php`, `config/turnstile.php`, `InstanceSettingEnum`.
- **Examples:** none. **Acceptance:** "registration cannot be closed" stated plainly.
- **Delivery:** P1, ready.

#### `hosting.notification-channels` Offer Telegram and text messages
- **Identity:** `…/10-notification-channels.md`. How-to. Operators.
- **Need and success:** users can connect Telegram or text message on this instance.
- **Scope:** installing the optional packages (D26); Telegram bot token, bot URL, webhook secret, public
  HTTPS address, registering the webhook from the admin screen or `monica:setup-telegram`; Vonage
  credentials and cost.
- **Applicability:** self hosted. **Links:** related `admin.configuration`.
- **Evidence:** `TelegramWebhook::missingPrerequisite`, `SetupTelegramCommand`, `config/vonage.php`.
- **Examples:** none. **Acceptance:** followed once end to end. **Delivery:** P2, blocked on D26.

#### `admin.first-administrator` Make yourself the administrator
- **Identity:** `…/11-first-administrator.md`. How-to. Operators.
- **Need and success:** the operator's user is an administrator and sees **Admin space**.
- **Scope:** sign up normally, then `php artisan monica:make-administrator --email=`; nobody becomes
  administrator automatically; granting others from the admin space; the last administrator cannot be removed.
- **Applicability:** any instance. **Links:** next `admin.overview`.
- **Evidence:** `MakeAdministratorCommand`, `GrantAdministratorAccess`, `InstanceMustKeepAnAdministrator`.
- **Examples:** none. **Acceptance:** command output quoted. **Delivery:** P1, ready.

#### `admin.overview` What the admin space is for
- **Identity:** `…/12-admin-space.md`. Explanation. Administrators.
- **Conceptual question:** what can an administrator do, and what can they never do?
- **Scope:** overview figures, accounts, moderation, administrators, the administrator log, configuration;
  administrators never read vault contents and cannot impersonate (spec 001, documented intent); the space
  answers "not found" to everybody else.
- **Applicability:** administrators. **Links:** the admin how-tos.
- **Evidence:** `routes/administrator.php`, `specs/001-admin-space/spec.md`. **Examples:** none.
- **Acceptance:** no steps. **Delivery:** P1, ready.

#### `admin.configuration` Change instance settings from the admin space
- **Identity:** `…/13-admin-configuration.md`. How-to. Administrators.
- **Need and success:** a setting changed, reverted to the environment value, or an orphan removed.
- **Scope:** groups; password confirmation; secrets never shown back; settings whose package is missing are
  read only; warnings on risky changes; the Telegram panel.
- **Applicability:** administrators. **Links:** prerequisite `hosting.configuration` for precedence.
- **Evidence:** `InstanceSettingController`, `ConfigurationViewModel`. **Examples:** changing sign in link lifetime.
- **Acceptance:** revert behaviour verified. **Delivery:** P1, needs verification.

#### `admin.accounts` Manage accounts
- **Identity:** `…/14-manage-accounts.md`. How-to. Administrators.
- **Need and success:** storage limit set, lifetime access granted or withdrawn, a user deactivated or reactivated.
- **Scope:** each operation and its effect; deactivation ends web sessions and blocks sign in, and since #293 (D21) also refuses the person's
  API keys, CardDAV devices and connected assistants until reactivation, without revoking them; no
  account deletion from the admin space.
- **Applicability:** administrators. **Links:** related `admin.overview`.
- **Evidence:** `Admin/AccountController` and siblings, `DeactivateUser`, `AppServiceProvider::refuseDeactivatedUsers`. **Examples:** none.
- **Acceptance:** deactivation effects verified. **Delivery:** P1, ready once #293 is merged.

#### `admin.moderation` Moderate marketplace submissions
- **Identity:** `…/15-moderation.md`. How-to. Administrators.
- **Need and success:** a submission approved or rejected, and the author notified.
- **Scope:** the queue, approve, reject, the emails sent. **Applicability:** administrators; publishing is
  not yet open to users, so the queue is normally empty.
- **Links:** related `marketplace.overview`. **Evidence:** `ModerationController`, approval and rejection controllers.
- **Examples:** none. **Acceptance:** states the queue is empty until publishing opens. **Delivery:** P2, ready.

#### `hosting.backups` Back up and restore
- **Identity:** `…/16-backups.md`. How-to. Operators.
- **Need and success:** a backup that restores: the database, `storage/` (uploads and OAuth keys) and
  `APP_KEY` (encrypts instance settings and secrets); a tested restore.
- **Scope:** per database dump commands; files; keys; restore order; no built in tooling.
- **Applicability:** self hosted. **Links:** related `hosting.upgrade`.
- **Evidence:** `storage/oauth-*.key`, `InstanceSetting` encryption, models with encrypted casts.
- **Examples:** none. **Acceptance:** a restore walked on a disposable instance.
- **Delivery:** P1, needs verification.

#### `hosting.upgrade` Upgrade your instance
- **Identity:** `…/17-upgrade.md`. How-to. Operators.
- **Need and success:** a newer version running without data loss.
- **Scope:** back up, update code, dependencies, `monica:setup --deploy` and `--build`, restart workers.
- **Applicability:** self hosted. **Links:** prerequisite `hosting.backups`.
- **Evidence:** `SetupApplicationCommand`. **Examples:** none.
- **Acceptance:** a versioned release exists to upgrade between.
- **Delivery:** P2, blocked until releases and a changelog exist.

#### `hosting.monitoring` Monitor your instance
- **Identity:** `…/18-monitoring.md`. How-to. Operators.
- **Need and success:** health checks and error reporting in place.
- **Scope:** `/up` (fails when the database is down), `/api/status`, Sentry via `SENTRY_LARAVEL_DSN`,
  scheduled job monitoring, logs.
- **Applicability:** self hosted. **Links:** related `hosting.background-work`.
- **Evidence:** `CheckDatabaseIsReady`, `StatusController`, `bootstrap/app.php`. **Examples:** none.
- **Acceptance:** both endpoints checked. **Delivery:** P2, ready.

#### `contributing.overview` Contribute to Monica
- **Identity:** `…/19-contribute.md`. How-to. Contributors.
- **Need and success:** a working development setup and a first contribution path (translations first).
- **Scope:** `composer setup`, `composer dev` (with D25), tests, checks CI runs, `monica:localize`; link to
  repository files for conventions rather than copying them.
- **Applicability:** anyone. **Links:** related `hosting.install-server`.
- **Evidence:** `composer.json`, `.github/workflows/*`. **Examples:** none.
- **Acceptance:** repository has a correct README, CONTRIBUTING and SECURITY file to link to.
- **Delivery:** P2, blocked on D19.

---

## Delivery and maintenance

### Order of work

Urgency and readiness are separate: a P0 page that is blocked stays in the first wave's list, visible,
and is not published until its blocker clears.

| Wave | Content | Portal work | Condition to publish |
| --- | --- | --- | --- |
| 0, corrections to live docs | D13, D14, D15, D16 done in #293; the `/docs` card text (D1, #274) remains | none | Merge #293; fix #274 |
| 1, first publication | Portal root; Start here; People (without `contacts.list`); Relationships; Reminders (create, answer); Vaults; Account and security P0 pages; Troubleshooting P0 pages; `reference.emails`, `reference.limits` | F1, F2, F3; card links | Every included page ready or its blocker resolved; D2, D5, D20 decided |
| 2 | Life events, notifications, duplicates, sharing, export, templates P1, remaining account pages, billing (with sources), `reference.glossary`, `reference.field-types`, operators P1 | F4, F5, F6, F7 | Hosted sources for billing; D18 or the Docker page held back |
| 3 | Customisation P2, marketplace, profiles, remaining operator pages, contributing | F8, F9, F10 | Releases exist for `hosting.upgrade`; D19 fixed |
| Translation | Begin with the locales whose glossary is complete in `.claude/rules/translations.md` | F5 | A section unchanged for one release |

Effort is not estimated; nothing requested it.

### Review process

- **With every behaviour change.** A pull request that touches a route, action, notification or
  translatable string in a documented area names the affected page ids. The page's **Evidence** line in
  this roadmap is the lookup table until the renderer can hold source references.
- **Examples and links.** F1's tests fail on an unknown `@doc` id. Screenshots are regenerated from the
  example account.
- **At each release.** Walk the reader journeys below against the live product and update
  `reference.limits` and `reference.emails` first, since they carry the most facts.
- **Review dates.** Written by the reviewer when a page has been checked against the product, never
  generated (F10).

### Measures

Measure task success and answer discovery, not page views: searches with no result (F4), "this page did
not help" reports per page (F8), support questions that a page should have answered, and the share of
new accounts that record a first contact within a day.

---

## Handoff

### Writer skill conflicts

Read against `.claude/skills/writer/SKILL.md`. Each has the smallest correction proposed; none has been
applied, because changing another skill is outside this task.

| ID | Conflict | Effect on these briefs | Smallest correction |
| --- | --- | --- | --- |
| W1 | "You MUST assume the reader has never used the application before" | Contradicts reference pages, returning user how-tos and operator pages | Apply it to tutorials and Start here; other pages assume what their brief's audience line says |
| W2 | Every feature introduction MUST explain what, why, when, when not | Pushes explanation into how-to and reference pages | Require it on explanation pages; how-to pages give one sentence of context and link the explanation |
| W3 | "Tutorials teach someone how to achieve a goal" and there is no how-to type | Merges tutorials and how-to guides | Add a how-to section; define a tutorial as a guided lesson with a fixed scenario |
| W4 | Examples and link targets from another product (collections, items, copies, roles, "Invite teammates") | "Invite teammates" and "roles" contradict the one person invariant | Replace with Monica examples (contact, vault, reminder) |
| W5 | Concept page template asks "Why does it exist?" | Invites guessed rationale | Add: cite documented rationale (constitution, specs); label anything else as analysis |
| W6 | No locale folder, while `@doc` URLs contain a locale | Paths in this roadmap add `docs/portal/en/` | Add one line defining `docs/portal/{locale}/` as the root for numbering |
| W7 | Fictional example guidance is generic (Emma and vinyl records) | Screenshots need data | Point to the example account seeder |

### Reader journeys (planning review)

Walked against this plan, not against real readers or the running product.

| Journey | Path | Result |
| --- | --- | --- |
| First use | `/docs` card → `portal.introduction` → `start.create-account` → `start.first-contact` | Complete, pending verification of the birthday reminder |
| Everyday task | Search "remind me of a birthday" → `reminders.create` | Complete once F4 exists; before that, via the Reminders section |
| Precise lookup | "How long does a sign in link last?" → `reference.limits` | Complete |
| Understanding | "Why did my contact not change after I edited the template?" → `templates.versions` | Complete |
| Recovery | Lost phone with two factor on → `troubleshoot.cannot-sign-in` → `security.recovery-codes` | Complete; forgotten password path blocked on D2 |
| Operator | README → `hosting.introduction` → install → background work → first administrator | Complete for a server install; Docker blocked on D18 |

### State at handoff

- **Ready to write now:** every brief marked ready, plus wave 0 corrections to the live developer docs.
- **Evidence still needed:** briefs marked needs verification (a running instance with a real queue and
  mail), and the hosted statements listed under
  [Hosted commitments](#hosted-commitments-that-need-an-authoritative-source).
- **Decisions needed:** D2, D5, D12, D18, D26 (fix the behaviour, or document the
  limitation). D3 and D4 affect copy the tutorial avoids but should still be fixed.
- **Portal functionality still to build:** F1 to F3 before anything publishes. Nothing in this roadmap
  has been built, and no usability testing has taken place.
