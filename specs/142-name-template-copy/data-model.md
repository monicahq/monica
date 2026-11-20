# Data Model: Name a template copy before it is made

No table, column or index changes.

## Template (existing)

| Attribute | Rule that applies to the copy form |
| --- | --- |
| `name` | Required, string, at most 255 characters. Prefilled with `Copy of :name`. Stored as typed, `name_translation_key` null |
| `slug` | Nullable, string, at most `Template::SLUG_LENGTH` (64), matches `^[a-z0-9]+(?:-[a-z0-9]+)*$`, unique per `account_id` including soft deleted rows. Empty means derived from the name with a number added when taken. Fixed once the template exists |

Everything else on the copy (description, locale, structure, a draft version numbered 1, position last) is set by `DuplicateTemplate` exactly as today.

## TemplateCopyViewModel (new, presentation only)

| Method | Returns |
| --- | --- |
| `template()` | The original template, for the page title and the back link |
| `values()` | `array{name: string, slug: string, slugTouched: bool}`: `old()` when a submission was refused, otherwise the suggested name and the free slug derived from it; `slugTouched` true only when `old('slug')` is present and not empty |
| `action()` | `route('settings.templateCopy.create', $template)` |
| `cancelUrl()` | `route('settings.templates.index')` |
| `user()` | The signed in user, for `x-settings.page` |

## State

```text
List of templates --Copy--> Copy form --confirm, valid--> copy created --> builder of the copy
                                 |  ^
                                 |  +-- refused (taken, invalid, empty name): form again, values kept
                                 +-- Back --> list of templates, nothing created
```
