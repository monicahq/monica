# Quickstart: Edit relationship form

## Automated

```bash
php artisan test --compact tests/Unit/Actions/UpdateRelationshipTest.php
php artisan test --compact tests/Unit/ViewModels/Vaults/RelationshipEditViewModelTest.php
php artisan test --compact tests/Feature/Controllers/App/Vaults/ContactRelationshipControllerTest.php
php artisan monica:localize --check
vendor/bin/phpstan analyse --no-progress
```

## By hand, at `vaults/personal/contacts/chandler-bing/relationships/{id}/edit`

| Set up | Do | Expect |
| --- | --- | --- |
| Chandler parent of Thierry, both with records | Pick "Thierry Henry is Chandler Bing’s parent.", save | Both tabs read the other way; the caption read "Parent" before saving |
| Same | Tick "This relationship has ended", save with no date | Ended, no date on the tab |
| Same, ended | Type a date, untick, save | Still going, no end date |
| Thierry partial, named | Change the last name, save | New name on every link to Thierry; one name change in the journal |
| Thierry partial, nameless | Keep "I do not know their name", save | Still nameless; nothing in the journal about the name |
| Thierry partial, nameless | "I know their name now", empty first name, save | Refused on First name, the radio still on "known" |
| A relationship between two other people | Open it through Chandler’s URL | Not found |

Keyboard only: tab through the select, the radios, the fields and the checkbox in reading order. A
screen reader announces the radio group by the caption.
