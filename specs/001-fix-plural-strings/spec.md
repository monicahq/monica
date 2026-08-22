# Feature Specification: Correct Plural Phrasing in Interface Copy

**Feature Branch**: `001-fix-plural-strings` (git branch `2026-08-21-fix-plural-strings`)

**Created**: 2026-08-21

**Status**: Draft

**Input**: User description: "Fix lang missusage with pluralization"

## Clarifications

### Session 2026-08-21

- Q: Should the new coding style rule stand on its own as written guidance, or must something automatically reject the bad pattern before it can be merged? → A: Option B, a written rule in the coding style rules plus a check in the routine that collects strings to translate, which fails when a phrase shows a number without plural wordings or when a wording is chosen by comparing a count to one.
- Q: Should the rule and its check cover plural wording only, or every number a reader sees, including digit grouping? → A: Plural wording only. How a number is written out is a per user setting, a separate mechanism from which wording a phrase takes, and it is not this rule's concern.
- Q: No shipped locale needs more than two wordings, so how should the spec prove a language needing three or more can be translated? → A: Option A, drop the claim. Prove only what the nine shipped locales exercise: two wordings, French showing the singular at zero, Turkish showing one. Three or more stays unproven until such a language is added.
- Q: Should every phrase showing a number become a counted phrase, or may a phrase declare that its wording does not depend on the number and be skipped by the check? → A: Option B, a phrase may declare itself number independent. The declaration is stated at the phrase itself, never in a list kept elsewhere, and the check honours it.

### Correction, 2026-08-21, found during implementation

The numbers and the examples in the first version of this spec were produced by looking for a guard on
the same line as each phrase, or within two lines of it. Most guards sit further up their method, so
the analysis misread them. Re-examined at method scope and verified by running the code:

- **The examples were wrong.** "1 people" on the vault dashboard, "Opened 1 times", "used 1 times",
  "1 records use this type", "Shared by 1 links" and "Active · 1 API keys" do **not** happen. Each of
  those methods guards a count of one, and often zero too, and renders "1 person", "Opened once",
  "used once", "1 record uses this type", "Shared by one link" and "Active · 1 API key". The codebase
  is careful about English.
- **The real defect is French at zero.** 33 of the call sites choose the wording by comparing the count
  to one without also guarding zero. French groups zero with the singular, so those render the plural:
  `:count modèles` at a count of zero gives "0 modèles" where French requires "0 modèle". Verified by
  running the French locale.
- **The split is 17 and 52, not 37 and 32.** 17 call sites carry no plural wordings at all, of which
  about six can render wrongly in English (three always, three only when a configured duration is set
  to one). 52 choose the wording in PHP, 33 of them without a zero guard.
- **Turkish is wasteful rather than wrong today.** Two keys per phrase means a Turkish translator
  translates both, and the English comparison then picks one. The output is right and the work is
  doubled.

### Amendment, 2026-08-21, after the second convergence pass

FR-020, FR-021 and SC-012 were added after implementation, not before it. They describe a mechanism the
code already used and the spec did not mention: a wording pinned to a single number with `{1}`, which
ten phrases rely on to keep copy such as "Opened once" that the tests for those screens ask for. The
design and the alternatives rejected are recorded as decision D9 in
[research.md](./research.md).

It earned a requirement of its own because it carries a trap that cost three defects. A wording reserved
for one number is reached by no other number, so at zero the phrase falls back to the language's own
rules and lands on that reserved wording, which French then reads for zero. Two rounds of review missed
the third instance; the check now required by FR-021 found it.

Added by hand rather than through `/speckit-clarify` or `/speckit-specify`. Clarify asks questions to
settle an open decision, and this one was already settled and documented; specify would regenerate the
document and take the correction note below with it.

The feature is unchanged and still worth doing: the mechanism is wrong even where the output happens to
be right, French is wrong at zero in 33 places, and no language needing three or more wordings can be
added at all. What changes is the justification, which is now a localization defect with a narrow
English tail rather than the widespread English breakage first claimed.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - A count and its noun agree, at every number (Priority: P1)

Numbers sit next to nouns all over the product. How many people are in a vault, how many times a
share link was opened, how many templates an account holds, how many minutes a magic link stays valid,
how many files a sweep removed. 17 of those places carry one wording whatever the number.

Six of them can be read wrongly. A file of one line is described as "1 lines", in the two places a
shared structure reports its size. A link revoked after a single visit says "It was opened 1 times".
And three phrases read wrongly only when a deployment configures a duration down to one: "Remember me
for 1 days" on the login screen, and "the next 1 minutes" in the magic link screen and its email.

