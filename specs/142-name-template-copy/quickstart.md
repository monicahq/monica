# Quickstart: Name a template copy before it is made

## Automated checks

```bash
php artisan test --compact tests/Feature/Controllers/App/Settings/Account/Templates/TemplateCopyControllerTest.php
php artisan test --compact tests/Unit/ViewModels/Settings/Account/Templates/TemplateCopyViewModelTest.php
composer test:parallel
```

What the tests have to prove, per layer (`.claude/rules/testing.md`):

| Layer | Proves |
| --- | --- |
| `TemplateCopyViewModelTest` | The suggested name is "Copy of {name}"; the suggested slug skips a taken one, deleted templates included (`copy-of-contact-2`); refused input wins over the suggestion; `slugTouched` is false for a suggestion and true for typed input |
| `TemplateCopyControllerTest` | The form renders with the copy the reader recognises; a valid post creates the copy with the given name and slug and redirects to its builder; an empty slug is derived; a taken slug and an invalid slug are refused on `slug`; an empty name is refused on `name`; nothing is created on refusal |

The copy itself (content, draft version, journal) stays proven by the existing `DuplicateTemplate` action test. The API and the MCP tool tests stay unchanged and must still pass.

## By hand

1. `composer run dev`, sign in, open Settings, Templates.
2. On **Basic contact**, choose **Copy**. The form shows `Copy of Basic contact` and a slug such as `copy-of-basic-contact` (or `-2` if one exists). Nothing new is listed yet.
3. Leave both, confirm. The builder of the copy opens and its URL carries the suggested slug.
4. Copy again, type the name `Work contact`. The slug becomes `work-contact` as you type. Confirm. The URL carries `work-contact`.
5. Copy again, type the slug `work-contact`. Confirm. Refused with "Another template in this account already uses that slug.", values kept.
6. Type `Work Contact`. Refused with the format message.
7. Choose the way back. Nothing is created.
8. Keyboard only: Tab reaches both fields, the confirm button and the way back; errors are read with their field.
