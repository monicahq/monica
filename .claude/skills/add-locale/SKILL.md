---
name: add-locale
description: Add a new locale to the application. Use when the user wants to support an additional language, register a locale in config, and generate its lang/*.json translation file. Trigger whenever adding a language, new locale, or i18n support is mentioned.
---

# Add a new locale

You MUST use ISO 15897 names for region-specific languages, e.g. `fr_FR` for French from France and `es_ES` for Spanish from Spain.

You MUST follow these steps, in order:

1. You MUST update `config/app.php`:
   ```php
   'supported_locales' => ['en', 'fr_FR', 'es_ES'],
   ```
2. You MUST update the `composer.json` `kollek:locale` script with the same locale list:
   ```json
   "kollek:locale": "php artisan kollek:localize en,fr_FR,es_ES"
   ```
3. You MUST add any user-facing locale option labels to the English source language file.
4. You MUST update the UI controls that expose locale choices, such as `resources/views/app/settings/_detail.blade.php`.
5. You MUST run `composer kollek:locale`.
6. You MUST fill the new locale files completely, and you MUST NOT leave the generated empty strings in place.
