---
name: add-locale
description: Add a new locale to the application. Use when the user wants to support an additional language, register a locale in config, and generate its lang/*.json translation file. Trigger whenever adding a language, new locale, or i18n support is mentioned.
---

# Add a new locale

You MUST use ISO 15897 names for region-specific languages, e.g. `fr_FR` for French from France and `es_ES` for Spanish from Spain.

The lang folder is what says a locale exists: `App\Helpers\AvailableLocales` reads `lang/*.json`, and
`config/localization.php` only describes what it finds. A locale is therefore added by writing its
files first and describing it second.

You MUST follow these steps, in order:

1. You MUST declare the locale in the `aliases` array of `config/localization.php` whenever the name
   this project uses differs from the one laravel-lang uses. laravel-lang names Dutch `nl` and Italian
   `it`, this project names them `nl_NL` and `it_IT`, and the alias is what maps one onto the other:
   ```php
   Locale::Dutch->value => 'nl_NL',
   ```
2. You MUST run `php artisan lang:add {locale} --no-interaction`. It writes `lang/{locale}.json` and
   `lang/{locale}/`, which hold the strings the framework and its packages supply, meaning validation,
   authentication, password reset, pagination, actions and HTTP statuses. From that moment the locale
   is available. The command refuses a locale laravel-lang does not know, and once the alias above is
   declared it takes either name.
3. You MUST describe the locale in the `locales` array of `config/localization.php`, with a `label` (the language in its own words), a `region` (the country in its own words), a `date_format` (the order that language reads dates in), a `number_format` (one of the `NumberFormatEnum` values) and a `flag` (a CSS `background` value drawn with gradients, never an image):
   ```php
   'nl_NL' => [
       'label' => 'Nederlands',
       'region' => 'Nederland',
       'date_format' => 'd-m-Y',
       'number_format' => 'dot_comma',
       'flag' => 'linear-gradient(to bottom, #ae1c28 0 33%, #fff 33% 66%, #21468b 66%)',
   ],
   ```
   A locale nobody describes is still offered, under its own code, so this step is what stops the
   language picker naming it `nl_NL`. A locale with a file and no entry here fails
   `tests/Unit/Helpers/AvailableLocalesTest.php`.
4. You MUST run `php artisan monica:localize`. It reports how many of Monica's own strings the new locale is missing. You MUST NOT create or order `lang/{locale}.json` by hand.
5. You MUST translate every key it reports as missing, adding it to `lang/{locale}.json`. Keep the `:placeholders` untouched, and match the plain, calm tone of `lang/en.json`.
6. You MUST run `php artisan monica:localize` again and confirm the new locale reports nothing missing.
7. You MUST NOT touch the language pickers or the validation rules. They all read `App\Helpers\AvailableLocales`, so a locale whose file is there shows up on its own.
8. You MUST fill the new locale's column in the glossary of
   [`.claude/rules/translations.md`](../../rules/translations.md), and add its row to the table
   recording which languages a native speaker has read.
9. You MUST add the locale to `WORDINGS_PER_LOCALE` in
   `tests/Unit/Translation/CountedPhraseTest.php`, saying how many wordings its plural rules ask for.
   A locale missing from that table fails the suite.
10. You MUST run `php artisan test` afterwards, since a test that counts the locales may need updating.
11. You MUST run `php artisan monica:localize --check` to confirm the new locale is in sync with the code.

You MAY also declare how the new language punctuates, as a `typography` key in the same
`config/localization.php` entry, and write its section in
[`.claude/rules/translations.md`](../../rules/translations.md). It is optional: a locale that declares
nothing is not checked, which is where most shipped locales stand. French is written there in full and
is the example to copy. Declare it only if you read the language, since the check then refuses every
value that breaks it, including the ones already translated.
