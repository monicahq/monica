---
name: translations
description: Update Laravel PHP translation files by fixing missing or empty translations in lang/**/*.php. Use when UI copy changes, new keys are added, or locale files are out of sync. Trigger whenever translation keys, lang files, i18n, or __() / @lang() strings are mentioned.
---

# Translations Updater

This skill keeps Laravel translation files consistent and complete.

## Key conventions

This project uses **translation keys** in JSON files.

## When to use this Skill

You MUST use this skill when:

- New UI text or translation keys were introduced.
- Modules, pages, or components added new strings.
- Translation files contain empty or missing values.
- Locale files are out of sync with the codebase.
- A supported language is added or removed.

## Voice and tense per locale for logs

When translating logs, you MUST match the convention for each language:

| Locale | Convention | Example |
|---|---|---|
| `en` | Simple past, active | "Created an account" |
| `fr_FR` | Passé composé, actif | "A créé un compte" |
| `de` | Perfekt, active (Hat + Partizip) | "Hat ein Konto erstellt" |

## Instructions

### Step 1: Regenerate locale files

1. You MUST run the locale generation command:
   ```bash
   composer kollek:locale
   ```
2. You MUST confirm the command completes successfully before making any edits.

### Step 2: Identify missing or empty translations

- For each new entry in the `en.json` file, you MUST check all the other locale files of the project and fix any missing or empty translation.
- You MUST NOT remove keys unless they are confirmed unused.
- You MUST replace empty or invalid values with an appropriate human-readable translation.
- You MUST preserve placeholders exactly, and you MUST NOT alter tokens like `:name`, `:count`, `:seconds`, `{value}`.
- You MUST preserve embedded HTML or Markdown: the structure MUST be identical across locales.
- You MUST keep translations consistent:
   - Same tense and tone as the surrounding keys.
   - Same terminology used elsewhere in the locale file.
   - No informal register, unless the file already uses it.

### Step 3: Quality checks

1. No empty string, null value, or `TODO` placeholder MUST remain.
2. When a translation is uncertain, you SHOULD prefer literal and conservative wording.
3. You MUST maintain the existing key order, unless the project explicitly requires sorting.
4. You MUST run `bash scripts/check-translations.sh` after translation changes. It checks all PHP short-key language files and fails on empty strings.