The rest of the 17 name a fixed limit far above one, or are reached only past a threshold, so they are
not wrong today. They are converted anyway, because nothing but a reading of the surrounding code says
so, and that is the reasoning this feature exists to remove.

**Why this priority**: a wrong reading in the product's own default language is the one defect no
reader can excuse, and three of the six need no unusual configuration to reach. It is also the slice
that delivers value on its own: correcting these needs nothing from the other two stories. It is a
smaller slice than the second story, and it is still first, because it is the only part a reader of
English can see.

**Independent Test**: set each affected count to one (a vault with a single contact, a link opened
once, an account with one template) and read the screen, the email or the changelog entry. Every
phrase agrees with the number. Repeating the walk with the count set to two and to a large value
also reads correctly.

**Acceptance Scenarios**:

1. **Given** a vault holding exactly one contact, **When** the reader opens the vault dashboard,
   **Then** the headcount reads as a singular phrase and never as "1 people".
2. **Given** a share link opened exactly once, **When** the reader opens the list of share links for
   that contact, **Then** the visit count reads as a singular phrase and never as "Opened 1 times".
3. **Given** the remember duration is configured to one day, **When** somebody opens the login
   screen, **Then** the checkbox label reads as a singular phrase and never as "Remember me for 1
   days".
4. **Given** a count of two or more anywhere a count is shown, **When** the reader looks at it,
   **Then** the phrase reads as a plural, exactly as it does today.
5. **Given** a count of zero on a screen that already has its own wording for nothing at all (a
   vault with no contacts reads "Nobody in it yet"), **When** the reader looks at it, **Then** that
   wording is unchanged.
6. **Given** a count of zero on a screen that has no dedicated wording for nothing at all, **When**
   the reader looks at it, **Then** the phrase reads correctly for zero in the reader's language.

---

### User Story 2 - A translator can express their own language's plural rules (Priority: P2)

Where the product does vary its wording by number, it varies it in the shape of English: one wording
for exactly one, a second for everything else. That shape is baked into the code, not into the copy,
so it is not something a translator can change.

The product ships in nine languages today, and two of them already disagree with that shape. French
needs zero to read as a singular ("0 personne", not "0 personnes"). Turkish needs one wording and one
only, so handing it two leaves the second as dead text that no number will ever reach. Neither can be
expressed while the choice is made in code by comparing the count to one.

Languages the product does not ship yet fare worse. Polish, Czech and Russian need three wordings and
Arabic needs six, so a translator would be handed two boxes for a phrase that needs six. Making that
work is not proven by this change, and is not claimed to be: what this change does is stop the number
of wordings being decided anywhere other than in the language itself, which is the precondition for
ever adding such a language.

**Why this priority**: it is invisible in English, so it costs nothing today, but it is the reason
the first story keeps coming back. Fixing the visible wording without fixing the shape leaves the
next language, and the next counted phrase, in the same place.

**Independent Test**: for any counted phrase, a translator supplies the wordings their language
requires, in the translation files alone, and the interface picks the right one for the number.
Verified against the shipped locales where the requirement differs from English: French at zero must
read as a singular, and Turkish must read the same at every number. No code change in either case.

**Acceptance Scenarios**:

1. **Given** a counted phrase, **When** a translator opens the translation file for their locale,
   **Then** they find the whole phrase in one place, with room for every plural category their
   language requires.
2. **Given** the Turkish locale, which needs exactly one wording, **When** a count of one and a count
   of five are shown, **Then** both read the same, and the file holds one wording rather than two.
3. **Given** the number of wordings a phrase needs, **When** it is determined, **Then** it comes from
   the reader's language and from nowhere else, so a language needing three or more is a translation
   to supply and not a change to make.
4. **Given** the French locale, **When** a count of zero is shown, **Then** the phrase reads as
   French requires for zero, which is the singular.
5. **Given** a locale where a counted phrase has not been translated yet, **When** the count is
   shown, **Then** the reader sees the English wording for that number, correctly formed, and never
   an empty or malformed phrase.

---

### User Story 3 - The misuse cannot come back (Priority: P3)

Two documents already told contributors to get this right. The constitution requires plurals to be
translated, and the coding style rules have a section on translations. Both were in place while the
69 places in story one were written, so writing the rule down again on its own would repeat a step
that has already failed here.

