# 046. MarketplaceListingVersion

**Status:** exists (`app/Models/MarketplaceListingVersion.php`), built as far as the author path goes; nothing consumes the payload yet
**Spec:** 7, 6, 3.1

## What it is

One published state of a listing, with the payload somebody installs and the change note they read before deciding whether to update.

It is separate from the listing for the same reason a template version is separate from a template (6): what is published is frozen, and what people rely on has to keep working. It is also what makes the update flow in section 7 possible. When an author publishes a new version, everybody who installed the previous one is notified with the change note, and each of them decides whether to apply it or stay where they are.

## Fields

```
id                          int                             primary key
marketplace_listing_id      int                             listing this is a version of
version_number              int                             incrementing number within the listing
payload                     json                            the whole structure, self contained, with its pages, section placements, fields and value lists
change_note                 text                            what changed since the previous version, shown to every installer
status                      enum (submitted, approved, rejected, withdrawn)  where it is in moderation
rejection_reason            text                            why it was refused
approved_at                 datetime                        when it was approved
withdrawn_at                datetime                        when it was pulled
previous_version_id         int                             version it was built from
install_count               int                             how many accounts installed this precise version
checksum                    string (64)                     hash of the payload, so an install can prove it got what was published
created_at                  datetime
updated_at                  datetime
```

## Relationships

- `belongsTo` [MarketplaceListing](045-marketplace-listing-done.md)
- `belongsTo` self as previous
- `hasMany` [MarketplaceInstallation](047-marketplace-installation-done.md)

## Rules

- An approved version is immutable, like every other published version in the product (6).
- The payload is self contained. For a template it carries the pages, their stable keys and order, the placement of sections on those pages, the fields, the value list items they reference and, when it applies to an entity type, the entity type itself. A section published alone carries no page. Installing must never need anything from the author's account at install time, because installing is copying and the copy has to stand alone.
- An activity type payload carries the type and its field structure, but not the author's account owned activity category. The installer chooses one of their own categories when making the local copy ([ActivityCategory](028-activity-category-done.md)).
- **There is never a forced synchronisation** (7, 3.1). A new version notifies. It never updates anything by itself. This is the single most important rule of the marketplace and it is a direct application of the principle that nothing changes under somebody's feet.
- Withdrawing a version stops new installs and changes nothing for existing ones.
- `checksum` lets an install verify the payload it received matches what was approved.

## Open questions

- Whether a version can be marked as a security or correctness fix, which would justify a louder notification while still not forcing anything. Not in the specification, and it is the kind of thing that gets abused into forced updates if it is not carefully bounded.
