# Research: Contact sort names follow each language

Sources read on 2026-11-21. CLDR is `cldr-person-names-full` 49.0.0 (`sorting` pattern), AACR2 is rule 22.5D1 (2002 revision).

## R1. Where a filing rule lives

**Decision**: a `name_filing` key in each locale of `config/localization.php`, beside `typography`. Keys:
`files_under` (`family` or `given`), `skipped_particles` (a list of phrases), `phonetic_scripts` (a list
of ISO 15924 codes). All optional. A locale without the key files family first and skips nothing.
**Rationale**: that array already describes each language, which gains a rule by being described (003 FR-014).
**Rejected**: reading CLDR at run time (PHP's `intl` exposes no person name data), one class per language.

## R2. The table of shipped languages

| Locale | Files under | Skipped particles | Reading scripts | Source |
| --- | --- | --- | --- | --- |
| is_IS, si_LK, ta_IN | given | | | CLDR: the pattern opens with `{given}` |
| vi_VN | given | | | Chicago Manual of Style, quoted by Wikipedia, "Vietnamese name": "Diem, Ngo Dinh", and Vietnamese sources order names the same way |
| nl_NL | family | af, aan, bij, de, den, der, het, 't, ’t, in, onder, op, over, 's, ’s, te, ten, ter, tot, uit, uijt, van, voor | | AACR2, Dutch. Wikipedia, "Tussenvoegsel": ignored when sorting in the Netherlands |
| de_DE | family | von und zu, von der, von dem, von den, von, zu, van der, van den, van de, van | | AACR2, German |
| fr_FR | family | de, d', d’ | | AACR2, French: a preposition is skipped, elided or not (Aubigné, Agrippa d'), an article (Le, Du) is not |
| es_ES | family | de la, de las, de los, del, de | | AACR2, Spanish: an article alone is not skipped |
| pt_PT, pt_BR | family | da, das, de, do, dos, d', d’ | | AACR2, Portuguese: every prefix is skipped. Its examples are da and dos, none is elided |
| ja_JP | family | | Hira, Kana | Wikipedia, "Gojūon": the prevalent order for collating Japanese. Checked here: the collation orders 山田 before 鈴木, against their readings |
| The other 21 | family | | | CLDR: the pattern opens with the surname. AACR2 files English and modern Italian names under the prefix |

One row goes against CLDR, on purpose: it files Vietnamese under the surname. The source above
describes what those readers do, and the spec fixed that row. Thai follows CLDR, which gives it the
English pattern, by the owner's decision.

Particles follow AACR2, not CLDR. CLDR puts a prefix last for most languages, English included, but
expects it as a field of its own. Here it is read off the family name, so which words count has to be
a list per language. No Scandinavian language has one: AACR2 makes it depend on the origin of the
prefix, which would be detecting a language (070 FR-008).

## R3. How a sort name is built

**Decision**: one helper, `App\Helpers\NameFiling`. Family first gives family, given, additional. Given
first gives given, additional, family. The longest skipped phrase opening the family name is removed,
again until none matches or nothing would remain, and what was removed goes last, as the CLDR pattern
`{surname-core}, {given} {given2} {surname-prefix}` has it. A name with no part files under its full
name, then nickname, then maiden name, as today. A phrase ends at a space, or at its own apostrophe.
**Rejected**: skipping word by word (Spanish La Fuente would lose its article).

## R4. Letter case

**Decision**: the sort name is lowercased with the reader's language, through a public
`LocaleCollation::lower()` over the transliterator `foldForSearch()` already uses. Checked here:
`mb_strtolower('Irmak')` gives `irmak`, which the Turkish collation ranks after `ipek`, apart from `ırmak`.

## R5. Readings

**Decision**: where the rule lists `phonetic_scripts`, the contact files under its first pronunciation
(kind phonetic, by position) whose recorded script is listed. Saving or deleting a representation that
is not the displayed one re-saves the contact. One more query per save, for such accounts only.
**Rejected**: reading the script off the characters (070 FR-008), the `x-romaji` and `ipa` systems.

## R6. Search is left alone

**Decision**: `contacts.search_key` keeps being folded from the family first text it is folded from
today, byte for byte, so it no longer derives from `sort_name`. No search changes (FR-008).
**Left as found**: that text is still lowercased the English way (the representation keys cover the
Turkish case), and `contact_name_representations.sort_name`, read by nothing, keeps the old rule.

## R7. Language change and upgrade

**Decision**: `RebuildOrderingKeys` rebuilds `sort_name` and both keys for contacts from their name
columns, through the two methods the `Contact` hook calls, so `search_key` stays the fold of the family
first text (R6). It runs inside the transaction `UpdatePreferences` already opens (FR-009). Entities
keep their path. A migration `backfill_contact_sort_names` runs the action for each account, one
transaction per account, in that account's language (FR-010). That calls an action where the migrations
skill wants the query builder: the rule must not exist twice, and re-saving every contact, as
`backfill_contact_match_keys` does, leaves no account all or nothing.

## R8. Lists that skip the ordering key

Six lists of contacts order by `sort_name` under the database's own alphabet: the relationship form,
the contact field choices, and the group member, possession loan and life event (twice) candidates.
**Decision**: they order by `sort_key` (FR-007). Lists of entities are out of scope.
