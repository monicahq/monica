# Research: Documentation portal header

## R1. Styles: the two sites share one palette

Both repositories alias the same base custom properties (`--ink`, `--body`, `--hairline`, `--card`,
`--hover`, `--canvas`, `--action-primary`, `--pop-shadow`, `--focus-ring`), with identical values in
both themes, and `logo.svg` is byte for byte the marketing `monica-panda-mark.svg`.

| Marketing utility | Application utility |
| --- | --- |
| `text-text`, `text-text-secondary`, `text-text-muted`, `text-text-soft` | `text-ink`, `text-body`, `text-muted`, `text-ink-soft` |
| `border-border`, `border-border-subtle`, `border-border-strong` | `border-hairline`, `border-hairline-soft`, `border-hairline-strong` |
| `bg-surface-subtle`, `bg-primary`, `text-on-primary` | `bg-card`, `bg-primary`, `text-on-primary` |
| `text-caption` (11px / 1.4) | `text-2xs` (same values) |
| `ease-standard`, `duration-150` | `ease-out` (same curve), `duration-(--duration-fast)` (150ms, 0 under reduced motion) |
| `mn-btn mn-btn-primary` | `x-button` primary (same height, padding, size, weight) |

Missing in the application, added to `@theme` in `resources/css/app.css`: `--color-identity-1`, `-2`,
`-3`, `-5`, `-6`, `-7` (the marketing hex values, the same in both themes), `--text-lede-sm`
(16px / 1.55) and `--ease-pop`. Tailwind is 4.3 here, so `starting:` works.

`mn-btn-secondary` has no twin: `x-button.secondary` adds `shadow-control` and a stronger edge. Sign
in is written out in the header with the marketing look rather than bending the shared component.

## R2. Links

| Item | Destination |
| --- | --- |
| Features menu, Pricing, Blog, Sales, Handbook, Contribute, Translations, Roadmap, Support | Marketing site, localised slug |
| Documentation, Getting started, API reference | `docs.index`, `docs.portal.index` (`en`, as marketing does), `docs.api.index` |
| Self-hosting, Start here card | GitHub repository: marketing has a `#` placeholder and no install guide exists anywhere |
| GitHub, Source code, Report a bug, Changelog | The absolute URLs marketing uses |
| Sign in, Get started, dashboard | `auth.login.new`, `auth.register.new`, `home.index` |

The slugs differ per language (`/fr/tarifs/`, `/de/funktionen/menschen/`). They live in one helper,
`App\Helpers\MarketingSite`, holding the table for the 24 pages the header names and building
`{base}/{locale}/{slug}/`. Base URL: `config('monica.marketing_url')`, default
`https://www.monicahq.com` (the marketing `baseUrl`). Handbook articles keep their English slug in
every language (`/fr/manuel/principles/`). Rejected: reading the marketing `config.php` at runtime
(another repository).

## R3. Language

The header follows the application locale, like the rest of the portal chrome. Locale mapping:
`fr_FR` fr, `de_DE` de, `es_ES` es, `pt_BR` and `pt_PT` pt (the marketing Portuguese is Brazilian),
`nl_NL` nl, `zh_CN` zh, `ru_RU` ru, everything else en.

## R4. Copy

72 phrases, 18 already in `lang/en.json`. For 25 locale and phrase pairs the application already
holds a different translation (`Docs` is `Documentation` in fr_FR, `Vaults` is `Cámaras` in es_ES).
A JSON locale file holds one translation per phrase and the glossary in `.claude/rules/translations.md`
fixes product nouns, so the existing translation stays. New phrases take the marketing wording in
fr_FR, de_DE, es_ES, pt_BR, nl_NL, zh_CN and ru_RU; the 24 other locales are translated by Sonnet
subagents (translations skill). The star label uses `:figure` rather than `:count`, since the value is
`24k+` and `:count` would demand `trans_choice()` with a number.

## R5. Behaviour of the menus

Native `<details>`, as marketing does, so they open without scripts. The document listeners of the
marketing `app.js` (Escape, click elsewhere, mouse rest with a 250ms close, one menu open at a time,
closed before a Turbo snapshot) become one Alpine component, `headerMenu`, in
`resources/js/header-menu.js`, bound on each `<details>`. Focus uses `focus-ring` on every control;
marketing draws none, and WCAG 2.2 AA requires it.

## R6. Theme switch

The marketing switch is `size-9`, shows the moon in light and announces "Switch to dark mode" or
"Switch to light mode". `x-theme-toggle` is `size-8`, shows the sun and has one label. The header
writes the marketing button out, calling `$store.theme.toggle()`; `x-theme-toggle` is untouched.
