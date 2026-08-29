# Quickstart: validating Locale Aware Sorting

## Prerequisites

`intl` is the new hard dependency, so check it first:

```bash
php -m | grep intl
```

If that prints nothing, nothing below works and the application should be refusing to start.
On a `php:8.4` base image the extension is not enabled by default and needs
`docker-php-ext-install intl`.

```bash
php -r 'echo INTL_ICU_VERSION, PHP_EOL;'
```

## Run the tests

```bash
php artisan test --compact --filter='Collation|OrderingKeys'
```

## Scenarios

1. **A list reads in the reader's alphabet.** French interface, a vault holding Dwight
   Schrute, Élodie Bernard and Erin Hannon, ordered by name. Expect Bernard, Hannon, Schrute,
   with Bernard under a `B` heading rather than after `Z`. Then German, with Angela Martin,
   Ångström Nilsson and Michael Scott: expect Martin, Nilsson, Scott.
2. **Reversing is exact.** Reverse the same list. Expect the exact reverse, nothing moving
   relative to its neighbours.
3. **Paging loses nobody.** In a vault of more than 50 people, page to the end and collect
   every name. Expect each exactly once. Worth running against several identical names, since
   this is what catches an unstable tie break.
4. **Headings agree with their contents.** Turkish interface, a vault holding Işık and Irmak.
   Expect Işık under `I` and Irmak under `İ`. Both under `I` means something is calling
   `mb_strtoupper` instead of the helper.
5. **Search folds what the language folds, and only that.**

   | Interface | Type | Expect |
   |---|---|---|
   | French | `elodie` | finds Élodie |
   | German | `muller` | finds Müller |
   | Spanish | `nunez` | does **not** find Ñuñez |
   | Turkish | `isik` | does **not** find Işık |

   The last two are correct. Those languages treat them as different letters. See
   [research.md](research.md).
6. **Changing language is all or nothing.** Change it on an account of a thousand people:
   one step, then every list reads entirely in the new language, never a mixture. Force a
   failure inside the rebuild and confirm the language is still the old one.
7. **A nonsense language still renders.** Point a user's locale at something ICU does not
   know. Expect a list ordered neutrally and identically every reading, nothing thrown. ICU
   falls back on its own, so this tests that no code added a failure path of its own.

## Cross database check

Ordering must be identical on all three supported databases, because the comparison is
bytewise. Run the feature tests against each connection and compare the order for one
fixture.