So the rule is written down, and something checks it. The routine that collects everything needing
translation, and the check that fails when the translation files fall behind, do not know what a
counted phrase is: a contributor can add one, fill in only the plural wording, and nothing
complains. That routine already reads every file looking for the calls that mark a string as
translatable, so it is where both bad patterns can be caught. The first is a phrase that shows a
number and carries no plural wordings. The second is a wording chosen by comparing a count to one,
which imposes English grammar on every language.

**Why this priority**: it protects the first two stories rather than delivering anything a reader can
see. It is worth doing in the same change, because the audit story one requires is exactly the work
this check automates, and because the rule and the check have to agree from the day they land.

**Independent Test**: write a counted phrase with an incomplete set of wordings, and separately write
a wording chosen by comparing a count to one. Run the check. It fails on both, and says where.

**Acceptance Scenarios**:

1. **Given** a new counted phrase added to the interface, **When** the routine that collects strings
   to translate runs, **Then** the phrase is reported as needing every wording its locales require.
2. **Given** a counted phrase missing a wording in any configured locale, **When** the check that
   reports whether the translation files are up to date runs, **Then** it fails and names the phrase
   and the locale.
3. **Given** a phrase that shows a number and carries no plural wordings, **When** the check runs,
   **Then** it fails and names the phrase and where it is written.
4. **Given** a wording chosen by comparing a count to one, **When** the check runs, **Then** it fails
   and names where it is written.
5. **Given** every counted phrase complete in every configured locale and no wording chosen by
   comparing a count to one, **When** the check runs, **Then** it passes.
6. **Given** a contributor or a coding agent about to write a phrase that shows a number, **When**
   they read the coding style rules, **Then** they find the rule stated there, in the same terms the
   check enforces.

---

### Edge Cases

- A count of zero where the screen already says something else entirely for nothing at all. Several
  screens have such wording ("Nobody in it yet", "No keys yet", "No values yet"). It stays, and the
  counted phrase covers one and upwards.
- A count of zero where no such wording exists. The phrase has to read correctly at zero in every
  language, and zero does not belong to the same category in every language. French groups zero with
  one, so a French reader must see the singular at zero while an English reader sees the plural.
- Turkish, which needs exactly one wording. A phrase given two wordings in Turkish would always show
  the first and the second would be dead text, so the number of wordings expected is a property of the
  locale and a check that demands two everywhere would be wrong for Turkish and right for the rest.
- A count of one that names a configured limit rather than something the reader created (an import
  ceiling, the remember duration, the minutes a magic link lasts). These are usually above one, but
  a deployment can set them to one, so they need the same treatment.
- A count buried in a long sentence rather than standing on its own, such as the paragraph warning
  what happens when a share link is revoked. The whole sentence varies, not just the noun.
- Two counts in one sentence ("3 of 12 shown", "3 of 12 templates"). Which number governs the
  wording has to be decided per phrase, and in some languages both parts move.
