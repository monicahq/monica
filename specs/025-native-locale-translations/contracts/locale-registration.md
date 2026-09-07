# Contract: registering a locale

One entry in the `locales` array of `config/monica.php`, keyed by the ISO 15897 code.

```php
'ja_JP' => [
    'label' => '日本語',
    'region' => '日本',
    'date_format' => 'Y/m/d',
    'number_format' => 'comma_dot',
    'flag' => 'radial-gradient(circle at 50% 50%, #bc002d 0 30%, #fff 30%)',
],
```

| Key | Required | What it holds |
| --- | --- | --- |
| `label` | yes | The language written in that language. Read by every picker. |
| `region` | yes | The country written in that language. |
| `date_format` | yes | The order that language reads a date in, as a PHP date format. |
| `number_format` | yes | One of `comma_dot`, `dot_comma`, `space_comma`, `no_separator`. `NumberFormatEnum::from()` throws on anything else. |
| `flag` | yes | A CSS `background` value built from gradients. Never an image. Rendered inline on a 20 by 14 pixel box. |
| `typography` | no | Declared only once a native reviewer confirms the rules. A locale that declares nothing is not checked. |
| `answers_bare_language` | no | Only where a language has two shipped variants. Portuguese is the only one. |

A missing `label`, `region` or `flag` throws in `GuestViewModel::locales()`.

## Files the registration requires

| Path | Strings | Written by |
| --- | --- | --- |
| `lang/<locale>.json` | 2,206 | `php artisan monica:localize`, never by hand |
| `lang/<locale>/validation.php` | 138 | by hand, against `lang/en/` |
| `lang/<locale>/auth.php` | 3 | by hand |
| `lang/<locale>/passwords.php` | 5 | by hand |
| `lang/<locale>/pagination.php` | 2 | by hand |

## What a translated value must satisfy

- Every `:placeholder` survives exactly as the English carries it. No source string carries HTML or
  Markdown, so nothing else needs preserving.
- A counted phrase carries the number of wordings its language asks for, and every wording that is
  not pinned to a single number interpolates `:count`.
- A value never comes back empty. Leave the key out instead, which the command does for you.
- A product noun uses the word the glossary fixes for that language, every time.
- A log sentence reads as something somebody did, in the tense that language uses for a completed
  action.
- An example is localized rather than translated: `+44 7700 900461`, `14 June 2019`,
  `14 June, every year`, `18.4` and `12-hour` each read as that country writes them.

## The glossary

A table in `.claude/rules/translations.md`, one column per locale, one row per product noun: vault,
template, section, account, field, reminder, contact, passkey, relationship, value list, marketplace,
note, share, API key, group, activity. About 800 of the 2,206 strings hold at least one of them.

## The review record

A table in the same file naming, per locale, whether a native speaker has read it. A locale ships
either way, and the record is what says which are still waiting.
