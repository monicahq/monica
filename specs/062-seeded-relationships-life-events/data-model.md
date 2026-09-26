# Data model: Relationships and life events in the example account

No schema change. This file fixes the content: who is added, and who is linked to whom.

## The seven new people (added to `cast()`)

| Person | Category | Gender | Birthday | Note carries |
| --- | --- | --- | --- | --- |
| Jack Bing | Family | Man | 2004-05-06 (reminder) | Adopted at birth, from Erica in Ohio |
| Erica Bing | Family | Woman | 2004-05-06 (reminder) | Jack's twin, the surprise second baby |
| Frank Buffay Jr. | Acquaintance | Man | none | Phoebe's half brother, married to Alice, the triplets |
| Ursula Buffay | Acquaintance | Woman | 1969-02-16, Phoebe's (reminder) | Phoebe's twin, Riff Raff waitress, not to be trusted |
| Barry Farber | Acquaintance | Man | none | Orthodontist Rachel left at the altar |
| Nora Bing | Family | Woman | none | Erotic novelist, Chandler's mother |
| Helena Bing | Family | Woman | none | Chandler's other parent, stage name Helena Handbasket (research.md R3) |

## Relationships (A holds role A; dates are episode air dates)

| Type | Pairs |
| --- | --- |
| Parent / Child | Jack G. and Judy → Monica, Ross · Ross, Carol, Susan → Ben · Ross, Rachel → Emma · Monica, Chandler → Jack B., Erica · Nora, Helena → Chandler |
| Sibling | Monica and Ross · Phoebe and Ursula · Phoebe and Frank Jr. · Jack B. and Erica · Ben and Emma (half) |
| Grandparent / Grandchild | Jack G. and Judy → Ben, Emma, Jack B., Erica · Nora, Helena → Jack B., Erica |
| Aunt or Uncle / Niece or Nephew | Monica, Chandler → Ben, Emma · Ross → Jack B., Erica |
| Cousin | Ben and Emma, each with Jack B. and Erica |
| Spouse | Monica and Chandler (2001-05-17, current) · Jack G. and Judy (current) · Phoebe and Mike (2004-02-12, current) · Carol and Susan (1996-01-18, current) · Ross and Carol (ended 1993) · Ross and Emily (1998-05-07, ended 1999) · Ross and Rachel (1999-05-20, ended 1999-11) · Nora and Helena (ended 1978-11-23) |
| Fiancé | Rachel and Barry (ended 1994-09-22) |
| Partner | Ross and Rachel (1995-11, ended 1997-02) · Ross and Rachel (2004-05-06, current) · Monica and Richard (ended 1996-05) · Chandler and Janice (ended 1997-01) · Joey and Rachel (2003-05, ended 2003-10) · Phoebe and Mike (2002-09, ended 2004-02, became spouses) |
| Friend | the six, pairwise, less Monica and Ross, less Monica and Chandler (FR-004) · Mike and each of the six · Jack G. and Richard |
| Colleague | Rachel and Gunther (Central Perk, ended 1996) |
| Neighbour (custom, Other, symmetric) | Mr. Heckles with Monica, Rachel, Chandler, Joey (ended 1995-10-05) |

Janice, Emily, Barry, Ursula, Frank Jr. and Gunther reach FR-002 through the rows above.

## Life events

- About 200 distinct events in `ExampleLifeEvents`, each one: type key, date, title, narrative,
  optional location, participants by full name.
- Budget: each person takes part in at least 20 events. The six and the Gellers reach that mostly
  through shared events. Minor people (Heckles, Barry, Emily, Janice, Ursula, Gunther) get the rest
  from their own storylines.
- Coverage: all 8 categories and at least 20 of the 40 types (FR-008). Arguments, therapy, illness,
  hospital stays and spiritual events come from real episodes, each checked against the show.