- A count already rendered as a finished phrase and then dropped into a second sentence ("The 3
  records on this template, and everything written in their fields"). The inner phrase is
  pluralized, and the outer sentence still has to read.
- A phrase where the number happens not to change the wording in English ("3 of 12 shown.", "Show 3
  more", "3 in March"). Some of these change in other languages and some do not, so each declares
  itself number independent or is treated as depending on its number. Reading it in English is not
  the test, and silence is not a declaration.
- A phrase that looks number independent and is not. "1 lines" and "1 published something this week"
  both read wrongly at one, so the fact that a number sits in a phrase without an obvious plural noun
  beside it proves nothing.
- A very large count. Digit grouping is a separate concern and is listed under Out of Scope.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: Every phrase that shows a number MUST be classified as one whose wording depends on
  that number or one whose wording does not. As counted today, 46 phrases in the English catalogue
  put a number inside themselves, referenced from 69 places, 17 of which carry one wording and 52 of
  which pick one in PHP, alongside 18 standalone singular phrases
  that exist only to pair with a plural one. Not every one of the 46 changes with its number, which
  is why they are classified rather than assumed. The classification MUST cover all of them, including those in emails and
  in changelog entries, not only those on screens.
- **FR-002**: For every phrase whose wording depends on the number, the product MUST show the
  wording that the reader's language requires for that number.
- **FR-003**: A counted phrase MUST be held as a single entry carrying all of its wordings together,
  so that a translator sees the whole phrase at once and supplies as many wordings as their language
  needs.
- **FR-004**: A wording MUST NOT be selected by comparing the number to one, a rule that holds in
  English and not everywhere. There are 52 such places today, and each imposes English grammar on all
  nine languages. 33 of them do not guard zero either, which is why French reads the plural there.
- **FR-005**: A phrase MUST NOT carry one fixed wording for every number when its wording depends on
  the number. There are 17 places today showing a single wording whatever the count.
- **FR-006**: A count of zero MUST read correctly in every configured locale, and the language MUST
  decide which wording zero takes rather than the code deciding it once for all languages.
- **FR-007**: Existing wording for the empty state MUST be preserved where a screen has it. Replacing
  "Nobody in it yet" with "0 people" is a regression, not a fix.
- **FR-008**: Where a count sits inside a longer sentence, the whole sentence MUST be available for
  the translator to vary, not only the noun beside the number.
- **FR-009**: Every counted phrase MUST be translated into every configured locale, of which there
  are nine today, with exactly the number of wordings that locale's language requires and no more.
  That number is a property of the locale: two for seven of them, two for French with zero grouped
  under the singular, and one for Turkish. A locale MUST NOT be handed a wording its language will
  never show.
- **FR-010**: Translation files MUST NOT be edited by hand. Every entry MUST arrive through the
  routine that collects strings to translate.
- **FR-011**: The routine that collects strings to translate MUST recognise counted phrases and
  report a phrase as incomplete when it is missing any wording a configured locale requires.
- **FR-012**: The check that reports whether the translation files are up to date MUST fail when a
  counted phrase is incomplete, and MUST name the phrase and the locale.
- **FR-015**: That same check MUST also fail on the two ways this misuse is written, and MUST say
  where each one is: a phrase that shows a number and carries no plural wordings, and a wording
  chosen by comparing a count to one. Detecting them belongs to the routine that already reads every
  file for translatable strings, rather than to a second scanner.
- **FR-016**: The check MUST NOT assume a fixed number of wordings. It compares what a phrase carries
  against what that locale's language requires, which is one for Turkish and two for the rest today.
- **FR-018**: A phrase whose wording genuinely does not depend on its number MUST be able to declare
  that, and the check MUST honour the declaration rather than failing. The declaration MUST be stated
  at the phrase itself and MUST NOT live in a list kept anywhere else, because a list drifts from the
  phrases it exempts and nothing forces it up to date.
- **FR-019**: Declaring a phrase number independent MUST be a deliberate and visible act, not the
  default a phrase falls into by saying nothing. A phrase that says nothing is treated as depending on
  its number. Judging by how a phrase reads in English is what produced the places this change
  corrects, so the classification required by FR-001 MUST record the decision for each phrase, and a
  reviewer MUST be able to read them together rather than discovering them one at a time.
- **FR-017**: The rule MUST be written into the coding style rules that contributors and coding
  agents read, in the same terms the check enforces, so that the guidance and the gate cannot drift
  apart. It states that a phrase showing a number carries one wording per plural category, and that
  the choice between wordings is never made by comparing a count to one.
- **FR-020**: A wording MAY be reserved for a single number, and then it need not say where the number
  goes, because the number is already known. This is for copy that deliberately spells the number as a
  word rather than a digit, which reads better in several places. A wording that serves more than one
  number MUST still say where its number goes, since one that does not reads the same at every number.
- **FR-021**: A phrase holding a wording reserved for one number MUST keep every other number away from
  it, and zero in particular. Such a wording names one thing and cannot stand in for none, and the
  languages that group zero with the singular will read it there. The guard MUST belong to the phrase
  rather than to the screen that shows it, and it MUST be enforced automatically rather than left to
  whoever reviews the change.
- **FR-013**: The wordings a reader sees at zero, one, two and a large number MUST be covered by
  tests, for every counted phrase in the source language, and for the two shipped locales whose rules
  differ from English: French, which reads the singular at zero, and Turkish, which reads one wording
  at every number.
- **FR-014**: No phrase that a reader can see today may disappear without its replacement reading
  correctly at every number. A phrase removed from the catalogue takes every translation of it away,
  so a phrase whose shape changes MUST be re-translated in all nine locales in the same change.

### Key Entities *(include if feature involves data)*

- **Counted phrase**: a piece of interface copy whose wording depends on a number shown inside it.
  It holds one wording per plural category of the language it is written in, and the number it is
  given selects between them.
- **Plural category**: the set of numbers a language treats alike for grammar. The set belongs to the
  language and not to the phrase, and the number of wordings a phrase needs therefore differs by
  locale. Of the nine locales shipped today, seven need two wordings, French needs two but groups zero
  with one, and Turkish needs exactly one. Languages not yet shipped need more: Polish and Czech three,
  Russian three, Arabic six.
- **Locale**: one of the nine languages the interface ships in. Each carries its own file of
  translated phrases, and its own plural categories.
- **Empty state wording**: copy a screen shows when there is nothing to count at all, distinct from
  a counted phrase given zero. They are not counted phrases, and this change leaves them alone.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Reading every counted phrase at zero, one, two and one thousand, in all nine shipped
  languages, produces no phrase that disagrees with its number. Today the same walk produces six wrong
  readings in English, and 33 wrong readings in French at a count of zero.
- **SC-002**: No counted phrase has its singular or plural settled before the reader's language is
  consulted, down from 52 places where it is settled in advance today.
- **SC-003**: Every phrase whose wording depends on its number varies with it. Of the 17 places
  showing one fixed wording today, six read wrongly, three are confirmed number independent, two turn
  out not to show a number at all, and the rest are converted for uniformity.
- **SC-004**: A translator can supply the wordings their language requires, for every counted phrase,
  without a single code change. Verified where the nine shipped locales disagree with English: French
  reads the singular at zero, and Turkish reads one wording at every number. Support for a language
  needing three or more wordings is deliberately not claimed here, and is left to whichever change
  first adds one.
- **SC-005**: A counted phrase missing any wording its locales require is reported before it ships,
  in 100% of cases, naming the phrase and the locale.
- **SC-008**: Both ways of writing this misuse are rejected automatically before shipping, in 100% of
  cases, each report naming where it is written. Reintroducing any one of the 69 places corrected by
  this change causes a failure, unless that place has declared itself number independent.
- **SC-010**: Every one of the 46 phrases that shows a number is recorded as depending on its number
  or as declaring itself independent of it, with none left undecided, and the list of those declaring
  independence is short enough for a reviewer to read in one sitting.
- **SC-009**: A contributor or coding agent reading the coding style rules finds the rule stated
  there, in the same terms the automatic check applies, so following the written rule is sufficient to
  pass the check.
- **SC-011**: French reads the singular at a count of zero in all 33 places where it reads the plural
  today.
- **SC-012**: No phrase carrying a wording pinned to a single number can be reached at zero, and
  writing one that can is rejected before it ships rather than found by reading the code.
- **SC-006**: Every one of the nine shipped locales reports zero missing translations after the
  change, the same state as before it.
- **SC-007**: A translator handles one entry per counted phrase rather than a pair, so the 18
  standalone singular entries no longer need separate attention.

## Assumptions

- "Lang missusage" refers to the way the product asks for translated copy when a number is involved,
  covering both the phrases that read wrongly today and the pattern that produced them. The pattern
  is in scope, since fixing only the visible readings leaves the next phrase and the next language in
  the same place.
- Plural categories are a property of each language and are taken from established rules for it,
  rather than being decided per phrase by whoever writes the phrase.
- Where a locale has no wording for a counted phrase yet, falling back to the English wording for
  that number is acceptable and is what happens today for any untranslated phrase.
- Empty state copy that exists today is deliberate and stays. This change does not add empty state
  copy to screens that lack it, and does not remove it from screens that have it.
- Counts that name configured limits are treated the same as counts of things a user created, because
  a deployment can set a limit to one.
- The nine locales configured today are the full set to translate. Adding a tenth is a separate
  change, and this one is expected to make that easier rather than to do it.
- The rule belongs with the operational guidance rather than in the constitution. Principle V already
  requires plurals to be translated, so the gap is not a missing principle but a missing concrete
  rule, and governance places naming details and style minutiae in the development time guidance
  rather than in the constitution. No amendment is therefore proposed.
- The existing per change checklist already requires running the string collecting routine and
  translating what it reports. Once that routine fails on this misuse, the checklist catches it with
  no new line of its own.
- No screen layout, no navigation and no behaviour beyond the wording of counted phrases changes.
  This is a correctness change to copy, not a redesign.

## Out of Scope

- Digit grouping and decimal separators for the number itself. How a number is written out is a per
  user setting, chosen in preferences, and it is a separate mechanism from which wording a phrase
  takes. It is therefore neither part of this change nor part of the rule this change writes. Worth
  recording for whoever picks it up: the preference reaches numeric field values a user typed, and
  does not currently reach counts shown in interface copy, so a reader who has chosen space and comma
  still sees "1234" in these phrases.
- Date and time formats, currencies, and the order of given name and family name. Named alongside
  plurals in the project's own localization principle, and each is its own change.
- Right to left layout.
- Adding a tenth language.
- Copy a user wrote themselves, which stays in the language its author used.
