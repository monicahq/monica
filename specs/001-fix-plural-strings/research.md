# Phase 0 Research: Correct Plural Phrasing in Interface Copy

Every decision below was checked by running the framework in this repository, at Laravel 13.23.0 and
PHP 8.4.24, not by reading its source alone. The commands used are in [quickstart.md](./quickstart.md)
so a reviewer can repeat them.

## D1. How a counted phrase is written

**Decision**: `trans_choice()` with the plural forms in one string separated by `|`, and the key is
that whole pipe separated English sentence.

```php
trans_choice(':count person|:count people', $count)
```

**Rationale**: it is the mechanism Laravel documents for this exact problem, which Principle IV makes
the default. It also preserves the project's existing convention without amendment: `LocalizeCommand`
already treats the first string argument of `__`, `trans`, `trans_choice` and `@lang` as the key, and
already writes the English file with every value equal to its own key. A pipe separated sentence is
still just a sentence, so `en.json` keeps its shape and the eight other files hold one translated
pipe separated sentence per phrase.

Verified: a JSON key containing `|` survives the command's own `json_encode` flags and round trips
identically. `trans_choice` adds `count` to the replacements itself, so no call site has to pass it.

**Alternatives considered**:

- A key that names the phrase (`contacts.count`) with the forms in a PHP array file. Rejected: it
  breaks the convention that the key is the English sentence, which is what makes `en.json` readable
  and what `monica:localize` is built around.
- Two keys and a helper that picks between them. Rejected: that is what the codebase does today in 32
  places, and it is the defect.

## D2. Every segment interpolates `:count`, and the digit is never written literally

**Decision**: each segment of a counted phrase MUST contain `:count`. Writing `1 person` as the
singular is forbidden.

**Rationale**: this is the finding that changes the shape of the work. French groups zero with the
singular, so the singular segment is what a French reader sees at zero. If that segment is the literal
`1 personne`, a count of zero renders **"1 personne"**. Measured:

| Phrase in `fr_FR.json` | count 0 | count 1 | count 2 |
|---|---|---|---|
| `1 personne\|:count personnes` | `1 personne` (wrong) | `1 personne` | `2 personnes` |
| `:count personne\|:count personnes` | `0 personne` (right) | `1 personne` | `2 personnes` |

English loses nothing: `:count person` at a count of one renders `1 person`, character for character
what `1 person` renders.

**Consequence**: the 18 standalone `1 xxx` keys in `en.json` are not promoted into segment 0. Their
wording is reused with `1` replaced by `:count`, and the keys themselves are removed.

**Alternatives considered**: keeping the literal singular and adding an explicit `{0}` segment for
zero. Rejected: it needs a third segment on every phrase in every locale to fix one language, when
the one character change to the segment fixes it everywhere.

## D3. How many segments a locale needs, and where that number comes from

**Decision**: probe `Illuminate\Translation\MessageSelector::getPluralIndex()` across a range of
numbers and take the highest index it returns, plus one. Do not keep a table.

**Rationale**: `getPluralIndex` is public, and it is the same function that will choose the segment at
render time, so probing it cannot disagree with what a reader sees. A table in config would be a
second source of truth that drifts the moment a locale is added. Probed result for the nine configured
locales:

| Locale | Segments needed |
|---|---|
| en, de_DE, es_ES, fr_FR, it_IT, nl_NL, pt_BR, pt_PT | 2 |
| tr_TR | 1 |

French needs two but groups zero with the singular, which is D2's reason for existing. Turkish returns
index 0 at every number, so a second segment is unreachable text. Probing also means Polish (3),
Russian (3) or Arabic (6) would be handled the day one is configured, with no code change, which is
what the spec claims and no more.

**Alternatives considered**: a `plural_forms` key beside each locale in `config/monica.php`. Rejected
under Principle II and because it can disagree with the selector that actually runs.

## D4. How a phrase declares that its wording does not depend on its number

**Decision**: a single segment prefixed with Laravel's explicit universal range, `[0,*]`, requested
through `trans_choice`.

```php
trans_choice('[0,*]:count of :total shown.', $count, ['total' => $total])
```

**Rationale**: it satisfies FR-018 and FR-019 with no invention. The declaration lives inside the
phrase itself rather than in a list kept elsewhere, it is deliberate (somebody typed it), and it is
visible both in review and in the catalogue, where `grep '\[0,\*\]' lang/en.json` lists every phrase
that claims independence so a reviewer can read them together as SC-010 requires.

