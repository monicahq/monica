# Feature Specification: French Translation Rules

**Feature Branch**: `002-french-translation-rules` (git branch `2026-08-26-french-translation-rules`)

**Created**: 2026-08-26

**Status**: Draft

**Input**: User description: "Add rules for french translations"

## Context

`lang/fr_FR.json` holds 1708 values, one per string the product ships. It reads well. What it does
not do is read consistently, because nothing anywhere states how French copy is supposed to be
punctuated, so each string was written to whatever its author had in mind that day. Counted against
the shipped file on 2026-08-26:

| What French asks for | The file today |
| --- | --- |
| The typographic apostrophe `’` throughout | 259 values use the straight apostrophe `'`, 226 use the typographic apostrophe `’`. No single value mixes them, so the split is between strings rather than inside them. |
| U+00A0 before `:` | 40 values put a space before a colon. All 40 use an ordinary space. None uses a non-breaking one. |
| U+202F before `?`, `!` and `;` | 36 values put a space before one of them. 34 use an ordinary space, 2 already use U+202F. |
| Guillemets `«` and `»`, with U+202F inside them | 10 values use guillemets, all 10 with ordinary spaces inside. 1 value uses straight double quotes instead. |
| One form of address throughout | 272 values address the reader, all with *vous*. Consistent today, and written down nowhere, so the next contributor has to read the file to find out. |

None of this is a mistranslation. Every string says the right thing. The defect is that the reader
sees two apostrophes in one product, and that in 74 places a punctuation mark can be pushed onto the
next line on its own, which is exactly what a non-breaking space exists to prevent. The deeper defect
is that the rules live only in the file: an agent or a contributor adding a French string has no
written statement to follow, and can only copy whichever neighbouring string it happens to look at.

This is the same shape of problem as [001-fix-plural-strings](../001-fix-plural-strings/spec.md),
which found that plural wordings were correct by accident in English and wrong in French at zero. That
feature wrote the rule down and made the routine that collects strings enforce it. This one covers
what that routine still does not judge: not whether a value exists or has the right number of
wordings, which it already checks, but how the prose inside it is written.

## Clarifications

### Session 2026-08-26

- Q: Which apostrophe character does French copy use? → A: The typographic apostrophe `’`
  (U+2019), always. The straight apostrophe `'` (U+0027) is never correct in French copy.
- Q: Should this cover French alone, or should it establish a place where any locale states its own
  conventions, French being the first to fill it in? → A: A per-locale structure, with French the
  first locale filled in. The other eight locales get a section that is visibly empty rather than
  absent, and anything built to catch a breach is built so a second locale is added by writing rules
  rather than by rebuilding the mechanism.
- Q: Should the rules be written guidance that a reviewer applies, or must something reject a breach
  before it can be merged? → A: Written rules plus a check that reports each breach and fails, added
  to the routine that keeps translations in step. It reports and refuses; it never rewrites the value
  itself.
- Q: Which non-breaking space sits before each mark? → A: The narrow non-breaking space U+202F
  before `;`, `!` and `?`, and inside the guillemets `«` and `»`. The full non-breaking space U+00A0
  before `:`. This is the Imprimerie nationale convention, and it is what the two French values
  already using a non-breaking space do today.
- Q: The shipped file already breaches these rules in roughly 340 values. Are those corrected as part
  of this feature, or does the rule apply only to strings written from now on? → A: Corrected in this
  feature, all of them, so that the check ships passing. No list of tolerated values is kept anywhere,
  on the same reasoning that kept the plural declaration at the phrase rather than in a list.

### Correction, 2026-08-26, found while planning

Two sentences in the first version of this spec said that the routine keeping translations in step
does not inspect translated values, and that this feature would give it that ability for the first
time. Reading `synchronize()` in the command shows otherwise: it already reads each translated value
to count its wordings and to check that each one carries `:count`, which is what 001-fix-plural-strings
added. Both sentences are corrected above.

What the routine has never done is judge the prose of a value: which characters it is written with,
and how it addresses the reader. That is the gap this feature fills, and it is a smaller and better
shaped one than the spec first claimed, because the place to hang the work already exists and has a
tested pattern to follow.

