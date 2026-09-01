# Contract: API language negotiation

**Feature**: Locale resolution modelled on laravel-localizer | **Date**: 2026-08-31

The outward facing half. What an API caller may ask for, and what they get. Nothing here depends on how
resolution is built.

## Request

Any request to `api/*` may carry a standard `Accept-Language` header.

```http
GET /api/me HTTP/1.1
Authorization: Bearer <token>
Accept-Language: fr-FR,fr;q=0.9,en;q=0.8
```

The header is optional, and it is the only way a caller states a language. No query parameter, no body
field, no custom header.

## Resolution

The header is honoured before the preference stored against the token's user, so a caller that asks for
a language gets it whoever the token belongs to. This is the opposite of the web ordering,
deliberately, and both the specification and research R6 record why.

| Header | Token's user preference | Answered in |
| --- | --- | --- |
| `fr-FR` | `de_DE` | `fr_FR`, the header asked |
| `fr` | `de_DE` | `fr_FR`, language matching |
| `fr-CH` | `de_DE` | `fr_FR`, region not shipped, language is |
| absent | `de_DE` | `de_DE`, the stored preference |
| `sv-SE` | `de_DE` | `de_DE`, header names nothing shipped, falls through |
| `sv-SE,fr;q=0.8` | `de_DE` | `fr_FR`, first shipped language in the header wins |
| `fr-FR` | none, no token | `fr_FR` |
| absent | none, no token | `config('app.locale')` |
| `pt` | any | `pt_BR` |
| `pt-PT` | any | `pt_PT` |

Unauthenticated routes, which is `api/register` and `api/login`, resolve the same way with the second
step yielding nothing.

## Response

The resolved locale governs every translated string in the body, which today means validation messages
and the errors raised by the authentication routes, for example
`__('That two factor code is not valid.')` in `Api\Auth\LoginController` and
`__('You have to agree with the terms of use and the privacy policy to create an account.')` in
`Api\Auth\RegistrationController`.

**No response header advertises the chosen language.** Adding `Content-Language` would be a new promise
about every endpoint and is not in scope here.

**No cookie is set.** An API response carries no `Set-Cookie` for the locale, whatever the request asked
for, and nothing is written to a session. An API request is stateless in this respect, which is FR-013,
and a test asserts it directly rather than trusting the framework's middleware groups to keep it true.

## What this repairs

Commit `6fa1e09` took `set.locale` off `routes/api.php` without putting anything in its place, so on
`main` today an API request runs no locale middleware at all and is answered in whatever
`app()->getLocale()` happens to hold. In a long lived process that is whatever the previous request left
behind. This contract closes that.

## What does not change

Every existing endpoint keeps its path, verb, payload, status codes and JSON shape. This contract adds a
way to influence the language of text already in those responses, and takes nothing away. A caller
sending no `Accept-Language`, which is every caller today, gets their stored preference and then the
application fallback, which is what the API did before `6fa1e09`.
