# Quickstart: Empty list of an invented type

## Automated

```bash
php artisan test --compact tests/Unit/ViewModels/Vaults/EntitiesViewModelTest.php tests/Feature/Controllers/App/Vaults/EntityControllerTest.php
php artisan monica:localize --check
composer test:parallel
```

## By hand

1. In account settings, create a type Animals (singular Animal), icon paw, any colour, no description.
2. Use it in a vault (set a default template for it), open Animals in the vault sidebar.
3. Expect three fading rows with the paw on a tint of the colour, "Your Animals will line up here",
   the generic sentence, and the secondary button "Add your first Animal" opening the add form.
4. Give the type a description. Reload: the description replaces the generic sentence.
5. Give the template a required repeater holding a required avatar. Reload: the reason shows; both add
   buttons are dimmed, not activable, and described by the reason. The rows are not announced.
6. Add one animal: the list shows it and no placeholder.
7. Repeat 3 in the dark theme and at 320px wide: no horizontal scroll, the tint stays legible.
