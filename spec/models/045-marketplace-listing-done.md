# 045. MarketplaceListing

**Status:** exists (`app/Models/MarketplaceListing.php`), built as far as the author path goes; the installer path is not written yet
**Spec:** 7, 3.7, 9.3

## What it is

Something published to the catalogue: a template, a section, or an activity type. It is the public face of a structure somebody built.

Section 7 describes the author's path in three steps. They build something, and it is private by default, visible to them alone. When they want to share it, they pick one category from the list we maintain. They submit, we moderate, and it becomes public once validated, with a refusal being explained rather than silent.

The listing is the stable public identity. The content lives in [MarketplaceListingVersion](046-marketplace-listing-version-done.md), mirroring the same split as templates and sections, and for the same reason.

## Fields

```
id                          int                             primary key
uuid                        string (36)                     unguessable identifier used by the API, alongside the public slug
account_id                  int                             account that published it
marketplace_category_id     int                             category it was filed under
listable_type               string (255)                    class of what is published, a template, a section or an activity type
listable_id                 int                             id of that object in the author's account
slug                        string (128)                    stable identifier used in the public URL
name                        string (255)                    name shown in the catalogue, which may differ from the author's internal name
summary                     string (255)                    one line describing what it is for, shown on the card
description                 text                            the full description, shown on the listing page
locale                      string (5)                      language the labels inside it are written in, shown and filtered on
author_name                 string (255)                    name shown as the author, which need not be the account holder's real name
status                      enum (draft, submitted, published, rejected, withdrawn)  where it is in the publication path
published_version_id        int                             version currently offered for installation, null before the first is approved
rejection_reason            text                            why it was refused, so the refusal is explained rather than bare
moderated_at                datetime                        when the decision was taken
moderated_by                string (255)                    who took it, on our side
install_count               int                             how many accounts installed it, shown publicly as a quality signal
first_published_at          datetime                        when it first became public
created_at                  datetime
updated_at                  datetime
deleted_at                  datetime
```

## Relationships

- `belongsTo` [Account](001-account-in-progress.md), [MarketplaceCategory](044-marketplace-category-done.md)
- `morphTo` listable, a [Template](012-template-in-progress.md), a [Section](014-section-in-progress.md) or an [ActivityType](035-activity-type-not-yet.md)
- `hasMany` [MarketplaceListingVersion](046-marketplace-listing-version-done.md)
- `hasMany` [MarketplaceInstallation](047-marketplace-installation-done.md)

## Rules

- Private by default. Nothing reaches the catalogue without a deliberate submission (7).
- **A section can be published on its own**, with no template or page around it (7). Section 7 names this as what lowers the barrier to contributing. Pages belong to a template version and are never marketplace items. Any design that requires wrapping a block in either one to share it has lost the point.
- An activity type is publishable the same way (9.3), carrying its own field structure with it.
- One category, chosen from ours. No free tags.
- A refusal carries a reason and the reason is shown to the author (7).
- `locale` is displayed in the catalogue and filterable (3.7). A listing is written in one language, and an installer in another gets the labels as written, which is fine because what they install is a copy they can rename freely.
- `install_count` is public, as a quality signal (7).
- Withdrawing a listing removes it from the catalogue and changes nothing for anybody who already installed it. What they hold is their own copy.

## What is built

The author path and the moderation path: `CreateMarketplaceListing`, `UpdateMarketplaceListing`, `SubmitMarketplaceListing`, `ApproveMarketplaceListingVersion`, `RejectMarketplaceListingVersion` and `WithdrawMarketplaceListing`.

Two things the specification asks for are not built yet and are named here so nobody assumes otherwise. `listable` accepts a template or a section and not an activity type, since [ActivityType](035-activity-type-not-yet.md) does not exist. And moderation has no user behind it: `moderated_by` is written as a name, because there is no instance administrator anywhere in the product yet, so the two moderation actions take one rather than a `User` and check no account.

## Open questions

- **Whether every new version needs fresh moderation** (16). Moderating each one is safe and slow. Trusting an author already validated is fast and lets a validated listing turn into something else. A middle path is to moderate the first version of every listing and spot check later ones, which is a policy decision rather than a schema one.
- Whether an author can be paid for a listing. Nothing in the specification suggests it, and the columns here neither allow nor forbid it.
