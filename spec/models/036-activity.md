# 036. Activity

**Status:** to create
**Spec:** 3.2, 9.1, 9.2, 9.4, 9.5, 9.6, 10.2, 13

## What it is

Something that happened, on a date, possibly with people. It is in the immutable core (3.2), and section 9.1 calls it the unit shared by both halves of the product.

That sharing is the whole product thesis. Noting a lunch with Pam on Tuesday fills the Tuesday journal and fills Pam's history at the same time. One entry, two readings. Section 9.1 argues this is what makes journalling survivable: an ordinary diary asks for daily effort that pays nothing back for years, so it gets abandoned after three weeks. Here every line immediately feeds a contact page, an inactivity reminder, the search and the statistics.

Section 9.2 explains why the concept is frozen while its nature is not. The product uses activities transversally to build timelines, compute inactivity, feed search and produce statistics. If an activity were just another user invented object, none of that could work reliably.

## Fields

```
id                          int                             primary key
uuid                        string (36)                     unguessable identifier used in URLs and by the API
account_id                  int                             account the activity belongs to
vault_id                    int                             vault it lives in
activity_type_id            int                             nature of the activity
happened_on                 date                            the day it happened, or the first day when it spans several
ended_on                    date                            the last day, null when it happened on a single day
date_precision              enum (day, month, year, decade, unknown)  how precisely the date is known, so a rebuilt past is not forced to lie
happened_at_time            time                            time of day, null when it does not matter or is not known
duration_in_minutes         int                             how long it lasted, null when unknown
timezone                    string (64)                     time zone the time was entered in, so it does not shift when the user moves
title                       string (255)                    short label, null when the type and the participants say enough
narrative                   text                            the free account of what happened
location                    string (255)                    where it happened, in words
latitude                    decimal (10,7)                  latitude, null when no place was pinned
longitude                   decimal (10,7)                  longitude, null the same way
is_shareable                boolean                         whether a share link may include it at all, false by default
narrative_is_shareable      boolean                         whether the free account may be included, false by default
created_from                enum (day, contact, api, mcp, import, module)  which entry path produced it, kept for the record
created_by_user_id          int                             user who entered it, null when an import did
participant_count           int                             running count of participants
created_at                  datetime
updated_at                  datetime
deleted_at                  datetime
```

## Relationships

- `belongsTo` [Account](001-account.md), [Vault](007-vault.md), [ActivityType](035-activity-type.md)
- `hasMany` [ActivityParticipant](037-activity-participant.md)
- `belongsToMany` [Contact](019-contact.md) and [Entity](020-entity.md) through it
- `hasMany` [FieldValue](022-field-value.md), for the fields its type asks for
- `belongsTo` [JournalDay](039-journal-day.md), resolved by date and vault

## Rules

- An activity may have no participants at all (9.2). A day can be documented without anybody else being involved, and requiring a participant would make the journal half unusable.
- Two entry paths, one result (9.4). Entering by the day, telling the evening and hooking people into the text, and entering by the person, adding it from their page right after seeing them, produce exactly the same row. `created_from` records which path was used and changes nothing else.
- Dates may be imprecise (9.4). Rebuilding years of past is a supported use, and the product never forces a precision the user does not have. The same precision vocabulary is used here, on relationship periods and on group memberships, and all three render through the same code.
- An activity whose type counts as an interaction updates `last_activity_at` on every participating contact (9.3, 10.2). Nothing else does.
- Private by default (9.6). A share link includes an activity only when it is explicitly allowed, and the free narrative only when that is allowed separately. Both default to false, and a field marked sensitive stays excluded even when the activity is shared.
- Files attach through the type's file fields (9.2). Photos land where they belong: the day they were taken, with the people who were there (9.5).
- An activity feeds the contact timeline, inactivity reminders, event relative reminders, statistics and search (9.5). It is not a module standing on its own, and nothing here should be built as though it were.
- Soft deleted. Creation, modification and deletion each emit a webhook, which section 14.2 calls probably the most useful event to listen to.

## Open questions

- Whether an activity spanning several days appears on every day of the journal or only its first. A trip should probably appear across the whole stretch. Not specified.
- Whether participants who are not contacts can be named. The relationship system refuses free text humans (8.2) and the same principle should hold here, which means naming someone in an activity creates a partial contact.