### Correction, 2026-08-26, found during implementation

FR-014 and FR-018 contradict each other for exactly one value, and the contradiction was only visible
once the check existed.

One string embeds a JSON sample in a sentence: `A file exported from Monica starts with "monica":
{ "format": … }.` The French carried the same sample. It breaks two rules, the straight double quotes
and a colon with no non-breaking space before it, and both breaches are JSON syntax rather than French
prose. Neither can be corrected: U+00A0 is not valid JSON whitespace, and guillemets are not JSON
string delimiters. The correction script did apply both and produced a sample that was no longer JSON,
which is how this was found.

So FR-014, which forbids changing what a string says, and FR-018, which fails a breach, could not both
hold here. FR-021 forbids the usual escape of recording the value as tolerated.

Resolved by decision: the French was reworded to drop the literal sample, now reading "commence par un
objet « monica » qui annonce son « format »". Every rule passes. The French says slightly less than the
English, which is the FR-014 breach, taken knowingly and confined to this one value.

The edge case list below already anticipated this ("Some strings are technical labels rather than
sentences ... A rule that assumes prose must not mangle them"). What it did not anticipate is that the
product has no way to mark a span as code, so the rules cannot tell one from prose. Two alternatives
were weighed and rejected: delimiting the sample in the English key, which changes an English string,
changes the key, and shows the delimiter to readers in all nine languages; and skipping any value
containing braces, which is a shape heuristic that would let a genuinely mis-quoted sentence through.

Nothing else in the file needed a wording change. Of the 315 French values this feature touched, 314
differ from their originals only in spaces and apostrophes, verified character by character.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - French punctuation stays attached to the word it belongs to (Priority: P1)

A French reader on a narrow screen reads a confirmation dialog, a form label, a piece of help text.
French puts a space before a question mark, an exclamation mark, a semicolon and a colon. That space
is typographic, not a word break, and the punctuation must never end up alone at the start of the
next line.

In 72 of the 74 places where the product does this, the space used is an ordinary one. A phone in
portrait, a browser zoomed for readability, a long contact name substituted into `Supprimer :name ?`,
and the question mark wraps by itself. It looks like a mistake, because it is one.

**Why this priority**: it is the only item here a reader can actually see going wrong. The others are
inconsistencies a reader feels rather than notices.

**Independent Test**: render the French locale at a narrow width across the screens carrying these
strings and confirm no punctuation mark begins a line on its own. Testable on its own, and delivers
correct French typography whether or not anything else in this feature ships.

**Acceptance Scenarios**:

1. **Given** a French string with a space before `?`, **When** the line is broken at that point,
   **Then** the `?` stays on the same line as the word before it.
2. **Given** `Supprimer « :name » ?` with a long name substituted, **When** the phrase wraps,
   **Then** neither guillemet nor the question mark is separated from the text it encloses.
3. **Given** a French string containing a `:name` or `:count` placeholder, **When** the spacing rule
   is applied, **Then** the placeholder's own colon is left exactly as it is, because it is a marker
   and not punctuation.

---

### User Story 2 - One French, not several (Priority: P2)

The product ships one voice per language. In French today it ships two apostrophes, 259 strings using
one and 226 using the other, and two ways of quoting something. A reader moving between two screens
sees the shift without being able to name it.

The form of address is the counter-example that proves the point: all 272 strings that address the
reader use *vous*, consistently, and purely because everybody who has written French here happened to
agree. Nothing records that agreement, so nothing protects it.

**Why this priority**: it degrades quality everywhere but breaks nothing, and it is only worth fixing
once the rules exist to fix it against.

**Independent Test**: read every French value and confirm one apostrophe character, one quotation
convention and one form of address across the whole file.

**Acceptance Scenarios**:

1. **Given** the shipped French file, **When** its values are examined, **Then** exactly one
   apostrophe character appears throughout.
2. **Given** a French string quoting a name or a value, **When** it is examined, **Then** it uses
   guillemets rather than straight or curly double quotes.
3. **Given** a French string addressing the reader, **When** it is examined, **Then** it uses *vous*.

---

### User Story 3 - A contributor knows the rules before writing, not after (Priority: P3)

Somebody adds a screen, runs the routine that collects strings, and is handed a list of English
phrases to translate into nine languages. For French they must now decide, with nothing to go on,
which apostrophe to type, whether a space goes before the question mark and what kind, how to quote a
value, and whether to say *tu* or *vous*. Today the only answer available is to open the file and
imitate a neighbour, and a neighbour picked at random is right about the apostrophe barely half the
time.

The project already keeps this kind of decision written down, in the coding style rules and in the
translation skill. French has nothing there beyond the instruction to match the existing tone.

**Why this priority**: it is what stops the file drifting again after Stories 1 and 2 have cleaned it
up. Valuable, but worthless on its own if the file it describes is inconsistent.

**Independent Test**: hand somebody unfamiliar with the codebase a new English string and the written
rules, and confirm the French they produce needs no punctuation corrections in review.

**Acceptance Scenarios**:

1. **Given** the written rules, **When** a contributor translates a new string into French, **Then**
   every question the rules cover is answerable without opening `lang/fr_FR.json`.
2. **Given** a French value breaching a rule, **When** the contributor prepares the change, **Then**
   the breach is named against a written rule before a reviewer ever sees it.

---

### Edge Cases

- A placeholder starts with a colon. `Supprimer :name ?` contains a colon that is part of `:name` and
  a question mark that is French punctuation. The spacing rule applies to the second and must never
  touch the first.
- A counted phrase carries several wordings separated by `|`. Each wording is French copy in its own
  right and the typography rules apply inside each one, while the separator itself is structure and
  is left alone.
- Values carry Markdown and HTML, which must stay identical across locales. Typography applies to the
  prose, never to the markup.
- A non-breaking space is invisible. An editor configured to normalise whitespace, or a careless
  find-and-replace, can silently turn it back into an ordinary space, and nothing in the rendered
  page says so until a line happens to wrap there.
- Proper nouns and brand names are not French copy. `Monica` is spelled `Monica` and takes no rules.
- Some strings are technical labels rather than sentences (a header name, a file extension, a format
  example). A rule that assumes prose must not mangle them.
- A string may legitimately contain no punctuation the rules mention, in which case there is nothing
  to check and it passes trivially.

## Requirements *(mandatory)*

### Functional Requirements

**The written rules**

- **FR-001**: The project MUST carry a per-locale place where a configured locale states its own
  writing conventions, kept where the project's other standing rules for contributors and agents
  already live.
- **FR-002**: Every configured locale MUST have a section there. A locale whose conventions nobody
  has written yet MUST have a section that says so, rather than no section at all, so that a
  contributor can tell "not yet written" from "no rules apply".
- **FR-003**: The French section MUST be filled in completely. The other eight locales are left for
  somebody who reads them, and this feature writes none of their rules.
- **FR-004**: The rules MUST state that French copy always uses the typographic apostrophe `’`
  (U+2019), and that the straight apostrophe `'` (U+0027) is never correct in French copy.
- **FR-005**: The rules MUST state that a space before `:`, `;`, `?` and `!` is required, that it MUST
  be a space that cannot break across a line, and which character that is for each mark: the narrow
  non-breaking space U+202F before `;`, `!` and `?`, and the full non-breaking space U+00A0 before
  `:`. The rules MUST name both by code point, because neither is visible in a text editor.
- **FR-006**: The rules MUST state that quoted text uses the guillemets `«` and `»`, with a narrow
  non-breaking space U+202F inside each of them, and that straight and curly double quotes are never
  correct in French copy.
- **FR-007**: The rules MUST state that the reader is addressed with *vous*.
- **FR-008**: The rules MUST state what they do not cover, so that a contributor can tell a gap from
  a deliberate silence. Number and date formatting are already a per-locale setting elsewhere and are
  explicitly out of scope here.
- **FR-009**: Every rule MUST be stated so that a breach can be recognised by looking at a single
  value, without reference to any other string.

**The file itself**

- **FR-010**: Every French value MUST use the typographic apostrophe `’`. No French value may
  contain a straight apostrophe `'`.
- **FR-011**: Every space preceding `;`, `!` or `?` in a French value MUST be U+202F, and every space
  preceding `:` MUST be U+00A0. No ordinary space may precede any of the four.
- **FR-012**: Every French value quoting text MUST use guillemets, with U+202F inside each of them,
  and no French value may quote with straight or curly double quotes.
- **FR-013**: Every French value addressing the reader MUST use *vous*.
- **FR-014**: Applying these rules MUST NOT change what any string says. Placeholders, Markdown, HTML,
  counted-phrase separators and the wording itself are untouched.

**Catching a breach**

- **FR-015**: The routine that keeps translations in step MUST report a breach of FR-010 through
  FR-013 by name, identifying the locale, the value and the rule broken, rather than reporting a bare
  count.
- **FR-016**: The report MUST NOT raise a breach against a placeholder's colon, against markup, or
  against a counted phrase's separator.
- **FR-017**: Whatever catches a breach MUST be told which rules to apply by the locale's own section,
  so that giving a second locale rules is a matter of writing them and not of rebuilding the
  mechanism. A locale with no rules written yet MUST pass rather than fail.
- **FR-018**: A breach MUST fail. The report is not advisory, and a change carrying a breach does not
  pass the checks that guard a merge.
- **FR-019**: The check MUST NOT alter a translated value. It reports what is wrong and refuses, and a
  person decides the wording that fixes it.
- **FR-020**: Every existing breach in the shipped French file MUST be corrected within this feature,
  so that the check passes against the file the moment it exists.
- **FR-021**: No list of tolerated breaches may be kept anywhere. A value either satisfies the rules
  or is corrected.

### Key Entities

- **A French value**: one translated string in the shipped French locale, possibly carrying
  placeholders, markup and several counted wordings.
- **A rule**: one statement about one locale's copy, narrow enough that a single value either
  satisfies it or does not.
- **A locale's rules**: the set of rules written for one configured locale, possibly empty.
- **A breach**: one value failing one rule, named by both.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: A French reader never sees a punctuation mark begin a line on its own, at any screen
  width and with any substituted value, across every screen the product ships.
- **SC-002**: The French locale uses the typographic apostrophe `’` and nothing else, where 259 of
  its 485 apostrophe-bearing values use the straight one today.
- **SC-003**: The French locale uses one quotation convention, down from two.
- **SC-004**: Every French string that addresses the reader does so the same way.
- **SC-005**: A contributor translating a new string into French can answer every punctuation and
  address question from the written rules alone, without reading the existing translations.
- **SC-006**: No string changes meaning. The English source and the French value say the same thing
  before and after, for all 1708 values.
- **SC-007**: A French value breaching a rule is identified within the normal course of preparing a
  change, rather than at review time or after release.
- **SC-008**: A newly written French string that breaches a rule is caught before it reaches the
  shipped file.
- **SC-009**: Giving a second locale its own rules requires writing that locale's rules and changing
  nothing else.
- **SC-010**: The check passes against the shipped French file on the day it is introduced, with no
  value exempted and no breach recorded as tolerated.

## Assumptions

- French from France is the target. `fr_FR` is the only French locale configured, and Belgian,
  Canadian and Swiss conventions are not in scope.
- The conventions taken as authoritative are those of the Imprimerie nationale: the typographic
  apostrophe, U+202F before `;`, `!` and `?`, U+00A0 before `:`, and guillemets for quotation. Where
  a convention has competing variants, the rules name one and only one, so that a value is either
  right or wrong and never a matter of which variant the writer had in mind.
- *Vous* is confirmed rather than chosen. The file already uses it in all 272 places, so this records
  an existing decision instead of making a new one.
- The rules describe punctuation and address. They do not attempt to govern word choice, sentence
  length or tone, which the translation skill already covers by asking for the plain, calm register of
  the English source.
- The other eight locales get a section and nothing in it. Their translations are untouched, no
  convention is invented on their behalf, and nothing fails because their rules are unwritten. The
  structure exists so that somebody who reads German can fill in German without asking anyone's
  permission first.
- Number and date formats stay out. They are a per-user setting resolved elsewhere, on the same
  reasoning that kept digit grouping out of 001-fix-plural-strings.
- Nothing here changes an English string, a key, or the set of keys. The keys are the English
  sentences, and this feature only ever touches the French side of the pair.
