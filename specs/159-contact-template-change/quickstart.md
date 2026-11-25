# Quickstart: validate the change of template

## Prerequisites

A local account seeded with `ExampleAccountSeeder`, and a second contact template: in Settings, Templates,
duplicate Basic contact, remove the field holding the birthday and one field of Information, add a Work
page with a text field, and publish.

## Scenarios

1. **Screen.** Open a contact holding a birthday reminder, choose Edit contact, pick the new template and
   Review the change. The block lists the two removed fields and the birthday reminder as stopping. Confirm:
   the contact page shows the Work page, Unused answers lists exactly those two answers, the reminder is
   stopped with its reason.
2. **Same template.** Pick the current template and confirm: nothing changes and the version history has
   no new entry.
3. **Go back.** In the version history, revert to the Basic contact entry: both answers are back in their
   fields and the reminder runs again.
4. **API.** With a key, `GET .../contacts/{contact}/template-preview?template={id}` returns the lists of
   [contracts/api.md](contracts/api.md) and changes nothing; `PUT .../contacts/{contact}/template` moves
   the contact. A turned off template answers 422 with the same sentence as the screen.
5. **MCP.** Call `preview-contact-template-change`, then `change-contact-template`: the answers match the
   API's.
6. **Help.** The question mark beside the template choice opens the bubble and its link opens
   `contacts.change-template`.

## Commands

```bash
php artisan test --compact tests/Unit/Actions/MoveRecordToStructureVersionTest.php tests/Unit/Actions/ChangeContactTemplateTest.php tests/Unit/Helpers
php artisan test --compact tests/Unit/ViewModels/Docs tests/Feature/Controllers/App/Docs tests/Unit/Actions/BuildOpenApiDocumentTest.php
php artisan monica:localize --check
composer test:parallel
```
