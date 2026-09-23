# Quickstart: Relationships empty state

## Prerequisites

- A seeded instance (`php artisan migrate:fresh --seed`), signed in as `monica.geller@centralperk.example`.
- A contact with no relationship, such as Chandler Bing in the Personal vault.

## Validate

1. Open `/vaults/personal/contacts/chandler-bing/relationships`. Expect five rows in this order:
   Family, Romantic, Friendship, Professional, Other, each naming its types, each with Add.
2. Press Add on Family. Expect the add form with the first Family type chosen, and every other type
   still in the picker.
3. In settings, switch off Professional, rename Friendship to Friends, and move Romantic first.
   Reload the tab. Expect Romantic first, "Friends", and no Professional row.
4. Open `/vaults/personal/contacts/chandler-bing/relationships/new?type=999999`. Expect the form with
   nothing chosen and no error.
5. Add a relationship and save. Expect the filled tab, unchanged from today.
6. Switch to French. Expect the category names and the type names in French.

## Automated

```bash
php artisan test --compact tests/Unit/ViewModels/Vaults/RelationshipsBlockTest.php \
  tests/Unit/ViewModels/Vaults/RelationshipTypeChoicesTest.php \
  tests/Unit/ViewModels/Vaults/RelationshipFormViewModelTest.php \
  tests/Feature/Controllers/App/Vaults/ContactRelationshipControllerTest.php
php artisan monica:localize --check
vendor/bin/phpstan analyse --no-progress
```
