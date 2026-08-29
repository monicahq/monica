# Phase 0 Research: Locale Aware Sorting

Everything below was measured at ICU 74.2, not reasoned about.

## Decisions

| # | Decision | Why |
|---|---|---|
| 1 | Order on a stored `Collator::getSortKey()` value in a binary column, compared bytewise. | Verified to give the same answer as the collator. Bytewise is the one ordering identical on all three databases. Asking the database to collate was rejected: SQLite ships no language collations, so the list would read differently per installation. Ordering in memory was rejected for a paged list: it abandons the index and slows as a vault grows. |
| 2 | Set `Collator::FRENCH_COLLATION` to `ON` for French. | Measured: off gives `cote coté côte côté`, on gives `cote côte coté côté`, which is the French order. `fr_FR` resolves its collation to `root`, so ICU ships no French tailoring and the convention must be asked for. The staged `CollectionSorter` already does this correctly, and that behaviour is absorbed into `LocaleCollation` rather than rewritten. |
| 3 | Fold for search by lowercasing with a locale aware transliterator, then stripping a character's mark only if the collator ranks stripped and unstripped equal at primary strength. | The only mechanism satisfying both search requirements at once, and it writes no character list, which FR-018 forbids. ICU is asked and ICU decides. Stripping all marks uniformly was rejected: it folds Spanish `ñ` and Dutch `ij`, ignoring the locale. |
| 4 | Uppercase heading letters with `Transliterator::create("{$lang}-Upper")`, falling back to `Any-Upper`. | Measured: `mb_strtoupper` maps both `i` and `ı` to `I`, giving a Turkish reader a heading that is not the letter under it. `tr-Upper` maps them to `İ` and `I` correctly. Of the nine languages `tr-Upper` is the only locale specific caser ICU ships, so this must be try and fall back, never a table. A tenth language then picks up its casing automatically. |
| 5 | Rely on ICU's own fallback for an unknown language. No code path of our own. | Measured: `zz_ZZ`, `xx` and `tlh` all construct, report `U_USING_DEFAULT_WARNING` and resolve to `root`. Nothing throws. FR-012 is satisfied by doing nothing, so the only thing worth testing is that a nonsense locale still renders. |
| 6 | Rebuild both keys synchronously, in one transaction with the language change. | An account is one user and a vault holds hundreds to a few thousand people, so this is a few thousand key computations and one bulk write. The transaction is what makes the all or nothing guarantee real: a list is never ordered on a mixture of two languages, because a failed rebuild rolls the language change back with it. A second key column with a pointer to flip was rejected as more machinery than the problem; a background rebuild reading partial state breaks the paging guarantee while it runs. |

## Measured: which letters fold, at primary strength

| Language | Pair | Verdict |
|---|---|---|
| French | `e` / `é` | same letter, folds |
| German | `o` / `ö` | same letter, folds |
| Spanish | `n` / `ñ` | different letters, does not fold |
| Turkish | `i` / `ı` | different letters, does not fold |
| Dutch | `i` / `ij` | different letters, does not fold |

**The consequence, stated plainly.** A French reader typing `elodie` finds Élodie and a German
typing `muller` finds Müller, but a Spanish reader typing `nunez` does **not** find Ñuñez, and
a Turkish reader typing `isik` does **not** find Işık. That is FR-018 working, not failing,
because those languages genuinely treat them as different letters. It will be reported as a
bug, so it belongs in the tests as an asserted expectation with the reason attached.

**Where the author was wrong.** The probe expected Turkish `Işık` to fold to `ısık`. It folds
to `ışık`, because Turkish treats `ş` as its own letter too. The collator was right and the
hand written expectation was wrong, on the second character of the second language checked.
That is the argument for Decision 3.

## Measured: sizing

15 bytes of name gave a 23 byte key; 616 bytes gave 843. Worst case for the existing 511
character `sort_name` is 1546 bytes, inside MySQL's 3072 byte index limit, so the column
indexes whole with no prefix.

## Decision 7: the shared catalogue cannot have a stored key

The marketplace catalogue is one list read by users of all nine languages. A key stored on the
row would have to pick one of them and be wrong for the other eight, unlike a vault, whose
account holds one person and therefore one language.

So it is the one paged list ordered when it is read: the names of everything matching are read,
ordered in PHP through the macro, and the page taken from the result. Only the sort by name pays
for it, the other three being dates and counts, which every language reads alike.

This was considered blocked before it was built, on the grounds that reading a whole catalogue
per page view contradicts the cost rule the contact list follows. It is accepted here because a
catalogue of templates and sections is small where a vault of people is not, and because the
alternative was leaving accented names filed past Z.

## Settled during implementation

The staged `AppServiceProvider` imported the `Illuminate\Cache\RateLimiter` class instead of
the facade, so nothing booted and no test could run. Its changes unrelated to sorting (rate
limiters, `Model::shouldBeStrict`, `CarbonImmutable`, `DB::prohibitDestructiveCommands`,
password defaults) were removed and saved aside. Only the `sortByCollator` macro belongs to
this feature.

The macro compares precomputed keys rather than asking the collator per pair, so sorting n
names costs n keys instead of about n log n comparisons. That is worth having wherever a list is
ordered in memory.
