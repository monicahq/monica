# Quickstart: Tag contacts with categories

Prerequisites: a local instance, signed in, one vault on the default template, and a CardDAV client
(a phone, or Thunderbird) connected to it. The suite runs with `php artisan test --compact`.

## 1. Inline creation (US1)

1. Edit Roy's "Category" section, type "Art class", press Enter, then save. Roy shows "Art class", and
   Settings › Value lists › Contact categories lists it, with an entry in the journal.
2. Type "art CLASS " on Pam. The existing chip is checked, and no second item appears.
3. Stop "Art class" in settings, then type it on Jim and save. The field says it was stopped and where
   to offer it again, and Jim's other values are unchanged.
4. Type "Pottery" on Kevin and leave without saving. The list has no "Pottery".
5. Switch "Accepts new values" off for the list. The field no longer offers to add.
6. Repeat step 1 with the keyboard alone and with a screen reader: the field is reached by Tab, Enter
   adds, and the new chip is announced (FR-012).

## 2. Filter (US2)

1. Give "Colleague" to Michael and Dwight, "Friend" to Pam, and nothing to Toby. Narrow the vault to
   "Colleague" and "Friend": three people show, Toby does not, and each filter shows its count.
2. Switch to Favourites, then reload the page from its address. The filter is still applied.
3. SC-002: on a vault of 1,000 contacts, compare
   `curl -s -o /dev/null -w '%{time_total}'` on the list with and without three categories. The two
   times are within the same order.

## 3. vCard and CardDAV (US3)

1. On the phone, Pam's card shows "Family" and "Colleague".
2. Add "Book club" on the phone and synchronise. Pam's page shows it, and the list gained it.
3. Remove "Colleague" on the phone. Pam loses "Colleague" at the next synchronisation.
4. Move Toby to a template without a Category field, and give his card "Golf" from the phone. "Golf"
   is still on his exported card, and the list did not gain it.

## 4. Monica 4 import (US4)

1. Import a Monica 4 file where Oscar has the tags "accounting" and "senator". Oscar holds both
   categories, and no "Tags" note is written (SC-004).
2. Roll the import back. "senator" is gone from the list, and "accounting" stays if it existed before.

## 5. API (US5)

Follow [contracts/api.md](contracts/api.md): tag Dwight with a new wording through the section save,
then list the vault filtered by that id. The outcome matches sections 1 and 2.

## Done

`php artisan monica:localize --check`, `vendor/bin/phpstan analyse --no-progress` and
`vendor/bin/pint --dirty --format agent` report nothing.
