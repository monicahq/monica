---
name: add-locale
description: Add a new locale to the application. Use when the user wants to support an additional language, register a locale in config, and generate its lang/*.json translation file. Trigger whenever adding a language, new locale, or i18n support is mentioned.
---

# Add a new locale

You MUST use ISO 15897 names for region-specific languages, e.g. `fr_FR` for French from France and `es_ES` for Spanish from Spain.

You MUST follow these steps, in order:

1. You MUST register the locale in the `locales` array of `config/monica.php`, with a `label` (the language in its own words), a `region` (the country in its own words), a `date_format` (the order that language reads dates in), a `number_format` (one of the `NumberFormatEnum` values) and a `flag` (a CSS `background` value drawn with gradients, never an image):
   ```php
   'nl_NL' => [
       'label' => 'Nederlands',
       'region' => 'Nederland',
       'date_format' => 'd-m-Y',
       'number_format' => 'dot_comma',
       'flag' => 'linear-gradient(to bottom, #ae1c28 0 33%, #fff 33% 66%, #21468b 66%)',
   ],
   ```
2. You MUST run `php artisan monica:localize`. It creates `lang/{locale}.json` and reports how many strings the new locale is missing. You MUST NOT create or order that file by hand.
3. You MUST translate every key it reports as missing, adding it to `lang/{locale}.json`. Keep the `:placeholders` untouched, and match the plain, calm tone of `lang/en.json`.
4. You MUST run `php artisan monica:localize` again and confirm the new locale reports nothing missing.
5. You MUST NOT touch the language pickers or the validation rules. They all read `config('monica.locales')`, so a registered locale shows up on its own.
6. You MUST run `php artisan test` afterwards, since a test that counts the locales may need updating.
