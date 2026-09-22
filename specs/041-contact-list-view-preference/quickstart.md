# Quickstart: proving the contact list view preference works

Phase 1. How to run it and what to look for. The addresses are in
[contracts/web-addresses.md](./contracts/web-addresses.md), the endpoint in
[contracts/api.md](./contracts/api.md).

## Before anything

The users migration is edited in place rather than added to, so an existing database does not pick the
columns up:

```bash
php artisan migrate:fresh --seed
```

This also regenerates contact slugs against the widened reserved list, which is what clears a contact
that was slugged `all`.

## The suite

```bash
php artisan test --compact --filter=ContactControllerTest
php artisan test --compact --filter=ContactsViewModelTest
php artisan test --compact --filter=UpdateContactListViewTest
php artisan test --compact --filter=MeContactListViewControllerTest
php artisan test --compact --filter=SortDirectionEnumTest
php artisan test --compact --filter=ReservedAddressWordsTest
```

`Contact::scopeSortedBy()` changes signature and `ContactsViewModel` changes constructor, so the whole
suite runs before the branch is finished, not only these six:

```bash
php artisan test --compact
vendor/bin/pint --dirty --format agent
vendor/bin/phpstan analyse
php artisan monica:localize --check
```

`monica:localize` is expected to report nothing. No string is added: the four chip labels, "Sorted by"
and both direction wordings already exist. If it reports something, translate it in every configured
locale before the branch is done.

## What each test file proves

| File | What it holds |
| --- | --- |
| `tests/Unit/Enums/SortDirectionEnumTest.php` | The two cases, `values()`, and that `fromValueOrAscending()` answers ascending for null, for an empty string and for a spelling that is not a case. |
| `tests/Unit/Actions/UpdateContactListViewTest.php` | It writes the three columns. It writes nothing when all three already match. It dispatches no job. It returns the user. |
| `tests/Unit/ViewModels/Vaults/ContactsViewModelTest.php` | Every address it builds states a grouping, Everybody included. The existing cases move from string arguments to enum ones. |
| `tests/Feature/Controllers/App/Vaults/ContactControllerTest.php` | The six rows of the address table, the redirect, the recording, the prefetch guard and the fallbacks. Listed below. |
| `tests/Feature/Controllers/Api/MeContactListViewControllerTest.php` | The write, the three validation failures, the unauthenticated answer, and the attributes coming back on `GET me`. |
| `tests/Unit/Helpers/ReservedAddressWordsTest.php` | `all` is among the contact words, and the list holds every grouping value. |

`User` gains three docblock properties and no behaviour, so `tests/Unit/Models/UserTest.php` is not
touched. What a stored value that is no longer a case does is the enums' business, and
`SortDirectionEnumTest` and the controller's fallback case are where it is proven.

The controller test carries at least these, each named for what it proves:

- `it_shows_everybody_when_nothing_is_recorded`
- `it_redirects_the_bare_address_to_the_recorded_view`
- `it_shows_the_grouping_the_address_states_over_the_recorded_one`
- `it_records_the_grouping_the_address_states`
- `it_keeps_the_recorded_sort_when_the_address_states_only_a_grouping`
- `it_records_nothing_when_the_request_is_a_prefetch`
- `it_shows_the_people_written_down_as_a_first_name_only`
- `it_falls_back_to_everybody_when_the_recorded_grouping_is_not_a_grouping`
- `it_records_nothing_for_a_contact_list_in_another_account`

## By hand

Sign in, then walk it:

1. Open `/vaults/{vault}/contacts`. It answers `302` to
   `/vaults/{vault}/contacts/all?sort=name&direction=asc`, and the address bar shows the second one.
2. Click Favourites, then choose Last spoke and Newest first and submit. The address states all three.
3. Open `/vaults/{vault}/contacts` again. It lands on favourites, last spoke, newest first.
4. Open a contact, then use the sidebar to come back to People. Still favourites.
5. Click Names only. It shows the people written down as a first name only, rather than the page that
   could not be found before this change.
6. Open the relationships panel of a contact and hover its Names only link without clicking, for longer
   than a second. Go back to People. It is still on whatever you last chose, not Names only.
7. Sign in as a second user of a second account. Their People list opens on Everybody, sorted by Name.

Step 6 is the prefetch guard, and it is the one that fails silently if it is dropped.

## Through the API

```bash
curl -X PUT "$BASE/api/v1/me/contact-list-view" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"grouping":"archived","sort":"added","direction":"desc"}'

curl "$BASE/api/v1/me" -H "Authorization: Bearer $TOKEN"
```

The second call answers `contact_list_grouping: "archived"`. Opening
`/vaults/{vault}/contacts` in the browser then lands on the archived list, sorted by when people were
added, newest first, which is what proves the two surfaces read one value.

## Accessibility

The chips already carry `aria-current` on the one in force, and the change adds no control. Check the
row is reachable by keyboard alone, that focus is visible on each chip, and that the Names only chip
now leads somewhere. The screen is touched, so principle IX applies to it whole: keyboard, screen
reader, contrast, 200% zoom, and the three widths.
