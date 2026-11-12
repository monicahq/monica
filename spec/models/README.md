# Models

One file per model. The number in the file name is the order the model must be implemented in, and that order is a dependency order: a model never depends on a model numbered after it, except where a file says otherwise and explains why.

The first six models already exist in `app/Models`. They come first because everything else hangs off them, and because most of them need changes before the rest can be built on top.

## The status in the file name

Every file name ends with where the model layer stands.

| Suffix | What it means |
|---|---|
| `-done` | The table matches the Fields list, the model, factory and tests exist, and every rule nothing blocks is enforced. |
| `-in-progress` | The model exists and something is missing: a column the Fields list asks for, or a rule that could be enforced today and is not. |
| `-not-yet` | No table and no model. |

The status covers the model layer only. Screens, the API and work waiting on a model further down the list do not count against a file, which is why a file can read `-done` and still carry a long **Not done yet** section.

## How to read a file

Every file follows the same shape.

- **Status** says whether the model exists today, and whether it has to change.
- **Spec** points at the sections of [the product specification](../spec.md) the model comes from.
- **What it is** explains the object in plain words, and why it exists as its own model rather than as a column somewhere else.
- **Fields** lists the columns, in the format `name`, `type (size)`, comment.
- **Relationships** lists what it points at and what points at it.
- **Rules** lists the invariants the application must enforce. These are the things tests should cover.
- **Open questions** lists what the specification does not settle yet. Nothing in that section should be implemented before it is answered.

## The order

### What exists today

| # | Model | Status |
|---|---|---|
| 001 | [Account](001-account-in-progress.md) | exists, built as far as the models it depends on allow |
| 002 | [User](002-user-done.md) | exists, built as far as the models it depends on allow |
| 003 | [MagicLink](003-magic-link-in-progress.md) | exists, no change |
| 004 | [Log](004-log-in-progress.md) | exists, built as far as the models it depends on allow |
| 005 | [EmailSent](005-email-sent-in-progress.md) | exists, minor changes |
| 006 | [PersonalAccessToken](006-personal-access-token-in-progress.md) | exists, changes needed |

Models 007 to 025, 027 to 028, 034, 035, 040 to 049 and 055 exist as well. Models 040 to 043 are the reminder chain: the rule, the concrete
deadlines it produces, the notifications that carry them and the preferences that say how. It was
built ahead of the journal it will one day also read from. The screens have since caught up with
most of it: a vault lists what is coming and what has gone out, and a rule has a page of its own.
The preferences are the part still without one. Each file says how far it is built and what it is
waiting on.

Models 026, 036 to 039 and 050 to 054 have no table and no model at all: relationships, the
journal, webhooks, modules and search are the whole of what is still to be started. Groups, 032 and
033, are being built in specs/085-groups, and 031 was dropped.

### The compartments and the files

| # | Model | Spec |
|---|---|---|
| 007 | [Vault](007-vault-in-progress.md) | 4.2 |
| 008 | [File](008-file-in-progress.md) | 11 |

### The personalisation engine

| # | Model | Spec |
|---|---|---|
| 009 | [ValueList](009-value-list-in-progress.md) | 5.4 |
| 010 | [ValueListItem](010-value-list-item-done.md) | 5.4 |
| 011 | [EntityType](011-entity-type-done.md) | 5.3 |
| 012 | [Template](012-template-in-progress.md) | 5.2, 6 |
| 013 | [StructureVersion](013-structure-version-done.md) | 6 |
| 014 | [Section](014-section-in-progress.md) | 5.2 |
| 015 | [SectionVersion](015-section-version-in-progress.md) | 6 |
| 016 | [StructureVersionSection](016-structure-version-section-done.md) | 5.2 |
| 017 | [Field](017-field-done.md) | 5.5, 5.6 |
| 018 | [VaultDefaultTemplate](018-vault-default-template-done.md) | 5.3 |

Two models sit out of order here, and both for the same reason: they were added to an engine that was already built and already referenced throughout the repository, and renumbering it would make existing documentation, code reviews and implementation history misleading.

Model 055, [StructureVersionPage](055-structure-version-page-done.md), is a later addition to this engine, and its table is created before the one of model 016 so that every section placement names its page from the first migration.

Model 056, [Structure](056-structure-done.md), is the container models 012 to 018 now hang off, so by dependency it belongs before all of them and its table is created first. Read it before 012. It exists because the engine had been named after one of its callers: a version belonged to a template, which made it impossible for an [LifeEventType](035-life-event-type-in-progress.md) to have fields without either borrowing a template it is not or growing a second engine beside the first.

### The records

| # | Model | Spec |
|---|---|---|
| 019 | [Contact](019-contact-done.md) | 4.3, 5.7, 8.2 |
| 020 | [Entity](020-entity-done.md) | 5.3 |
| 021 | [FieldValueRow](021-field-value-row-done.md) | 5.5 |
| 022 | [FieldValue](022-field-value-done.md) | 5.5 |
| 023 | [RecordSnapshot](023-record-snapshot-done.md) | 6, 12 |

### Relations between people

| # | Model | Spec |
|---|---|---|
| 024 | [RelationshipCategory](024-relationship-category-done.md) | 8.2 |
| 025 | [RelationshipType](025-relationship-type-done.md) | 8.2, 8.3 |
| 026 | [Relationship](026-relationship-not-yet.md) | 8.2 |

### Groups and duplicates

| # | Model | Spec |
|---|---|---|
| 031 | [GroupType](031-group-type-not-yet.md), dropped: a group has no kind | 8.2 |
| 032 | [Group](032-group-not-yet.md) | 8.2 |
| 033 | [GroupMember](033-group-member-not-yet.md) | 8.2 |
| 034 | [ContactMerge](034-contact-merge-done.md) | 8.3, 15 |

