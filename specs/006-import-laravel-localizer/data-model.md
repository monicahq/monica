# Phase 1: Data Model

**Feature**: Locale resolution modelled on laravel-localizer | **Date**: 2026-08-31

No table is created, altered or dropped, and this feature adds no migration. What follows is the shape
of the things it reads and writes, because five of them decide one value between them and the ordering
is the design.

## Shipped locale

The unit everything is decided in. Defined once, in `config/monica.php` under `locales`, keyed by the
locale code, which is also the name of the file in `lang/`.

| Field | Type | Notes |
| --- | --- | --- |
| key | string, 2 or 5 characters | `en`, `fr_FR`, `de_DE`, `es_ES`, `pt_PT`, `pt_BR`, `nl_NL`, `it_IT`, `tr_TR`. Underscore between language and region. The only values any source may resolve to. |
| label | string | Existing. The language's name in itself. |
| region | string | Existing. |
| date_format, number_format | string | Existing. |
| flag | string | Existing. A CSS background value. |
| typography | array, optional | Existing. Read by `monica:localize`. |
| answers_bare_language | bool, optional | **New.** Set on the one locale answering a device that names its language and no region, where the product ships more than one for that language. Set on `pt_BR`, absent everywhere else. |

Derived, not stored: the language of a key is everything before the first underscore, the region is
everything after it. `en` has no region.

**Validation**: `Rule::in(array_keys(config('monica.locales')))` already guards the two routes that
accept a locale from a form. Unchanged.

**Invariant**: adding a tenth locale means one new entry here and one new `lang/*.json`. Nothing in
this feature holds a second list to keep in step, which is FR-007. The resolver reads the config array
directly, so there is no copy to drift.

## The five sources

Ordered as FR-002 orders them for the web. Each yields a shipped locale key or nothing.

| # | Source | Lives in | Lifetime | Written by |
| --- | --- | --- | --- | --- |
| 1 | Session choice | session, key `locale` | the session, 120 minutes idle | the language switcher and the preferences screen |
| 2 | Account preference | `users.locale`, `string(5)` nullable | until changed, follows the user across devices | the preferences screen, through `UpdatePreferences` |
| 3 | Device choice | cookie `locale`, one year | one year, that browser only | the language switcher and the preferences screen |
| 4 | Announced language | `Accept-Language` request header | the request | the reader's browser or the API caller |
| 5 | Application fallback | `config('app.locale')` | always present | deployment |

Source 5 always yields a value, so resolution cannot fail. Sources 1 and 3 are two records of one act,
written together in one call so they cannot disagree, which is FR-016.

### Reading order, by surface

The same five sources, ordered differently, because an announced language means something different on
each surface. On the web it is the browser's ambient setting, which no signed in user asked for. On the
API it is an explicit act by the caller on that request.

| Surface | Order | Recorded |
| --- | --- | --- |
| web | 1 session, 2 account, 3 device, 4 announced, 5 fallback | session, device, active locale |
| api | 4 announced, 2 account, 5 fallback | active locale only |

Sources 1 and 3 do not appear in the API order because an API request carries neither, and are absent
from what it records because FR-013 forbids it.

## Announced language, and how it becomes a shipped locale

Not stored. Parsed per request from `Accept-Language` into an ordered list, each entry already sorted
by the quality weight the browser sent and already written with an underscore between language and
region. What the framework returns is well formed but not necessarily shipped, so the mapping below is
applied, which is FR-004 and FR-005.

| Announced | Framework returns | Resolves to | By |
| --- | --- | --- | --- |
| `fr-FR` | `fr_FR` | `fr_FR` | already a shipped key |
| `fr` | `fr` | `fr_FR` | the one locale shipped for French |
| `fr-CH` | `fr_CH` | `fr_FR` | region not shipped, language is, so the language wins |
| `pt-BR` | `pt_BR` | `pt_BR` | already a shipped key |
| `pt-PT` | `pt_PT` | `pt_PT` | already a shipped key |
| `pt` | `pt` | `pt_BR` | the locale declaring `answers_bare_language` |
| `en-US` | `en_US` | `en` | the one locale shipped for English |
| `sv`, `sv-SE` | `sv`, `sv_SE` | nothing | not shipped, skip to the next announced language |
| `FR-fr` | `fr_FR` | `fr_FR` | case is not meaning, and the framework canonicalises it |
| malformed | returned as is | nothing | fails the shipped test like any other unknown value |

**Invariant**: the result is always a key of `config('monica.locales')` or nothing. Nothing else can
come out, which is FR-006, and it is what makes a forged header harmless.

## State transitions

One, and it is the only thing this feature writes.

```text
reader picks a language from the switcher, or saves the preferences screen
        │
        ├─ session `locale`  := chosen key
        ├─ cookie  `locale`  := chosen key, one year
        └─ active locale     := chosen key, so the redirect that follows is already in it
```

The account preference is written only by the preferences screen, and only through the existing
`UpdatePreferences` action, unchanged by this feature. The switcher never touches it, which is FR-011.

Nothing else writes anything. A reader who never chooses a language has nothing stored for them
anywhere, and an API request stores nothing at all.
