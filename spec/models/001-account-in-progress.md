# 001. Account

**Status:** exists (`app/Models/Account.php`), built as far as the models it depends on allow
**Spec:** 3.4, 4, 4.1, 11

## What it is

The root of everything. Every row of user data in the application hangs off an account, directly or through a vault. An account is the unit of billing on the hosted version, the unit of configuration everywhere, and the boundary no query may cross.

Erica is mono-user (4.1), so an account holds exactly one person. The account still exists as its own object rather than being folded into the user, for three reasons: it is what the storage quota is counted against, it is what a purchase is attached to, and it is the natural owner of everything that is configured once and reused everywhere (value lists, life event types, templates, notification preferences).

## Fields

```
id                          int                             primary key
has_lifetime_access         boolean                         whether full access was bought once or granted by an administrator
purchased_at                datetime                        when the purchase was made or the grant given, null otherwise
billing_email               string (255)                    address invoices are sent to, null when there is nothing to invoice
storage_used_in_bytes       int                             sum of the size of every file the account holds, kept as a running total
storage_limit_in_bytes      int                             free ceiling stamped at creation, null when the instance meters nobody
onboarding_completed_at     datetime                        when the account finished the first run, null while it has not
created_at                  datetime
updated_at                  datetime
```

Section 3.4 puts two ways of holding full access beside the free offer. A purchase made once is recorded here, in `has_lifetime_access`, and so is a grant by an administrator, which exists for the purchase that did not register. A subscription, billed monthly or yearly, is recorded by the billing package in its own tables and read through the account. Full access is the union of the two, and the caps read only whether it is held, never how. There is no trial and no plan to manage. The used storage total is maintained by the file model rather than recomputed, because the quota is shown on every upload screen.

## Relationships

- `hasOne` [User](002-user-done.md)
- `hasMany` [Vault](007-vault-in-progress.md)
- `hasMany` [ValueList](009-value-list-in-progress.md), [EntityType](011-entity-type-done.md), [Template](012-template-in-progress.md), [Section](014-section-in-progress.md), [LifeEventCategory](028-life-event-category-done.md), [LifeEventType](035-life-event-type-in-progress.md), [Mood](038-mood-not-yet.md), [GroupType](031-group-type-not-yet.md), [RelationshipCategory](024-relationship-category-done.md), [RelationshipType](025-relationship-type-done.md)
- `hasMany` [File](008-file-in-progress.md), [Log](004-log-in-progress.md), [EmailSent](005-email-sent-in-progress.md), [ShareLink](048-share-link-done.md), [WebhookSubscription](050-webhook-subscription-not-yet.md), [ModuleInstallation](053-module-installation-not-yet.md)
- `hasOne` [NotificationPreference](043-notification-preference-done.md)

## Rules

- Creating an account seeds it: one user, one default vault, the default value lists (5.4), the default life event categories and types (9.3), the default moods (9.4), the default relationship categories and types (8.2), the default group types, the default contact template with its first page, and the `Moi` contact in the default vault (4.3). None of this is optional, because the product has to be usable in the first minute (2.1).
- Seeded data can be renamed, reordered or disabled unless another model protects more of it. Supplied relationship categories and types cannot be deleted, and the stable direction and product behaviour of supplied relationship types cannot be edited ([RelationshipCategory](024-relationship-category-done.md), [RelationshipType](025-relationship-type-done.md)).
- Whether accounts are metered is the instance's setting, `monica.self_hosted`, read where a cap is checked and never stamped on the account. A self hosted instance meters nobody: `storage_limit_in_bytes` is null and no rate limit applies (3.4, 14.5). There is no second switch, so a self hoster cannot turn a paywall on for their own users.
- The free offer caps vaults, contacts and storage (3.4). The vault and contact ceilings are read from configuration at the moment of the check, `monica.free_vault_limit` and `monica.free_contact_limit`, one and thirty by default, so raising one raises it for every account. Contacts are counted across every vault of the account, archived ones included, leaving out each vault's Me contact and any partial contact.
- The storage ceiling is copied from `monica.free_storage_limit_in_bytes` onto the account when it is created, so raising the number does not raise it for the accounts that already exist. While full access is held the effective ceiling is `monica.paid_storage_limit_in_bytes`, ten gigabytes by default, and the stamped value is never rewritten, so a refund or a lapse puts nothing back.
- An account above a cap keeps everything it holds and can read and edit all of it. Only adding more is refused, and the refusal names the cap and where to lift it. The check lives where the operation lives, so the web, the API and whatever comes later are all bound by it.
- Full access is unlocked by the payment provider's confirmation and never by the return from checkout. The confirmation records when, under which billing address, under which provider identifier and for which offer, and one that arrives twice records once.
- Deleting an account deletes everything under it. This is the one hard delete in the product, and it must go through a confirmed, explicit action with a warning, per the standing rule on destructive actions.

## Not done yet

- The rest of the seeding rule. Creating an account writes the account, the user and the default vault, and asks a chain of jobs for everything else: the notification preferences ([NotificationPreference](043-notification-preference-done.md)), the supplied value lists, the life event categories, the supplied contact template, the life event types and the relationship vocabulary. All nine lists are seeded, including the countries, languages and currencies section 5.4 names, each with ten choices to start from (see [ValueList](009-value-list-in-progress.md)). The moods, group types and the `Moi` contact still wait on models 011 and later.
- Everything but the account, the user and the vault arrives a moment after sign-up rather than with it. Those three stay because they are what somebody meets on signing in: the vault list offers anybody holding no vault the way to make one, so a vault arriving late would invite them to make a second one. The rest is one `Bus::chain` on the `high` queue, dispatched once those rows are committed. Each link is a job that calls the action doing the work, rather than holding that work itself, so the same rules apply wherever the operation is reached from. It is a chain rather than six dispatches because the order matters and nothing in it complains when it is wrong: the template reads the value lists by key and writes nothing where it finds none, and a nature whose category is missing is skipped without a word. The preferences go first, being the only one whose absence a reader meets as an error rather than an empty screen. Each job returns early on an account that already holds its rows, so a retry is a no-op rather than a duplicate key.
- The supplied contact template asks a little more than it did and still not everything. Three pages, six sections: a category, a gender and a birthday; an email address, a phone number and a website; an address; a repeater of documents and one of photographs; a note. A relation and a repeater of labelled phone numbers wait on the controls that would let somebody fill one in. The single phone field is still a stopgap.
- The relationships to the models that do not exist yet. Only user, vaults, contacts, value lists, entity types, logs, emails sent, notifications and the notification preferences are wired; templates, sections and files are reached through the models that carry them rather than from here.
- One user per account is a `hasOne` on the model, but the database does not enforce it. The unique index on `users.account_id` belongs to [User](002-user-done.md).
- Buying full access. The checkout, the provider's confirmation and the subscription's tables belong to the billing package and are not wired yet; only the administrator's grant exists.
- The rest of the account deletion. `DestroyAccount` is wired to the profile screen, behind a warning and the address typed out in full, and it clears the photo files and the API keys before cascading through the foreign keys. Every new table has to join that cascade, and anything it writes outside the database has to be removed there too.

## Open questions

- The specification asks for an admin surface (section 16, last bullet). Whatever it turns out to be, it reads accounts. Nothing here should assume the account is only ever read by the person who owns it.
