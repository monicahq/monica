# Quickstart: validating the feature

## Automated

```bash
php artisan test --compact \
  tests/Unit/Actions/DemoteFullContactTest.php \
  tests/Unit/Actions/PromotePartialContactTest.php \
  tests/Unit/Actions/MergeContactsTest.php \
  tests/Unit/Policies/ContactPolicyTest.php \
  tests/Unit/ViewModels/Vaults/ContactEditViewModelTest.php \
  tests/Feature/Controllers/App/Vaults/ContactPartialControllerTest.php \
  tests/Feature/Controllers/Api/ContactPartialControllerTest.php
composer test:parallel
php artisan monica:localize --check
vendor/bin/phpstan analyse --no-progress
```

Expected: everything passes. `PolicyCoverageTest` fails if the `demote` ability lacks its three tests.
`McpCatalogueTest` fails if the new tool names a route that does not exist.

## By hand

The example account (`monica.geller@centralperk.example`) holds no partial contact, so the flow
starts from a full one.

1. Open a full contact's edit page. The panel "Keep :name as a partial contact" sits above
   "Delete contact", not in red.
2. Press "Make a partial contact". The dialog names the list, the phones, the birthday calendar, and
   that everything stays. For a contact without a relationship, it adds where they will be found.
3. Confirm. The contact's page shows the partial band and the "Partial contact" badge. The list of
   people no longer shows them; "Names only" does; search still finds them. The free plan meter is one
   lower.
4. Press "Make a full contact" on the band and confirm. Everything is back, the meter one higher.
5. Open the edit page of the account holder's own contact. The panel is there, its button refused,
   its reason read out by a screen reader.
6. On a free account at 30 of 30, make two contacts partial, then merge one into the other, then try
   to make the result full: the merge succeeds only while the account is below the ceiling.
7. With a phone synchronised over CardDAV, make a contact partial and sync: the card leaves the phone.
   Make it full again and sync: the card returns.

The `accessibility-reviewer` and `rules-reviewer` agents review the change before the pull request.
