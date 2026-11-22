# Quickstart: Contact sort names follow each language

How to see the feature work once it is built. The rules are in [research.md](research.md) R2.

## Automated

```bash
PAO_DISABLE=1 php artisan test --parallel --filter='NameFilingTest|LocaleCollationTest|RebuildOrderingKeysTest|UpdatePreferencesTest|BackfillContactSortNamesTest'
```

Then the whole suite, since `Contact` is under most of it.

## By hand

Walk the acceptance scenarios of [spec.md](spec.md), stories 1 to 3 and scenario 2 of story 4, in the
product: set the interface language in the preferences, record the names, order the people list by
name. For the Japanese scenario, record each reading as a pronunciation whose script is Hiragana.

Then check what must not move:

- Search for a name in the order it is displayed, and in the order family, given. Both find it, in
  every language of those scenarios, as they did before.
- Open the relationship form and the group member picker. They list people in the same order as the
  people list.

## Upgrade

Run `php artisan migrate` on a copy of a database from before the change, for accounts reading in
Icelandic, Dutch and English. The first two are reordered, the third is not, and no name column
differs from the copy.
