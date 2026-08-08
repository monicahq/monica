# 044. MarketplaceCategory

**Status:** to create
**Spec:** 7

## What it is

One of the categories an author picks from when publishing: Health, Finance, Animals, and so on.

Section 7 is specific and the reasoning is worth keeping: the list is maintained by us, and there are no free tags. A catalogue with open keywords becomes unreadable, and the whole value of the marketplace is that somebody can find the medical follow up block without wading through forty near duplicates.

This is the one model in the folder that is not owned by an account. It is instance level data, shared by every account, seeded from `database/data`.

## Fields

```
id                          int                             primary key
key                         string (64)                     stable identifier, used in URLs and by the API
name_translation_key        string (255)                    translation key of the name, since the catalogue reads in the user's language
description_translation_key string (255)                    translation key of the description
icon                        string (64)                     icon shown on the category card
parent_id                   int                             category it sits under, null for a top level one
position                    int                             order categories are listed in
is_active                   boolean                         whether new content can be published into it
listing_count               int                             how many published listings it holds
created_at                  datetime
updated_at                  datetime
```

## Relationships

- `belongsTo` self as parent
- `hasMany` self as children
- `hasMany` [MarketplaceListing](045-marketplace-listing.md)

## Rules

- Only we create categories. There is no user path to adding one, and no free tagging (7).
- Names and descriptions are translation keys, not stored strings. The catalogue is browsed by people in every language the product supports (3.7), and categories are ours to translate, unlike the content inside them.
- One level of nesting at most. Deeper trees are how a catalogue becomes unbrowsable again by a different route.
- Deactivating a category stops new publications into it and keeps existing listings reachable.
- `listing_count` is maintained on publication and on withdrawal.

## Open questions

- The actual list of categories. Section 7 gives three examples and no list. It should be short at launch and grown from what people actually publish.
