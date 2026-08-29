# Contracts

The project has no HTTP API yet, so these are the internal contracts the rest of the code
depends on. All logic sits in a helper or an action, so the API controller adds only
transport when it arrives.

## LocaleCollation

`app/Helpers/LocaleCollation.php`. The only place that asks ICU what a language does. No other
file may construct a `Collator` or a `Transliterator`. Not `final`, per the constitution.

It is one class rather than two: it absorbs the staged `CollectionSorter`, which is deleted. Both
needed the same per-locale `Collator`, cached, with the French attribute set, and one cache is
the point of the merge.

```php
public static function sortKey(string $text, ?string $locale = null): string
public static function foldForSearch(string $text, ?string $locale = null): string
public static function headingLetter(string $text, ?string $locale = null): string
public static function sortByCollator(Collection $collect, callable|string $callback, bool $descending = false): Collection
public static function getCollator(?string $locale = null): Collator
```

`sortByCollator` is the absorbed method, unchanged in behaviour: it orders a collection already
in hand by the reader's language. **Application code does not call it here.** It reaches it through
the `sortByCollator` macro on `Collection`, registered in `AppServiceProvider`, so a call site reads
as ordinary collection work and says nothing about ICU:

```php
User::query()->get()->sortByCollator('first_name')
```

The macro takes the same `callable|string $callback` and optional `bool $descending`, works on an
Eloquent collection as well as a support one, and hands back the same kind it was given. The static
is the implementation behind it and is called directly only by the macro itself and by the helper's
own unit test.

`getCollator` is the shared cache the other four read from, and it is what sets `FRENCH_COLLATION`
so no caller has to remember to.

`sortKey` returns a binary collation key, to be compared bytewise. Guaranteed:
`strcmp(sortKey($a), sortKey($b))` has the same sign as the collator's own comparison;
French gets `FRENCH_COLLATION` set to `ON`; an unknown locale falls back to root without
throwing; never exceeds 2048 bytes for a 511 character input.

`foldForSearch` lowercases for the language, then strips a character's mark only where the
collator ranks stripped and unstripped equal at primary strength. Guaranteed:

| Call | Result | Because |
|---|---|---|
| `('Élodie', 'fr_FR')` | `elodie` | French folds the accent |
| `('Müller', 'de_DE')` | `muller` | German folds the umlaut |
| `('Ñuñez', 'es_ES')` | `ñuñez` | Spanish `ñ` is its own letter |
| `('Işık', 'tr_TR')` | `ışık` | Turkish `ş` and `ı` are their own letters |

No character list may appear in the implementation. The collator decides every character.

`headingLetter` returns the single uppercase character a list groups the text under, or `#`
when it does not start with a letter. Uppercasing uses `Transliterator::create("{$lang}-Upper")`
where ICU has one and `Any-Upper` otherwise; `tr` is the only one of the nine. So
`headingLetter('ırmak', 'tr_TR')` is `I` and `headingLetter('irmak', 'tr_TR')` is `İ`.
`mb_strtoupper` gets both wrong and MUST NOT be used here.

## The two derived keys

Applies to `Contact` and `Entity`.

A model carrying `sort_name` MUST, in the same saving hook that builds it, set `sort_key` to
`LocaleCollation::sortKey($this->sort_name, $locale)` and `search_key` to
`LocaleCollation::foldForSearch($this->sort_name, $locale)`, where `$locale` is
`$this->account->locale()` and never the language of the request. Neither key may appear in
`$fillable`, be accepted from a request, or be set by a caller.

A view model ordering a paged list MUST order on `sort_key` then `id`, so equal ranking
entries keep a stable order and no row lands on two pages or none. It MUST take a heading from
`headingLetter()`, never from `mb_strtoupper`. When searching it MUST match the folded term
against `search_key` and keep matching the term as typed against `sort_name` and `nickname`, so
a reader who did type the accents still finds the entry.

A view model MUST NOT build, refresh or repair a key. That is a write, and writes are actions.

## Ordering without a stored key

Two lists have none, for two different reasons.

**The administrator filter** is held whole and never paged, so it orders through the macro and
is done:

```php
User::query()->get()->sortByCollator('first_name')
```

**The marketplace catalogue** is shared across accounts, so there is no one language to store a
key for. Ordering it by name MUST therefore read the names of everything matching, order them
through the macro, take the page's worth of ids, and load those rows with their relations in
that order. It MUST NOT order a page after slicing it, which orders each page against itself
and leaves the list wrong across pages.

## RebuildOrderingKeys

`app/Actions/RebuildOrderingKeys.php`. The only thing allowed to rewrite derived keys in bulk.
Promoted `readonly` `Account $account` and `string $locale`, one public `execute(): void`.

Guaranteed: every `sort_key` and `search_key` on `contacts` and `entities` in the account is
rebuilt for `$locale`, soft deleted rows included, since a restored row carrying a stale key is
filed where its reader will not look. All of it lands or none does, because the caller wraps the
language change and this action in one transaction. Idempotent. The locale is an input rather
than read from application state, so a job or command can call it.

Called by the action that changes a user's language, today `UpdatePreferences`, inside its
transaction. Nothing else: no view model, no controller directly, no model hook.