### The journal

| # | Model | Spec |
|---|---|---|
| 028 | [LifeEventCategory](028-life-event-category-done.md) | 9.3 |
| 035 | [LifeEventType](035-life-event-type-in-progress.md) | 9.3 |
| 036 | [LifeEvent](036-life-event-not-yet.md) | 9.2 |
| 037 | [LifeEventParticipant](037-life-event-participant-not-yet.md) | 9.2 |
| 038 | [Mood](038-mood-not-yet.md) | 9.4 |
| 039 | [JournalDay](039-journal-day-not-yet.md) | 9.4 |

### Reminders and notifications

| # | Model | Spec |
|---|---|---|
| 040 | [Reminder](040-reminder-done.md) | 10.1, 10.2 |
| 041 | [ReminderOccurrence](041-reminder-occurrence-done.md) | 10.1, 10.3 |
| 042 | [Notification](042-notification-done.md) | 10.4 |
| 043 | [NotificationPreference](043-notification-preference-done.md) | 10.4 |

### The marketplace

| # | Model | Spec |
|---|---|---|
| 044 | [MarketplaceCategory](044-marketplace-category-done.md) | 7 |
| 045 | [MarketplaceListing](045-marketplace-listing-done.md) | 7 |
| 046 | [MarketplaceListingVersion](046-marketplace-listing-version-done.md) | 7 |
| 047 | [MarketplaceInstallation](047-marketplace-installation-done.md) | 7 |

### Sharing, webhooks, modules, search

| # | Model | Spec |
|---|---|---|
| 048 | [ShareLink](048-share-link-done.md) | 4.4, 9.6 |
| 049 | [ShareLinkVisit](049-share-link-visit-done.md) | 4.4 |
| 050 | [WebhookSubscription](050-webhook-subscription-not-yet.md) | 14.3 |
| 051 | [WebhookDelivery](051-webhook-delivery-not-yet.md) | 14.4 |
| 052 | [Module](052-module-not-yet.md) | 3.9 |
| 053 | [ModuleInstallation](053-module-installation-not-yet.md) | 3.9 |
| 054 | [SearchIndexEntry](054-search-index-entry-not-yet.md) | 13 |

### Later additions to established areas

| # | Model | Spec |
|---|---|---|
| 055 | [StructureVersionPage](055-structure-version-page-done.md) | 5.2, 6 |
| 056 | [Structure](056-structure-done.md) | 5.2, 6, 7, 9.3 |
| 058 | [AccountExport](058-account-export-done.md) | 4.2, 11, 12 |
| 059 | [AccountImport](059-account-import-done.md) | 4.3, 11, 12 |
| 060 | [AccountImportItem](060-account-import-item-done.md) | 12 |
| 058 | [Location](058-location-in-progress.md) | 3.2, specs/086-location-management |
| 059 | [PossessionType](059-possession-type-in-progress.md) | 3.2, specs/088-possession-types |
| 060 | [PossessionFieldGroup](060-possession-field-group-in-progress.md) | 3.2, specs/088-possession-types |
| 061 | [PossessionField](061-possession-field-in-progress.md) | 3.2, specs/088-possession-types |
| 062 | [Possession](062-possession-in-progress.md) | 3.2, specs/091-possessions |
| 063 | [PossessionFieldValue](063-possession-field-value-in-progress.md) | 3.2, specs/091-possessions |
| 064 | [PossessionTag](064-possession-tag-in-progress.md) | 3.2, specs/092-possession-tags |
| 065 | [PossessionCondition](065-possession-condition-in-progress.md) | 3.2, specs/093-possession-conditions |
| 066 | [PossessionTransaction](066-possession-transaction-in-progress.md) | 3.2, specs/099-possession-transactions |

## Not modelled here

These appear in the specification but are explicitly out of the first version (section 15), so they get no file yet.

- **Task.** Deferred. It will reuse the reminder and notification machinery rather than grow its own, so nothing here should make that harder.
- **Calculated fields and aggregations.** Deferred. They are a field type, not a model.
- **Statistics.** Read from life events, so no storage of their own until the shape of the questions is known.
- **Mobile applications.** They consume the API, and add no model beyond the push token, which lives on the notification preference.
- **Advanced relationships.** Labels, qualifiers, repeated periods, recorded certainty, deductions and suggestions are possible later extensions of the explicit relationship graph. They have no model in the first version.

## Conventions used across the files

- Every table has `id`, `created_at` and `updated_at`. They are listed once per file and not commented on again.
- **Identifiers come in two kinds.** `id` is a bigint auto-increment. It is the primary key, and it is what foreign keys and joins use. `uuid` is the public identifier: it is what appears in URLs, in API payloads, in webhook bodies and over MCP, and it is never the integer. Nothing outside the database ever sees an `id`.
- A `uuid` is declared **immediately after `id`**, both in these files and in the migration, before the foreign keys and before the columns of the model itself.
- Not every table has one. It is carried by the objects a user can open, link to or address as a resource. Joins, pivots, value rows and log tables stay on integer keys alone, because nothing outside the product ever names one of those rows directly.
- Every table that holds user data carries an `account_id`, even when it could be reached through a parent. Scoping a query to an account must never depend on a join being written correctly.
- Every table that holds data belonging to a compartment also carries a `vault_id`, for the same reason.
- Foreign keys are declared at the bottom of a migration and never with `constrained()`, per `.claude/skills/migrations`.
- Deletion is soft wherever the specification says a thing can come back (section 3.3). Where it is not soft, the file says why.
