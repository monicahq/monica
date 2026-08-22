# Phrase classification

Regenerated from `lang/en.json` by T047, after convergence found the first version stale. It
now describes what shipped rather than what the superseded line window analysis predicted.

**Totals**: 44 counted phrases, of which 10 pin their singular to exactly one
number, and 3 phrases declare that their wording does not depend on their number.

Eighteen standalone `1 xxx` keys were absorbed and removed. One `1 section` literal remains in
`ShareSectionViewModel::structureLabel` and is not a counted phrase: a section export always holds
exactly one section, so the number is a constant rather than a count.

## Counted phrases, singular chosen by the language

Written `singular|plural`, every segment interpolating `:count`. Turkish takes the first segment
alone, the other eight locales take both.

| # | Phrase |
|---|---|
| 1 | `:count contact|:count contacts` |
| 2 | `:count field on this page|:count fields on this page` |
| 3 | `:count field|:count fields` |
| 4 | `:count installation|:count installations` |
| 5 | `:count line · :size|:count lines · :size` |
| 6 | `:count list of choices|:count lists of choices` |
| 7 | `:count listing|:count listings` |
| 8 | `:count new list|:count new lists` |
| 9 | `:count of :total shown.|:count of :total shown.` |
| 10 | `:count page|:count pages` |
| 11 | `:count person|:count people` |
| 12 | `:count person|All :count people` |
| 13 | `:count published something this week|:count published something this week` |
| 14 | `:count record across all vaults|:count records across all vaults` |
| 15 | `:count record uses this type|:count records use this type` |
| 16 | `:count record|:count records` |
| 17 | `:count section|:count sections` |
| 18 | `:count template|:count templates` |
| 19 | `:count type|:count types` |
| 20 | `:count value list|:count value lists` |
| 21 | `:count value|:count values` |
| 22 | `:count will be added|:count will be added` |
| 23 | `Active · :count API key|Active · :count API keys` |
| 24 | `Brought back :count value written in :label|Brought back :count values written in :label` |
| 25 | `If that address has an account, a link is on its way. It works once, and only for the next :count...` |
| 26 | `Remember me for :count day|Remember me for :count days` |
| 27 | `Removed :count file nothing pointed at any more|Removed :count files nothing pointed at any more` |
| 28 | `Set aside :count value written in :label|Set aside :count values written in :label` |
| 29 | `The address stops working straight away. It was opened :count time, last :when. The row stays on ...` |
| 30 | `The file holds more than :count character at :path, which is more than can be stored.|The file ho...` |
| 31 | `The file holds more than :count thing at :path.|The file holds more than :count things at :path.` |
| 32 | `The link works once, and only for the next :count minute.|The link works once, and only for the n...` |
| 33 | `This file carries more than :count list of choices.|This file carries more than :count lists of c...` |
| 34 | `This file describes more than :count field, which is more structure than one file is meant to car...` |

## Counted phrases with a singular pinned to one

The wording at exactly one is deliberate copy, spelled as a word rather than a digit, and the
tests for these screens ask for it. `{1}` pins it, so the segment needs no `:count`: the number is
already known. See research decision D9.

A pinned singular cannot serve zero, because "One section" is wrong for none in every language.
Every one of these sites guards zero, and the check enforces it: it reads back from the call to the
start of its function looking for something that deals with zero. That check found the third defect of
this kind, in `ContactsViewModel::resultLine`, after two rounds of review had missed it. None of these
phrases may be written in a Blade file, since a guard there belongs to the screen rather than to the
phrase, and the check refuses that outright.

| Phrase | Used at | Zero guarded |
|---|---|---|
| `{1}End of the list. One person, no pages.|End of the list....` | `ContactsViewModel.php::endLine` | yes |
| `{1}One person here was written down as a partial contact a...` | `ContactsViewModel.php::emptyLine` | yes |
| `{1}One person in this vault.|All :count people in this vau...` | `ContactsViewModel.php::resultLine` | yes |
| `{1}One section|:count sections` | `ShareLinkSummary.php::heldBackLine` | yes |
| `{1}One value written under another version is kept out of ...` | `ContactViewModel.php::setAsideLine` | yes |
| `{1}Opened once|Opened :count times` | `ContactShareLinksViewModel.php::openedLine` | yes |
| `{1}Opened once|Opened :count times` | `ShareLinkViewModel.php::visitsHeader` | yes |
| `{1}Shared by one link|Shared by :count links` | `ContactViewModel.php::shareLine` | yes |
| `{1}Takes the one person in it and everything written about...` | `VaultDashboardViewModel.php::deletionConsequence` | yes |
| `{1}The one contact in it goes too, and everything written ...` | `VaultSettingsViewModel.php::deletionConsequence` | yes |
| `{1}used once|used :count times` | `DefaultValuesViewModel.php::usageLabel` | yes |

## Phrases whose wording does not depend on their number

Declared with `[0,*]`, which `trans_choice` strips and `__()` would print. One segment in every
locale.

- `[0,*]:count in :month`
- `[0,*]:count in the last thirty days`
- `[0,*]Show :count more`

## Not counted phrases after all

Two sentences used `:count` to receive an already built label such as "3 records", not a number.
The placeholder is now `:records`, and the counted phrase is the injected label itself.

- `The :records on this template, and everything written in their fields. ...`
- `Two ways out, ... and the :records using this template stay here.`
