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

- `hasOne` [User](002-user-done.md)
- `hasMany` [Vault](007-vault-in-progress.md)
- `hasMany` [ValueList](009-value-list-in-progress.md), [EntityType](011-entity-type-done.md), [Template](012-template-in-progress.md), [Section](014-section-in-progress.md), [ActivityType](035-activity-type-not-yet.md), [Mood](038-mood-not-yet.md), [GroupType](031-group-type-not-yet.md), [RelationshipType](024-relationship-type-not-yet.md), [RelationshipQualifier](028-relationship-qualifier-not-yet.md)
- `hasMany` [File](008-file-in-progress.md), [Log](004-log-in-progress.md), [EmailSent](005-email-sent-in-progress.md), [ShareLink](048-share-link-done.md), [WebhookSubscription](050-webhook-subscription-not-yet.md), [ModuleInstallation](053-module-installation-not-yet.md)
- `hasOne` [NotificationPreference](043-notification-preference-done.md)

## Rules

- Creating an account seeds it: one user, one default vault, the default value lists (5.4), the default activity types (9.3), the default moods (9.4), the default relationship types and qualifiers (8.2), the default group types, the default contact template with its first page, and the `Moi` contact in the default vault (4.3). None of this is optional, because the product has to be usable in the first minute (2.1).
- Everything seeded is ordinary data the user can rename, reorder or disable. Nothing seeded is protected from editing, except where an invariant in another file says otherwise: a supplied value list is emptied rather than deleted, per [ValueList](009-value-list-in-progress.md).
- A self hosted account is never metered: `storage_limit_in_bytes` is null and no rate limit applies (3.4, 14.5).
- Deleting an account deletes everything under it. This is the one hard delete in the product, and it must go through a confirmed, explicit action with a warning, per the standing rule on destructive actions.

## Not done yet

- The rest of the seeding rule. Creating an account creates the user, the default vault, the supplied value lists and the notification preferences ([NotificationPreference](043-notification-preference-done.md)), then asks a job for the supplied contact template. All nine lists are seeded, including the countries, languages and currencies section 5.4 names, each with ten choices to start from (see [ValueList](009-value-list-in-progress.md)). The activity types, moods, relationship types and qualifiers, group types and the `Moi` contact all wait on models 011 and later.
- The template arrives a moment after the account rather than with it, since `CreateDefaultTemplate` is queued. It is read as soon as it lands: the job points every vault of the account at the version it publishes ([VaultDefaultTemplate](018-vault-default-template-done.md)), and that is what a new contact starts from. What is not settled is whether the rest of the seeding joins it on the queue: the value lists are nine lists the template itself reads, so they stay where they are.
- The supplied contact template asks a little more than it did and still not everything. Three pages, six sections: a category, a gender and a birthday; an email address, a phone number and a website; an address; a repeater of documents and one of photographs; a note. A relation and a repeater of labelled phone numbers wait on the controls that would let somebody fill one in. The single phone field is still a stopgap.
- The relationships to the models that do not exist yet. Only user, vaults, contacts, value lists, entity types, logs, emails sent, notifications and the notification preferences are wired; templates, sections and files are reached through the models that carry them rather than from here.
- One user per account is a `hasOne` on the model, but the database does not enforce it. The unique index on `users.account_id` belongs to [User](002-user-done.md).
- The rest of the account deletion. `DestroyAccount` is wired to the profile screen, behind a warning and the address typed out in full, and it clears the photo files and the API keys before cascading through the foreign keys. Every new table has to join that cascade, and anything it writes outside the database has to be removed there too.

## Open questions

- Does the free offer limit contacts, vaults, storage, or all three? The specification says "limitée" without saying limited in what. Until it is answered, only `storage_limit_in_bytes` is modelled, and any other limit is read from configuration rather than stored per account. The storage ceiling is copied from `monica.free_storage_limit_in_bytes` onto the account when it is created, so raising the number does not raise it for the accounts that already exist. Whether the purchase lifts that ceiling, and to what, is not settled either, so the purchase leaves it alone for now.
- The specification asks for an admin surface (section 16, last bullet). Whatever it turns out to be, it reads accounts. Nothing here should assume the account is only ever read by the person who owns it.
