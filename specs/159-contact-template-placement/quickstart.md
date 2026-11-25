# Quickstart: Contact Template Placement

## Automated

```bash
php artisan test --compact tests/Unit/ViewModels/Vaults/ContactViewModelTest.php
```

```bash
php artisan test --compact tests/Feature/Controllers/App/Vaults/ContactControllerTest.php
```

Expected: both pass; the new view model test proves the last fact names and links the template.

## By hand

1. `composer run dev`, sign in, open any contact page.
2. The summary row under the name ends with the layers icon and "Template <name>".
3. Tab to it: one focus ring around label and name; a screen reader reads "Template <name>", link.
4. Activate it: the template builder of that template opens.
5. Rename the template in Settings, reopen the contact: the new name shows.
6. Narrow the window to phone width: the fact wraps with the row, nothing overflows.
7. Open the same contact through a private share link: no template fact.
