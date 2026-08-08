---
name: translations
description: Keep the lang/*.json files in step with the code using the monica:localize command. Use when UI copy changes, new strings are added, or locale files are out of sync. Trigger whenever translation keys, lang files, i18n, or __() / @lang() strings are mentioned.
---

# Translations

The strings themselves are the keys, in one JSON file per locale under `lang/`. A key missing from a locale falls back to the key, which is the English sentence, so a missing translation reads as English rather than as a blank.

`php artisan monica:localize` is what keeps those files in step with the code. You MUST NOT add, remove or reorder a key by hand.

## What the command does

- Reads every `__()`, `trans()`, `trans_choice()` and `@lang()` in `app/` and `resources/views/`, with comments taken out first.
- Reads every case of every enum in `app/Enums` implementing `App\Contracts\Translatable`, since those strings are wrapped only where they are read.
- Writes `lang/en.json` with every string found, each its own value.
- Leaves every other locale holding only what has been translated, dropping any key the code no longer asks for and reporting what it dropped.
- `--check` writes nothing and fails when a string is missing or stale. A test runs it against the shipped files, so the suite fails when this has not been run.

## When you change or add copy

1. Write the string in the code as usual. A string in an enum belongs in `translationKeys()`, and the enum MUST implement `Translatable`.
2. Run `php artisan monica:localize`. Read what it says it added and removed.
3. Translate every string it reports as missing, in every locale of `config('monica.locales')`.
4. Run `php artisan monica:localize --check` and confirm it passes.

## Writing a translation

- You MUST preserve placeholders exactly: `:name`, `:count`, `:app`, `:time`.
- You MUST preserve any HTML or Markdown, identically across locales.
- You MUST match the tense, tone and terminology already used in that locale's file. The English is plain and calm, and the translations are too.
- You MUST NOT leave a value empty. An empty string is a real translation as far as Laravel is concerned, and renders as nothing at all. Leave the key out instead, which the command does for you.
- Logs read as something somebody did, and each language has its own way of saying it: simple past in English ("Created the account"), passé composé in French ("A créé le compte"), Perfekt in German ("Hat das Konto erstellt").
