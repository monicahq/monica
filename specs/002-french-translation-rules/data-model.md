# Data Model: French Translation Rules

**Date**: 2026-08-26 | **Spec**: [spec.md](./spec.md) | **Phase**: 1

Nothing here reaches the database. The entities are a configuration array, the values in a locale
file, and the breach that connects them.

## A locale's typography rules

Lives at `config('monica.locales.{locale}.typography')`, beside the `label`, `region`, `date_format`,
`number_format` and `flag` that locale already declares. Absent or empty means the locale has no rules
and every one of its values passes.

| Key | Type | Meaning |
| --- | --- | --- |
| `apostrophe` | `string` | The one character an apostrophe is written with. |
| `forbidden_apostrophes` | `list<string>` | Characters that are never an apostrophe in this language. |
| `space_before` | `array<string, string>` | Punctuation mark to the space character required before it. |
| `quotes` | `array{open: string, close: string, inner_space: string}` | The quotation pair, and the space written inside each mark. |
| `forbidden_quotes` | `list<string>` | Quotation characters this language never uses. |
| `forbidden_words` | `list<string>` | Words that must not appear, matched on word boundaries, case insensitively. |

Every key is optional. A rule that is not declared is not checked, which is what makes an empty
declaration and an absent one behave identically.

### French, filled in

```php
'typography' => [
    'apostrophe' => '’',                       // U+2019
    'forbidden_apostrophes' => ["'"],          // U+0027
    'space_before' => [
        ':' => "\u{00A0}",                     // full non-breaking space
        ';' => "\u{202F}",                     // narrow non-breaking space
        '!' => "\u{202F}",
        '?' => "\u{202F}",
    ],
    'quotes' => ['open' => '«', 'close' => '»', 'inner_space' => "\u{202F}"],
    'forbidden_quotes' => ['"', '“', '”'],
    'forbidden_words' => ['tu', 'toi', 'tes'],
],
```

The escape sequences are deliberate. Both space characters are invisible, so writing them literally in
a PHP file would leave the config unreadable and unreviewable. This is the same reason FR-005 makes the
document name them by code point. Note that the file they are written into does not get the same
choice: `synchronize()` encodes with `JSON_UNESCAPED_UNICODE`, so `lang/fr_FR.json` carries them
literally. See [D4](./research.md).

The other eight locales declare no `typography` key. Nobody has written their rules, and this feature
does not invent them.

## A translated value

One entry in `lang/{locale}.json`. The key is the English sentence, the value is the translation. For
the purposes of these rules a value has three kinds of content, and only the first is judged:

| Content | Example | Judged |
| --- | --- | --- |
| Prose | `Supprimer définitivement ?` | Yes |
| Placeholder | `:name`, `:count` | No. FR-014, FR-016 |
| Counted-wording separator | the `\|` in `:count personne\|:count personnes` | No. FR-014, FR-016 |

A counted phrase is several wordings in one value. Each wording is prose in its own right and is
judged, while the separator between them is structure and is not.

## A breach

One value failing one rule. It carries the locale, the key, and which rule was broken. It is never
recorded anywhere: it is reported, and then either the value is corrected or the check keeps failing.
FR-021 forbids storing it.

| Rule | A value breaches it when | Requirement |
| --- | --- | --- |
| Apostrophe | It contains a character from `forbidden_apostrophes`. | FR-010 |
| Spacing | A mark in `space_before` is preceded by any space other than the one required, or by no space. | FR-011 |
| Quotation | It contains a character from `forbidden_quotes`, or a quotation mark without `inner_space` inside it. | FR-012 |
| Address | It contains a word from `forbidden_words` on a word boundary. | FR-013 |

### The colon exclusion

A colon immediately followed by a letter or an underscore opens a placeholder and is not punctuation.
`Supprimer :name ?` breaches the spacing rule once, at the `?`, and not at the `:`. This is exact
rather than approximate, because Laravel's placeholders are `:` followed by word characters, and
because no French value contains a time, a ratio or a URL scheme. Both facts were measured. See
[D7](./research.md).

## What the shipped French file looks like against this model

Measured on 2026-08-26 across all 1708 values.

| Rule | Values breaching |
| --- | --- |
| Apostrophe | 259 |
| Spacing before `:` | 40 |
| Spacing before `;`, `!`, `?` | 34 |
| Quotation | 11 (10 guillemets with ordinary spaces inside, 1 using straight double quotes) |
| Address | 0 |

Around 340 distinct values once the overlaps are counted once. All of them are corrected in this
feature (FR-020), after which each number above is zero and stays zero.
