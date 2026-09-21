# 036. LifeEvent

**Status:** to create
**Spec:** 3.2, 9.1, 9.2, 9.4, 9.5, 9.6, 10.2, 13

## What it is

Something that happened, on a date, possibly with people. It is in the immutable core (3.2), and section 9.1 calls it the unit shared by both halves of the product.

That sharing is the whole product thesis. Noting a lunch with Pam on Tuesday fills the Tuesday journal and fills Pam's history at the same time. One entry, two readings. Section 9.1 argues this is what makes journalling survivable: an ordinary diary asks for daily effort that pays nothing back for years, so it gets abandoned after three weeks. Here every line immediately feeds a contact page, an inactivity reminder, the search and the statistics.

Section 9.2 explains why the concept is frozen while its nature is not. The product uses life events transversally to build timelines, compute inactivity, feed search and produce statistics. If a life event were just another user invented object, none of that could work reliably.

## What it covers, and what participation means

Despite the name, a LifeEvent is not only a milestone. It covers ordinary interactions (a call, a message, a meal, a
visit), pastimes (a concert, a hike, a journey), major events (a birth, a marriage, a separation, a move, an illness, a
new job), and facts recorded about other people that the account holder took no part in.

`created_by_user_id` says who wrote the life event down. It never means that Me took part in it.

Me is an explicit [Contact](019-contact-done.md) and is never added as a participant by implication. "Marc married
Julie" may carry Marc and Julie as participants and no Me at all.

- A life event appears in the history of every Contact that participates in it.
- A mention in the narrative is not participation.
- A life event may carry no participant at all.
- `life_event_count` counts every life event the contact participates in.
- `last_life_event_at` is the latest life event the contact participates in.
- `last_interaction_at` is the latest life event in which the contact **and** Me both participate.
- Inactivity reminders and the "last spoke" display read `last_interaction_at`, never `last_life_event_at`.
- Neither the LifeEventType nor the LifeEventCategory ever decides on its own whether an interaction took place.
- Recording a life event about a contact without Me enriches their history without resetting their inactivity delay.

## Fields

```
id                          int                             primary key
uuid                        string (36)                     unguessable identifier used in URLs and by the API
account_id                  int                             account the life event belongs to
vault_id                    int                             vault it lives in
life_event_type_id            int                             nature of the life event
structure_version_id        int                             exact field structure used when it was created
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

- `belongsTo` [Account](001-account-in-progress.md), [Vault](007-vault-in-progress.md), [LifeEventType](035-life-event-type-in-progress.md), [StructureVersion](013-structure-version-done.md)
- `hasMany` [LifeEventParticipant](037-life-event-participant-not-yet.md)
- `belongsToMany` [Contact](019-contact-done.md) and [Entity](020-entity-done.md) through it
- `hasMany` [FieldValue](022-field-value-done.md), for the fields its type asks for
- `belongsTo` [JournalDay](039-journal-day-not-yet.md), resolved by date and vault

## Rules

- A life event may have no participants at all (9.2). A day can be documented without anybody else being involved, and requiring a participant would make the journal half unusable.
- Two entry paths, one result (9.4). Entering by the day, telling the evening and hooking people into the text, and entering by the person, adding it from their page right after seeing them, produce exactly the same row. `created_from` records which path was used and changes nothing else.
- A life event copies the version its type's structure publishes at the moment it is created, and never follows a newer one automatically. Its existing custom fields therefore keep the structure they were entered against.
- The column is required. A type always has a structure with a published version, even when nobody has given it fields, so there is no case where a life event has no structure to name.
- Dates may be imprecise (9.4). Rebuilding years of past is a supported use, and the product never forces a precision the user does not have. Group memberships use the same precision vocabulary. Relationship dates stay simpler in the first version and may adopt it later.
- Every contact added as a participant receives the life event in their history. The latest dated life event in which the contact participates becomes `last_life_event_at`, regardless of its type (9.3, 10.2). Recording an older life event never replaces a newer value. A life event with no contact participant changes no contact.
- Private by default (9.6). A share link includes a life event only when it is explicitly allowed, and the free narrative only when that is allowed separately. Both default to false, and a field marked sensitive stays excluded even when the life event is shared.
- Files attach through the type's file fields (9.2). Photos land where they belong: the day they were taken, with the people who were there (9.5).
- A life event feeds the contact timeline, inactivity reminders, event relative reminders, statistics and search (9.5). It is not a module standing on its own, and nothing here should be built as though it were.
- Soft deleted. Creation, modification and deletion each emit a webhook, which section 14.2 calls probably the most useful event to listen to.

## Open questions

- Whether a life event spanning several days appears on every day of the journal or only its first. A trip should probably appear across the whole stretch. Not specified.
- Whether participants who are not contacts can be named. The relationship system refuses free text humans (8.2) and the same principle should hold here, which means naming someone in a life event creates a partial contact.
