# Contract: `x-docs.header` and `MarketingSite`

## `<x-docs.header />`

No props. Reads the signed in user, `config('monica.registration_enabled')` and the application locale.

| Element | Contract |
| --- | --- |
| `<header>` | `print:hidden` |
| `<nav>` | `aria-label` "Main" |
| Features, Docs | `<details>` with `x-data="headerMenu"`, a `<summary>` button; the Docs `<summary>` carries `aria-current="page"` |
| Pricing, Blog, Sales | `<a>` to `MarketingSite::url()`, never `aria-current` |
| GitHub | `<a>` whose `aria-label` contains the visible figure |
| Theme switch | `<button>` calling `$store.theme.toggle()`, one `sr-only` label per state |
| Visitor | "Log in" to `auth.login.new`; "Sign up" to `auth.register.new` only while registration is open |
| Signed in | One primary link to `home.index`, labelled "Dashboard"; no Sign in, no Get started |

## `App\Helpers\MarketingSite`

| Method | Returns |
| --- | --- |
| `locale(): string` | Marketing language for the application locale (research R3), `en` by default |
| `url(string $page, ?string $locale = null): string` | `{marketing_url}/{locale}/{slug}/`; `home` gives `{marketing_url}/{locale}/` |
| `handbookArticle(string $slug, ?string $locale = null): string` | `{marketing_url}/{locale}/{handbook slug}/{slug}/` |

An unknown page throws, as the marketing `localePath()` does, so a typo fails the test suite rather
than drawing a dead link.
