# Models

One file per model. The number in the file name is the order the model must be implemented in, and that order is a dependency order: a model never depends on a model numbered after it, except where a file says otherwise and explains why.

The first six models already exist in `app/Models`. They come first because everything else hangs off them, and because most of them need changes before the rest can be built on top.

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
| 001 | [Account](001-account.md) | exists, changes needed |
| 002 | [User](002-user.md) | exists, changes needed |
| 003 | [MagicLink](003-magic-link.md) | exists, no change |
| 004 | [Log](004-log.md) | exists, changes needed |
| 005 | [EmailSent](005-email-sent.md) | exists, minor changes |
| 006 | [PersonalAccessToken](006-personal-access-token.md) | exists, changes needed |

### The compartments and the files

| # | Model | Spec |
|---|---|---|
| 007 | [Vault](007-vault.md) | 4.2 |
| 008 | [File](008-file.md) | 11 |

### The personalisation engine

| # | Model | Spec |
|---|---|---|
| 009 | [ValueList](009-value-list.md) | 5.4 |
| 010 | [ValueListItem](010-value-list-item.md) | 5.4 |
| 011 | [EntityType](011-entity-type.md) | 5.3 |
| 012 | [Template](012-template.md) | 5.2, 6 |
| 013 | [TemplateVersion](013-template-version.md) | 6 |
| 014 | [Section](014-section.md) | 5.2 |
| 015 | [SectionVersion](015-section-version.md) | 6 |
| 016 | [TemplateVersionSection](016-template-version-section.md) | 5.2 |
| 017 | [Field](017-field.md) | 5.5, 5.6 |
| 018 | [VaultDefaultTemplate](018-vault-default-template.md) | 5.3 |

### The records

| # | Model | Spec |
|---|---|---|
| 019 | [Contact](019-contact.md) | 4.3, 5.7, 8.2 |
| 020 | [Entity](020-entity.md) | 5.3 |
| 021 | [FieldValueRow](021-field-value-row.md) | 5.5 |
| 022 | [FieldValue](022-field-value.md) | 5.5 |
| 023 | [RecordSnapshot](023-record-snapshot.md) | 6, 12 |

### Relations between people

| # | Model | Spec |
|---|---|---|
| 024 | [RelationshipType](024-relationship-type.md) | 8.2 |
| 025 | [RelationshipLabel](025-relationship-label.md) | 8.2 |
| 026 | [Relationship](026-relationship.md) | 8.2 |
| 027 | [RelationshipPeriod](027-relationship-period.md) | 8.2 |
| 028 | [RelationshipQualifier](028-relationship-qualifier.md) | 8.2 |
| 029 | [RelationshipQualifierAssignment](029-relationship-qualifier-assignment.md) | 8.2 |
| 030 | [RelationshipSuggestion](030-relationship-suggestion.md) | 8.3 |

### Groups and duplicates

| # | Model | Spec |
|---|---|---|
| 031 | [GroupType](031-group-type.md) | 8.2 |
| 032 | [Group](032-group.md) | 8.2 |
| 033 | [GroupMember](033-group-member.md) | 8.2 |
| 034 | [ContactMerge](034-contact-merge.md) | 8.3, 15 |

### The journal

| # | Model | Spec |
|---|---|---|
| 035 | [ActivityType](035-activity-type.md) | 9.3 |
| 036 | [Activity](036-activity.md) | 9.2 |
| 037 | [ActivityParticipant](037-activity-participant.md) | 9.2 |
| 038 | [Mood](038-mood.md) | 9.4 |
| 039 | [JournalDay](039-journal-day.md) | 9.4 |

### Reminders and notifications

| # | Model | Spec |
|---|---|---|
| 040 | [Reminder](040-reminder.md) | 10.1, 10.2 |
| 041 | [ReminderOccurrence](041-reminder-occurrence.md) | 10.1, 10.3 |
| 042 | [Notification](042-notification.md) | 10.4 |
| 043 | [NotificationPreference](043-notification-preference.md) | 10.4 |

### The marketplace

| # | Model | Spec |
|---|---|---|
| 044 | [MarketplaceCategory](044-marketplace-category.md) | 7 |
| 045 | [MarketplaceListing](045-marketplace-listing.md) | 7 |
| 046 | [MarketplaceListingVersion](046-marketplace-listing-version.md) | 7 |
| 047 | [MarketplaceInstallation](047-marketplace-installation.md) | 7 |

### Sharing, webhooks, modules, search

| # | Model | Spec |
|---|---|---|
| 048 | [ShareLink](048-share-link.md) | 4.4, 9.6 |
| 049 | [ShareLinkVisit](049-share-link-visit.md) | 4.4 |
| 050 | [WebhookSubscription](050-webhook-subscription.md) | 14.3 |
| 051 | [WebhookDelivery](051-webhook-delivery.md) | 14.4 |
| 052 | [Module](052-module.md) | 3.9 |
| 053 | [ModuleInstallation](053-module-installation.md) | 3.9 |
| 054 | [SearchIndexEntry](054-search-index-entry.md) | 13 |

## Not modelled here

These appear in the specification but are explicitly out of the first version (section 15), so they get no file yet.

- **Task.** Deferred. It will reuse the reminder and notification machinery rather than grow its own, so nothing here should make that harder.
- **Calculated fields and aggregations.** Deferred. They are a field type, not a model.
- **Statistics.** Read from activities, so no storage of their own until the shape of the questions is known.
- **Mobile applications.** They consume the API, and add no model beyond the push token, which lives on the notification preference.

## Conventions used across the files

- Every table has `id`, `created_at` and `updated_at`. They are listed once per file and not commented on again.
- Every table that holds user data carries an `account_id`, even when it could be reached through a parent. Scoping a query to an account must never depend on a join being written correctly.
- Every table that holds data belonging to a compartment also carries a `vault_id`, for the same reason.
- Foreign keys are declared at the bottom of a migration and never with `constrained()`, per `.claude/skills/migrations`.
- Deletion is soft wherever the specification says a thing can come back (section 3.3). Where it is not soft, the file says why.
