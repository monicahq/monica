# Quickstart: validating More Authorization Policies

## Prerequisites

`composer install`, `php artisan migrate`, `bun run build` as usual. No seeder or fixture is specific to this feature. [028](../028-authorization-policies/spec.md) is merged, so `app/Policies`, `User::inspect()`, `User::authorize()` and `PolicyCoverageTest` already exist.

## What to exercise by hand

Each row is one rule that had two homes. Change it in the policy alone and both columns must follow, which is SC-001.

| Ability | Drive the screen to | Then attempt it anyway |
| --- | --- | --- |
| `delete` on `RelationshipType` | a type relationships are written with | deleting it from its form |
| `delete` on `ValueList` | a list the product supplied | deleting it |
| `addItem` on `ValueList` | a list that takes no choices of its own | adding one |
| `delete` on `ValueListItem` | a value records point at | deleting it |
| `delete` on `EntityType` | a type holding records | deleting it |
| `share` on `Template` | a template never published | its share address, directly |
| `delete` on `TemplateVersionPage` | a version holding one page | removing that page |
| `removePassword` on `User` | an account whose only way in is its password | removing the password |
| `disconnectProvider` on `User` | an account signed in only through a provider | disconnecting it |
| `absorb` on `Contact` | the contact representing you, on the merge screen | absorbing them |
| `decide` on `MarketplaceListingVersion` | a version nobody is waiting on | approving it |

## What to look for on each screen

- The control is **present**, not missing. This is the change, and SC-004 counts it.
- It cannot be activated, by mouse or by keyboard, and a screen reader announces it as unavailable.
- Its reason is reachable from it. Where the row already prints that reason, the control points at that text; where the row prints none, a reason was added.
- Focus is still visible on it, and the screen still reads at 200% zoom.

## Automated checks

```bash
php artisan test --compact tests/Unit/Policies
php artisan test --compact tests/Feature/Controllers/App/Settings/Account
php artisan test --compact tests/Feature/Controllers/Api
php artisan test --compact --filter=Destroy
php artisan monica:localize --check
vendor/bin/pint --dirty --format agent
vendor/bin/phpstan analyse --memory-limit=2G --no-progress
```

`tests/Unit/Policies` must grow by eight files. `PolicyCoverageTest` fails the suite if any new method lacks both a permitted and a refused case, which is how FR-009 of 028 is enforced here without restating it.

`monica:localize --check` must pass with the reasons added for the rows that carried none, translated in all 31 locales.

## What must not change

- The two API endpoints that refuse these capabilities still answer 422 with the same field and the same wording.
- The three template addresses still answer as if the template were not there, not with an explanation.
- Every compartmentalisation test passes untouched: this feature adds no way to reach anything.
