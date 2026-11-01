# Quickstart: validating groups

## Prerequisites

The worktree set up as usual (composer install, `public/build`, `passport:keys`), then:

```bash
php artisan migrate --no-interaction
php artisan monica:switch-flag groups on --every-account
```

## Automated

```bash
php artisan test --compact tests/Unit/Actions --filter=Group
php artisan test --compact tests/Feature/Controllers --filter=Group
php artisan test --compact tests/Feature/Dav
php artisan test --compact tests/Unit/Actions/MergeContactsTest.php tests/Unit/Actions/UndoContactMergeTest.php
php artisan monica:localize --check
vendor/bin/phpstan analyse --no-progress
```

## By hand

| # | Do | Expect |
| --- | --- | --- |
| 1 | Create "Halpert household", add Jim, Pam and Me | Group page lists three current members (US1) |
| 2 | Add a contact of another vault through the API | 404; a duplicate current member gives 422 |
| 3 | Open Dwight's page, in two groups and one left | Three groups, current first, with roles (US2) |
| 4 | End Ryan's Scranton team membership in 2007, add him again in 2009 | Two memberships, one current; both on his timeline (US3) |
| 5 | Set a leave date before the join date | Refused on the form (US3) |
| 6 | Sync the vault with DAVx5 set to "Groups are separate vCards" | One group card naming the three members (US4) |
| 7 | On the phone, create a group of two contacts, remove a member from another | Group created with no new contact; membership ended today (US4) |
| 8 | Delete the group card on the phone, then restore the group in the app | Group comes back with its memberships |
| 9 | Merge two Jims who are both current members of the household | Preview names the fold; one membership remains; undo restores both |
| 10 | Export the account, import it into a fresh account | Same groups, members and periods (SC-003) |
| 11 | Switch the flag off for the account | Group screens 404; no group card in the address book |

Repeat 6 and 7 with an iPhone (Contacts app) for the `X-ADDRESSBOOKSERVER` form; neither device path
has been tried before this feature.
