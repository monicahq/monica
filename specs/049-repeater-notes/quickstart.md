# Quickstart: Notes in a repeater

## Automated

```bash
php artisan test --compact tests/Unit/ViewModels/Vaults/ContactViewModelTest.php
php artisan test --compact tests/Feature/Controllers/App/Vaults/ContactSectionControllerTest.php
php artisan monica:localize --check
vendor/bin/phpstan analyse --no-progress
vendor/bin/pint --dirty --format agent
```

## By hand

On a contact built from the default template, open the page holding the **Notes** section.

| Setup | Expect |
| --- | --- |
| One short note | Text across the panel, no label column, "Added" and the date under it (frame 1a) |
| A note of five paragraphs | Two shown, **Read more**; pressing it shows all and reads **Show less** (1b) |
| No note | "No note yet" and **Add a note**, which opens the section editor with a blank row (1c) |
| Scripts off | The whole long note is readable and no button shows |
| Width 390px | Label rows stack, Read more spans the width, controls are 44px tall (1f) |
| Keyboard only | Tab reaches Read more and Add a note, each with a visible ring |
| A section with other fields | They keep their label and value rows around the note block (1d) |
