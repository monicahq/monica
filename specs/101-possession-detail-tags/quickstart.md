# Quickstart: Tags on the Possession Page

## Automated

```bash
php artisan test --compact tests/Unit/Actions/AttachPossessionTagTest.php tests/Unit/Actions/DetachPossessionTagTest.php
php artisan test --compact tests/Unit/Models/AccountTest.php --filter=next_possession_tag_colour
php artisan test --compact tests/Unit/ViewModels/Vaults/PossessionViewModelTest.php
php artisan test --compact tests/Feature/Controllers/App/Vaults/PossessionTagAttachmentControllerTest.php
php artisan test --compact tests/Feature/RouteProtectionTest.php
php artisan monica:localize --check
vendor/bin/phpstan analyse --no-progress
vendor/bin/pint --dirty --format agent
php artisan test --compact
```

What each layer proves (testing rules, one layer per rule):

| Test | Proves |
| --- | --- |
| Attach / Detach actions | link written or removed, no duplicate, no-op logs nothing, journal entry, another account refused |
| `Account::nextPossessionTagColour` | Blue with no tag, the colour after the latest, wraps after Slate |
| View model | chips sorted with colour; options sorted, ticked, "Unused" and other counts excluding this possession; next colour named |
| Controller | each route answers and flashes; `name` validated; wrong vault 404 |

## By hand (`composer run dev`)

1. Open a possession carrying two tags. Chips show in colour under the name.
2. "Add a tag": the panel lists every tag sorted by name, the carried ones checked.
3. Tick a tag: chip appears, panel closes, foot count changes, toast shows.
4. Type a new name: "Create “…” and add it" names the colour. Choose it with Enter.
5. Untick a tag: the confirmation opens; "Keep it" changes nothing, "Remove" takes it off.
6. Press a chip's ×: same confirmation. Escape returns focus to the ×.
7. Keyboard only: Tab to "Add a tag", Enter, type, Down, Enter, Escape. Then the same with VoiceOver
   or Orca: the option and its checked state are announced.
8. The Transactions section of the same possession: the same header works there.
9. Switch to French: counts read correctly at 0, 1 and 2.
