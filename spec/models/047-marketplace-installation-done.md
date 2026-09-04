# 047. MarketplaceInstallation

**Status:** exists (`app/Models/MarketplaceInstallation.php`), table and model only; nothing installs yet
**Spec:** 7, 3.1, 5.4

## What it is

The link between something somebody installed and where it came from.

Section 7 states the rule this model exists to serve, and it is worth quoting because everything else follows from it: **installing is copying**. The installer gets their own private copy, which they can change freely without affecting the original and without depending on its author. What the copy keeps is a trace of its origin, so that when the author publishes a new version, the installer is notified with the change note and chooses whether to apply it or stay put.

The trace is this row. Without it there is no update notification. With it, and only with it, section 3.1 is honoured: no forced synchronisation, ever.

## Fields

```
id                          int                             primary key
account_id                  int                             account that installed it
marketplace_listing_id      int                             listing it came from
marketplace_listing_version_id  int                          exact version that was copied
installable_type            string (255)                    class of the local copy, a template, a section or an activity type
installable_id              int                             id of that local copy
installed_at                datetime                        when the copy was made
has_local_changes           boolean                         whether the installer edited their copy since, which makes an update riskier
update_available_version_id int                             newer approved version, null when the copy is current
update_notified_at          datetime                        when the installer was told about it
update_decision             enum (pending, applied, declined)  what they decided, null when nothing new has appeared
update_decided_at           datetime                        when they decided
value_list_mapping          json                            how the payload's value lists were resolved locally, copied or merged
created_at                  datetime
updated_at                  datetime
deleted_at                  datetime
```

## Relationships

- `belongsTo` [Account](001-account-in-progress.md), [MarketplaceListing](045-marketplace-listing-done.md), [MarketplaceListingVersion](046-marketplace-listing-version-done.md)
- `morphTo` installable, the local copy

## Rules

- Installing copies. The local copy is fully editable and has no runtime dependency on the listing (7).
- A new approved version raises `update_available_version_id` and produces a notification carrying the change note ([Notification](042-notification-done.md)). It changes nothing else.
- The installer applies or declines. Declining is a real, remembered choice, not a postponement, and it does not stop them being told about the version after that.
- Applying an update creates a **new version of the local copy** ([TemplateVersion](013-template-version-done.md) or [SectionVersion](015-section-version-in-progress.md)). A template update copies its pages and section placements into that new local version. It never edits a published local version, and records already on the old version stay there until moved deliberately (6).
- `has_local_changes` is shown before applying an update, because an update onto an edited copy is where a surprise would come from.
- Uninstalling removes the origin trace and leaves the copy in place. Nothing the user made is taken away.

## What is built

The table, the model and its factory, and nothing else. There is no install action, no update notification and no way to apply or decline one. [Notification](042-notification-done.md) now exists and would carry the update notice, so what installing still needs is the value list question below answered, which it is not. The columns are all here, so the day that work happens it adds actions and touches no schema.

## Open questions

- **How the payload's value lists are resolved locally** (16, and [ValueList](009-value-list-in-progress.md)). Copying produces duplicates ("Countries" twice). Matching against the installer's equivalent list is tidier and can silently change meaning, which section 3.1 forbids. `value_list_mapping` is shaped to record either answer, and the recommendation stands as: copy on install, then offer an explicit merge. It needs deciding before the marketplace ships.
- What happens when an update removes a field the installer's records have values in. The mechanism exists already ([FieldValue](022-field-value-done.md) detaches rather than deletes), so the question is only how loudly the update screen says so.
