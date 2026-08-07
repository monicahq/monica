---
name: add-locale
description: Add a new locale to the application. Use when the user wants to support an additional language, register a locale in config, and generate its lang/*.json translation file. Trigger whenever adding a language, new locale, or i18n support is mentioned.
---

# Add a new locale

You MUST use ISO 15897 names for region-specific languages, e.g. `fr_FR` for French from France and `es_ES` for Spanish from Spain.

You MUST follow these steps, in order:

1. You MUST register the locale in the `locales` array of `config/monica.php`, with a `label` (the language in its own words), a `region` (the country in its own words) and a `flag` (a CSS `background` value drawn with gradients, never an image):
   ```php
   'nl_NL' => [
       'label' => 'Nederlands',
       'region' => 'Nederland',
       'flag' => 'linear-gradient(to bottom, #ae1c28 0 33%, #fff 33% 66%, #21468b 66%)',
   ],
   ```
2. You MUST create `lang/{locale}.json` with exactly the keys of `lang/en.json`, in the same order, formatted the same way (four space indent, unescaped UTF-8, trailing newline).
3. You MUST translate every key, and you MUST NOT leave a value empty or in English. Keep the `:placeholders` untouched, and match the plain, calm tone of `lang/en.json`.
4. You MUST NOT touch the language pickers or the validation rules. They all read `config('monica.locales')`, so a registered locale shows up on its own.
5. You MUST run `php artisan test` afterwards, since a test that counts the locales may need updating.