Verified: `MessageSelector::extract` matches `[0,*]` for every number and `choose` returns the segment
trimmed of the prefix, under `en`, `tr_TR` and `pl` alike. Verified in French through a real locale
file: `[0,*]:count sur :total affichés.` renders `1 sur 12 affichés.` and `5 sur 12 affichés.`

**Extended after the fifth convergence pass**: the rule below keys on the count placeholder, and D9
later allowed a pinned wording to carry no placeholder at all, which left a phrase such as
`__('{1}Just one thing')` printing its condition to the reader with nothing to catch it. The check now
also refuses any wording carrying a condition when it is asked for by a call that cannot read one. It
matches the framework's own condition shape, so a sentence merely opening with a bracket, such as
"[Draft] something", is left alone.

**The reason the rule is uniform**: `__()` does **not** strip the prefix. `__('[0,*]:count of :total
shown.')` renders the literal `[0,*]3 of 12 shown.` to the reader. So a declared phrase has no choice
but to go through `trans_choice`, which collapses the whole rule to one sentence: any phrase whose
text contains `:count` is requested through `trans_choice`. That single invariant is what the check
enforces, and silence is not a declaration, exactly as FR-019 demands.

**Alternatives considered**:

- Renaming the placeholder (`:number` instead of `:count`) to mean "does not vary". Rejected: nothing
  on the page tells a reader of the code that the rename was a decision rather than a preference, which
  fails FR-019's requirement that the declaration be visible.
- A single segment with no prefix. Rejected: indistinguishable from a phrase whose translator supplied
  one form and forgot the other, so the check could not tell a declaration from an omission.
- A list of exempt phrases. Rejected by the spec in FR-018, and it drifts.

## D5. What happens to a locale that has not translated a phrase yet

**Decision**: nothing to build. `Translator::choice()` calls `localeForChoice()`, which falls back to
the fallback locale when the key is absent for the reader's locale, so the English text is selected by
English rules.

**Rationale**: this was a real risk worth checking. Had the fallback kept the reader's plural rules
while using English text, an untranslated phrase under Turkish would always take segment 0, rendering
"5 person". Measured under `tr_TR` with the phrase absent: count 1 gives `1 record`, count 5 gives
`5 records`. Correct. Story 2 acceptance scenario 5 therefore holds by construction, and the test for
it is a guard against a future regression rather than a thing to build.

## D6. Where the two bad patterns are detected

**Decision**: in `LocalizeCommand`, which already reads every file under `app/` and
`resources/views/` as text with comments stripped, and already finds every translation call by regex.

- **Pattern one**, a phrase that shows a number without plural wordings: the command already knows
  which function each key came from, once that association is kept rather than merged away. A key
  containing `:count` collected from `__`, `trans` or `@lang` is a failure.
- **Pattern two**, a wording chosen by comparing a count to one: a comparison against `1` in a ternary
  whose branches contain a translation call. Text detectable, since the command holds the file's text.

**Rationale**: putting it here means one place in the codebase knows what a translatable string looks
like, and it is the place that already runs as part of the definition of done, so the existing per
change checklist catches this misuse with no new line of its own. `--check` already turns its report
into a non zero exit, and `static.yml` and the done checklist already run it.

**Alternatives considered**:

- A custom PHPStan rule. Rejected: it would be a second place that knows what a translatable string is,
  and it cannot see Blade, where 10 of the affected files live.
- A test that greps the tree. Rejected for the same duplication reason, though the command's own tests
  will assert both failures, which is where that intent belongs.

**Known limitation, to be stated in the command's output rather than hidden**: pattern two is found by
reading text, so an exotic spelling of the same idea could slip past. It catches all 32 present
occurrences, and a false positive is one line to fix. This is recorded in Complexity Tracking.

## D7. The one non view model render path

