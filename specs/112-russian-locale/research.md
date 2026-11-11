# Research: Russian locale

## Locale code and the framework strings

**Decision**: `ru_RU`, aliased from laravel-lang's `ru` in `config/localization.php`, then
`php artisan lang:add ru_RU` for `lang/ru_RU/` (validation, auth, passwords, pagination, actions,
HTTP statuses).

**Rationale**: the add-locale skill requires ISO 15897. `vendor/laravel-lang/lang/locales/ru` and
its five sibling packages ship Russian, and Carbon ships `ru_RU.php`, so month names and relative
times render in Russian with no extra work. Bare `ru` is refused by the skill's naming rule.

## Config entry

**Decision**: label `Русский`, region `Россия`, `date_format` `d.m.Y`, `number_format`
`space_comma`, flag `linear-gradient(to bottom, #fff 0 33%, #0039a6 33% 66%, #d52b1e 66%)`. No
`currency`, no `answers_bare_language`, no `typography`.

**Rationale**: `CurrencyEnum` quotes no rouble, and `BillingViewModel::currency()` falls back to the
catalogue when a locale suggests none, as `pl_PL` already does. `LocaleResolution::shippedForLanguageOf()`
answers a bare `ru` with the only Russian locale, so the bare language flag is redundant.
No typography: nobody who reads Russian declares it (`translations.md`); values still use `«…»`.

## Plural wordings

**Decision**: three, `WORDINGS_PER_LOCALE` gains `'ru_RU' => 3`, and no Russian wording is pinned
with `{1}`: the 17 pinned English phrases get three `:count` wordings (`Каждый :count месяц`).

**Rationale**: `getPluralIndex('ru_RU', n)` is 0 at 1 and 21, 1 at 2 and 22, 2 at 0, 5 and 11. A
pinned first segment is that index 0, so `{1}Каждый месяц|…` printed «Каждый месяц» at 21, checked.
The check demands exactly three segments, so moving the pin cannot help. The four `[0,*]` phrases
read the same at every number (`Показать ещё :count`) and keep one wording.

## Sorting, headings and search

**Decision**: no code change. Two tests in `LocaleCollationTest` pin the Russian behaviour.

**Rationale**: run against the shipped helper with `ru_RU`:

| Input | Result |
| --- | --- |
| Sort Яна, Жанна, Ёлкин, Евгений, Анна, Иван, Йорк, Smith | Анна, Евгений, Ёлкин, Жанна, Иван, Йорк, Яна, Smith |
| `foldForSearch('Ёлкин')` / `headingLetter('Ёлкин')` | `елкин` / `Е` |
| `foldForSearch('Йога')` / `headingLetter('Йога')` | `йога` / `Й` |

`foldCharacter()` strips a mark only when the locale's primary collator calls the two letters equal.
ICU does for Ё and Е and does not for Й and И, which is what a Russian reader expects. The concern
raised after `/speckit-specify` is therefore unfounded. Slugs keep Cyrillic letters and do not read
the locale (`SlugBuilder::stem()`), and stored keys rebuild through `RebuildOrderingKeys`.

## Glossary column

**Decision**:

| English | Russian | English | Russian |
| --- | --- | --- | --- |
| template | шаблон | plan | тариф |
| account | аккаунт | value list | список значений |
| section | раздел | marketplace | маркетплейс |
| vault | хранилище | note | заметка |
| field | поле | share | общий доступ |
| reminder | напоминание | API key | API-ключ |
| contact | контакт | group | группа |
| passkey | ключ доступа | life event | событие |
| relationship | связь | | |

**Rationale**: the word a Russian interface already uses for each (`аккаунт` as Google and Yandex
write it, `ключ доступа` as Apple and Google name passkeys). `связь` over `отношение`, which reads as
an attitude. `событие` over `активность`, since a life event is something that happened on a date.

**Alternatives considered**: `учётная запись` (heavier, administrative register), `сейф` for vault
(reads as a physical safe), `каталог` for marketplace (collides with ordinary lists).

## Register and how the file is produced

**Decision**: *вы*, lower case, in the neutral register of the English source. Logs use the
impersonal short passive (`Создан шаблон :name`), never a gendered past tense. The values are
written in batches, each checked against the glossary, then `monica:localize --check` closes it.

**Rationale**: same practice as 025. Capitalised *Вы* is letter etiquette, not interface copy.
