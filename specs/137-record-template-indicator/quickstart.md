# Quickstart: Template named on the edit screen of a record

## Automated

```bash
php artisan test --compact tests/Unit/ViewModels/Vaults/ContactEditViewModelTest.php
php artisan test --compact --filter=the_edit_screen tests/Feature/Controllers/App/Vaults/ContactControllerTest.php
```

Expected: both pass. The view model test proves the template is returned and the link points at its
builder, and that no template is named once it is gone. The feature test proves the screen shows
"Current template", the template name and the link.

## By hand

1. Open a vault, then a contact, then Edit.
2. The header shows "Current template" and the template's name to the right of the title.
3. Activate the name: the template builder in account settings opens.
4. Narrow the window to 320px: the block sits under the title, nothing scrolls sideways.
5. Tab to the link: the focus ring is visible.
