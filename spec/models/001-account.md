# 001. Account

**Status:** exists (`app/Models/Account.php`), changes needed
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

## What changes

The table today carries `plan` (a `PlanEnum`) and `trial_ends_at`, both of which describe a subscription. Section 3.4 removes the subscription: the hosted version has a limited free offer and a single purchase that unlocks everything, with no plan to manage and no trial to expire.

- Drop `plan` and `PlanEnum`. Replace with `has_lifetime_access` and `purchased_at`.
- Drop `trial_ends_at` and `isOnTrial()`. The free offer replaces the trial, and it does not end.
- Add `storage_used_in_bytes` and `storage_limit_in_bytes` (11). The used total is maintained by the file model rather than recomputed, because the quota is shown on every upload screen.
- The `users()` relationship becomes `hasOne` rather than `hasMany` (4.1).
- Add the relationships to everything the account owns: vaults, value lists, activity types, moods, templates, sections, entity types, relationship types, group types, share links, webhook subscriptions, modules, notification preferences.

## Relationships

- `hasOne` [User](002-user.md)
- `hasMany` [Vault](007-vault.md)
- `hasMany` [ValueList](009-value-list.md), [EntityType](011-entity-type.md), [Template](012-template.md), [Section](014-section.md), [ActivityType](035-activity-type.md), [Mood](038-mood.md), [GroupType](031-group-type.md), [RelationshipType](024-relationship-type.md), [RelationshipQualifier](028-relationship-qualifier.md)
- `hasMany` [File](008-file.md), [Log](004-log.md), [EmailSent](005-email-sent.md), [ShareLink](048-share-link.md), [WebhookSubscription](050-webhook-subscription.md), [ModuleInstallation](053-module-installation.md)
- `hasOne` [NotificationPreference](043-notification-preference.md)

## Rules

- Creating an account seeds it: one user, one default vault, the default value lists (5.4), the default activity types (9.3), the default moods (9.4), the default relationship types and qualifiers (8.2), the default group types, the default contact template, and the `Moi` contact in the default vault (4.3). None of this is optional, because the product has to be usable in the first minute (2.1).
- Everything seeded is ordinary data the user can rename, reorder or disable. Nothing seeded is protected from editing, except where an invariant in another file says otherwise.
- A self hosted account is never metered: `storage_limit_in_bytes` is null and no rate limit applies (3.4, 14.5).
- Deleting an account deletes everything under it. This is the one hard delete in the product, and it must go through a confirmed, explicit action with a warning, per the standing rule on destructive actions.

## Open questions

- Does the free offer limit contacts, vaults, storage, or all three? The specification says "limitée" without saying limited in what. Until it is answered, only `storage_limit_in_bytes` is modelled, and any other limit is read from configuration rather than stored per account.
- The specification asks for an admin surface (section 16, last bullet). Whatever it turns out to be, it reads accounts. Nothing here should assume the account is only ever read by the person who owns it.
