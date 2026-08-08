# 039. JournalDay

**Status:** to create
**Spec:** 9.4, 9.6, 3.5

## What it is

One day, as a thing that can be written about. It holds what belongs to the day itself rather than to any activity in it: the general account of the day, and the mood.

Section 9.4 makes the case for it in one line: a day may hold nothing but a mood. If everything lived on activities, a day with a mood and no events would have nowhere to be stored, and the journal would only work for days that happened to contain something worth logging.

It also states the tone the product takes, which is a design constraint and not a nicety. An empty day stays an empty day. The product does not make anybody feel guilty and does not ask for anything.

## Fields

```
id                          int                             primary key
account_id                  int                             account the day belongs to
vault_id                    int                             vault the day is written in
date                        date                            the day itself
mood_id                     int                             general mood, null when none was chosen
narrative                   text                            the account of the day, belonging to no activity in particular
narrative_is_shareable      boolean                         whether a share link may include that account, false by default
rating                      int                             how the day went on a fixed scale, null when not rated
activity_count              int                             running count of activities on that day, so the calendar renders without aggregating
is_notable                  boolean                         whether the user marked the day as one to remember
created_at                  datetime
updated_at                  datetime
```

## Relationships

- `belongsTo` [Account](001-account.md), [Vault](007-vault.md), [Mood](038-mood.md)
- `hasMany` [Activity](036-activity.md), matched on vault and date

## Rules

- One row per vault per date. The row is created lazily, on the first thing written about that day, and never in advance. Pre-creating days would fill the table with emptiness and turn "no entry" into "an empty entry", which are different things.
- A day with only a mood is complete and valid.
- Activities are not owned by the day. They belong to the vault and carry their own date, and the day gathers them by date. This matters because an activity can be entered from a contact page without the day existing yet (9.4), and it must land in the right day on its own.
- Private by default (9.6). The account of the day is included in a share link only when explicitly allowed.
- Past days can be filled in freely, and years can be rebuilt after the fact (9.4).
- The calendar view of the journal must not rely on colour alone to show which days hold something (3.5).

## Open questions

- Whether the journal is per vault or per account. This file makes it per vault, consistently with everything else in section 4.2, which means a lunch logged in the work vault does not appear in the family journal. The alternative is one journal across all vaults, which reads more like a real diary and punches a hole in the compartment. The specification does not settle it and it should be settled before the journal screens are built, because it changes what the main screen queries.
- Whether `rating` and mood are redundant. A mood with a `scale_value` covers most of what a rating would say.