**Decision**: `Log::description` at [app/Models/Log.php:202](../../app/Models/Log.php#L202) calls
`__()` on a key that `UserActionEnum::description()` returns, with the log's stored parameters. Three
of those sentences are counted phrases ("Removed :count files nothing pointed at any more", "Set aside
:count values written in :label", "Brought back :count values written in :label"), so this accessor
must call `trans_choice` when the key is a counted phrase.

**Rationale**: found by tracing the enum rather than assuming the phrases were only in view models. It
is the only render path outside a view model or a Blade template, and missing it would leave three
changelog sentences reading "Removed 1 files nothing pointed at any more" while everything else was
fixed.

## D8. Where the written rule lives

**Decision**: the Translations section of `.claude/rules/code-style.md`, which today holds one bullet.
No constitution amendment.

**Rationale**: Principle V already requires plurals to be translated, so the gap is a missing concrete
rule and not a missing principle. Governance places naming details and style minutiae in
`.claude/CLAUDE.md` and `.claude/rules/`, and says the constitution governs the repository layout and
nothing else. The rule is one sentence for each of the three invariants: any phrase containing
`:count` goes through `trans_choice`, every segment interpolates `:count`, and a phrase whose wording
does not vary says so with `[0,*]`.

## D9. A singular pinned to exactly one number

**Added during implementation, and not present in the spec.** Recorded here because the design has to
be traceable, and because the next person to touch a counted phrase will meet it.

**Decision**: where the copy at exactly one is deliberately spelled as a word rather than a digit, the
singular is pinned with Laravel's explicit `{1}` condition and needs no `:count`.

```php
trans_choice('{1}Opened once|Opened :count times', $count)
```

**Rationale**: the mechanical conversion turned "Opened once" into "Opened 1 time", "One section" into
"1 section", and "Shared by one link" into "Shared by 1 link". Nine tests objected, one of them named
`it_says_once_rather_than_one_time`, which is a product decision written down as a test rather than an
accident. `{1}` keeps that copy while still removing the comparison against one from PHP, which is
what FR-004 asks for, and it leaves the choice in the phrase where a translator can reach it.

Ten phrases use it, listed in [phrase-classification.md](./phrase-classification.md).

**The trap it carries**: `{1}` matches one and nothing else, so zero falls through to the plural rules
and lands on segment 0, which is the pinned singular. A French reader therefore saw "Le contact qu'il
contient part aussi" for a vault holding no contacts, and "Fin de la liste. Une personne" for a list of
none. Convergence found both. A pinned singular cannot serve zero in any language, because the wording
names one thing.

**The rule that follows**: a phrase with a pinned singular MUST keep zero away from it, and the guard
has to sit in the method that builds the phrase rather than in the screen that shows it. Leaving it to
the screen was tried and failed twice: `ContactsViewModel::resultLine` was rendered above the empty
state branch, so an empty vault's header read "Une personne dans ce coffre.", and the same shape in
`endLine` was safe only by accident of where the view put it. Both now return nothing at zero.

**This is enforced.** The check finds a `trans_choice` call whose wording is pinned to a single number
and reads back to the start of its function looking for something that deals with zero. It is the
check that found the `resultLine` defect, after two rounds of review had missed it. It reads text
rather than parsing it, so it can be spelled around, and it says so in its own output.

**A pinned wording may not live in a view.** A view has no function to read back to, so the check
originally fell back to a window of nearby text and accepted any comparison in it: a pinned phrase
preceded by an unrelated `@if ($other > 0)` passed. Rather than try to prove a guard written in a
screen, a pinned wording in a view is now refused outright, which is both stronger and what the rule
already implied. The one phrase that lived in a view, the vault delete confirmation, moved into
`VaultSettingsViewModel::deletionConsequence()`, which returns nothing when the vault holds nobody.

**Consequence for the check**: a wording pinned to a *single* number is exempt from the rule that every
wording interpolates `:count`, because there the number is already known. A wording covering more than
one number is not exempt, since `[0,*]1 gadget` reads "1 gadget" at every number, which is the original
defect wearing a condition. The exemption therefore reads the condition rather than merely noticing
that one is present.

**Alternatives considered**:

- Accept the digit and update the nine tests. Rejected: it overrides a product decision as a side
  effect of a refactor, which is not a refactor's business.
- Keep the comparison against one for these phrases only. Rejected: FR-004 forbids it, and it is the
  defect the feature exists to remove.
- Give every phrase an explicit `{0}|{1}|[2,*]` triple so zero is always covered. Rejected under
  Principle II: it triples the segments a translator handles, and mixing explicit and implicit segments
  selects the wrong wording above two, which is a sharper edge than the one it removes.
