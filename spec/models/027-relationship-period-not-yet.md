# 027. RelationshipPeriod

**Status:** to create
**Spec:** 8.2, 9.4

## What it is

One stretch of time during which a relationship held. A relationship can have several, because a couple can separate and get back together, and the specification says so explicitly.

It is a separate model rather than a pair of columns for two reasons. Several periods per relationship cannot fit in two columns. And dates are allowed to be imprecise: "since around 2018", "since childhood". Section 8.2 refuses to force a precision the user does not have, which means a period stores what is known and how precisely, not a timestamp pretending to be exact.

## Fields

```
id                          int                             primary key
account_id                  int                             account the period belongs to
relationship_id             int                             relationship the period belongs to
started_on                  date                            when it started, null when only a description is known
started_precision           enum (day, month, year, decade, unknown)  how precisely the start is known
started_description         string (255)                    the start as the user phrased it, such as during childhood
ended_on                    date                            when it ended, null while it is still running
ended_precision             enum (day, month, year, decade, unknown)  how precisely the end is known
ended_description           string (255)                    the end as the user phrased it
is_current                  boolean                         whether this is the period running now
end_reason                  string (255)                    why it ended, such as separation, move or death
notes                       text                            anything else worth keeping about this stretch
position                    int                             order the periods read in on the timeline
created_at                  datetime
updated_at                  datetime
```

## Relationships

- `belongsTo` [Account](001-account-in-progress.md)
- `belongsTo` [Relationship](026-relationship-not-yet.md)

## Rules

- A period whose end is before its start is refused (8.3).
- Overlapping periods on the same relationship are warned about, not blocked (8.3). They are unusual, not impossible.
- At most one period per relationship is current.
- The parent relationship's `is_active` is derived from whether any period is current. It is stored there because contact pages filter on it constantly.
- Precision is stored, never guessed. "Since around 2018" is stored as a year precision, and every screen renders it as around 2018 rather than as 1 January 2018. The same approach applies to imprecise dates in the journal (9.4), and both should render through the same code.
- A period with no date at all but a description is valid. That is the "since childhood" case, and refusing it would push the user into inventing a date.
- Starting or ending a period is an event on the contact timeline (8.4).

## Open questions

- Whether an end reason should come from a value list rather than free text, so it can be filtered on. A list is more useful and needs seeding in every locale.
