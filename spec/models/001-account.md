# 001. Account

**Status:** exists (`app/Models/Account.php`), built as far as the models it depends on allow
**Spec:** 3.4, 4, 4.1, 11

## What it is

The root of everything. Every row of user data in the application hangs off an account, directly or through a vault. An account is the unit of billing on the hosted version, the unit of configuration everywhere, and the boundary no query may cross.

Erica is mono-user (4.1), so an account holds exactly one person. The account still exists as its own object rather than being folded into the user, for three reasons: it is what the storage quota is counted against, it is what a purchase is attached to, and it is the natural owner of everything that is configured once and reused everywhere (value lists, activity types, templates, notification preferences).

## Fields

```
id                          int                             primary key
is_self_hosted              boolean                         whether the instance runs self hosted, where nothing is metered
has_lifetime_access         boolean                         whether the one time purchase unlocking the full product was made
purchased_at                datetime                        when the one time purchase was made, null on the free offer and self hosted
billing_email               string (255)                    address invoices are sent to, null when there is nothing to invoice
storage_used_in_bytes       int                             sum of the size of every file the account holds, kept as a running total
storage_limit_in_bytes      int                             ceiling above which no new file is accepted, null when there is no ceiling
onboarding_completed_at     datetime                        when the account finished the first run, null while it has not
created_at                  datetime
updated_at                  datetime
```

Section 3.4 removes the subscription the table used to carry: the hosted version has a limited free offer and a single purchase that unlocks everything, with no plan to manage and no trial to expire. The used storage total is maintained by the file model rather than recomputed, because the quota is shown on every upload screen.

## Relationships

- `hasOne` [User](002-user.md)
- `hasMany` [Vault](007-vault.md)
- `hasMany` [ValueList](009-value-list.md), [EntityType](011-entity-type.md), [Template](012-template.md), [Section](014-section.md), [ActivityType](035-activity-type.md), [Mood](038-mood.md), [GroupType](031-group-type.md), [RelationshipType](024-relationship-type.md), [RelationshipQualifier](028-relationship-qualifier.md)
- `hasMany` [File](008-file.md), [Log](004-log.md), [EmailSent](005-email-sent.md), [ShareLink](048-share-link.md), [WebhookSubscription](050-webhook-subscription.md), [ModuleInstallation](053-module-installation.md)
- `hasOne` [NotificationPreference](043-notification-preference.md)

## Rules

- Creating an account seeds it: one user, one default vault, the default value lists (5.4), the default activity types (9.3), the default moods (9.4), the default relationship types and qualifiers (8.2), the default group types, the default contact template with its first page, and the `Moi` contact in the default vault (4.3). None of this is optional, because the product has to be usable in the first minute (2.1).
- Everything seeded is ordinary data the user can rename, reorder or disable. Nothing seeded is protected from editing, except where an invariant in another file says otherwise: a supplied value list is emptied rather than deleted, per [ValueList](009-value-list.md).
- A self hosted account is never metered: `storage_limit_in_bytes` is null and no rate limit applies (3.4, 14.5).
- Deleting an account deletes everything under it. This is the one hard delete in the product, and it must go through a confirmed, explicit action with a warning, per the standing rule on destructive actions.

## Not done yet

- The rest of the seeding rule. Creating an account creates the user, the default vault and the supplied value lists, then asks a job for the supplied contact template. All nine lists are seeded, including the countries, languages and currencies section 5.4 names, each with ten choices to start from (see [ValueList](009-value-list.md)). The activity types, moods, relationship types and qualifiers, group types and the `Moi` contact all wait on models 011 and later.
- The template arrives a moment after the account rather than with it, since `CreateDefaultTemplate` is queued. Nothing reads it yet, so nothing notices, but whatever first offers a contact form has to cope with an account whose template has not landed. Whether the rest of the seeding joins it on the queue is not settled: the value lists are nine lists the template itself reads, so they stay where they are.
- The supplied contact template is deliberately conservative. It asks for a category, a gender, a birthday, an email address, a phone number, a website and a note, and nothing else: an address, a repeater of phone numbers, a relation and a file all wait on the screens that would let somebody edit them afterwards. The single phone field is a stopgap and is meant to become a repeater of a label and a number once the builder can author one.
- The supplied template does not have its required "General" page yet. [TemplateVersionPage](055-template-version-page.md) adds it and backfills the published version without changing the order or visibility of the supplied sections.
- The vault does not point at the template it applies to a new contact. `VaultDefaultTemplate` does not exist yet, and the Personal vault should point at the published version of the supplied template once it does.
- The relationships to the models that do not exist yet. Only user, vaults, value lists, logs and emails sent are wired.
- One user per account is a `hasOne` on the model, but the database does not enforce it. The unique index on `users.account_id` belongs to [User](002-user.md).
- The rest of the account deletion. `DestroyAccount` is wired to the profile screen, behind a warning and the address typed out in full, and it clears the photo files and the API keys before cascading through the foreign keys. Every new table has to join that cascade, and anything it writes outside the database has to be removed there too.

## Open questions

- Does the free offer limit contacts, vaults, storage, or all three? The specification says "limitée" without saying limited in what. Until it is answered, only `storage_limit_in_bytes` is modelled, and any other limit is read from configuration rather than stored per account. The storage ceiling is copied from `monica.free_storage_limit_in_bytes` onto the account when it is created, so raising the number does not raise it for the accounts that already exist. Whether the purchase lifts that ceiling, and to what, is not settled either, so the purchase leaves it alone for now.
- The specification asks for an admin surface (section 16, last bullet). Whatever it turns out to be, it reads accounts. Nothing here should assume the account is only ever read by the person who owns it.
