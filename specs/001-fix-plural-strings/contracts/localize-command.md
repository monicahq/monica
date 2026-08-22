# Contract: what `monica:localize` reports and when it fails

The command keeps its present behaviour and signature. It gains knowledge of counted phrases and two
new reasons to fail.

## Signature

```text
php artisan monica:localize [--check]
```

Unchanged. Without `--check` it writes the locale files and reports. With `--check` it writes nothing
and turns anything out of date into a non zero exit.

## What it already does, unchanged

- Reads every `.php` file under `app/` and `resources/views/`, comments stripped, as text.
- Collects the first string argument of `__`, `trans`, `trans_choice` and `@lang`, plus everything the
  `Translatable` enums carry.
- Writes `lang/en.json` with every value equal to its key, and each other locale with only what has
  been translated.
- Reports arrivals, departures, and a per locale count of what is missing.
- Under `--check`, exits non zero when any of that is out of date.

## What it gains

### Counted phrases are understood

A key containing `|` is a counted phrase. Its segments are counted, and the number required for a
locale is probed from `MessageSelector::getPluralIndex()` rather than read from a table. A locale
holding the wrong number of segments for a phrase is missing a translation, and is reported and counted
as such.

### Two new failures, each naming where it is written

| Failure | Trigger | Message names |
|---|---|---|
| A phrase shows a number but has no plural wordings | A key containing `:count` collected from `__`, `trans` or `@lang` rather than `trans_choice` | The phrase, the file and the line |
| A wording is chosen by comparing a count to one | A comparison against `1` in a ternary whose branches contain a translation call | The file and the line |

Both fail the command under `--check`, alongside the existing out of date condition. Both are reported
without `--check` too, since a contributor running the command to write the files should see them.

### A phrase may declare itself number independent

A key whose single segment is prefixed `[0,*]` is exempt from the first failure, and requires exactly
one segment in every locale. The prefix is the declaration. Its absence is not.

## Exit codes

| Code | Meaning |
|---|---|
| 0 | Files written, or under `--check` everything up to date and neither bad pattern present |
| 1 | Under `--check`: files out of date, a counted phrase incomplete, or either bad pattern found |

## Stated limitation

The second failure is found by reading text, because the command reads text on purpose: a Blade file is
not PHP until compiled, and 10 of the affected files are Blade. It catches all 32 occurrences present
today. An exotic spelling of the same idea could slip past, and the command's output says so rather
than implying the absence of a report means the absence of the pattern.

## Backwards compatibility

Any phrase that shows no number is untouched, which is the overwhelming majority of the catalogue. A
contributor who never writes a number never meets any of this.
