# Phase 1 Data Model: Favourite a Contact From Their Page

No migration. No new table, no new column, no new model, no change to any cast or to `$fillable`.
This feature reads and writes a column that already ships, through actions that already exist. What
follows records what that column is and which rules govern it, so the plan can be checked against
the schema rather than against memory.

## Contact

The only entity this feature touches. Defined by `app/Models/Contact.php` and created by
`database/migrations/2026_08_09_000007_create_contacts_table.php`.

| Attribute | Type | Notes |
|-----------|------|-------|
| `id` | integer | Primary key. Never in a URL. |
| `uuid` | string, unique | What the URL and the API carry. The route key. |
| `account_id` | integer | The compartment. Both actions refuse a contact whose account is not the user's. |
| `vault_id` | integer | The second compartment. The controller refuses a contact reached through the wrong vault. |
| `is_favourite` | boolean, default false, not null | **The whole of this feature.** Already fillable, already cast to boolean. |
| `is_archived` | boolean, default false | Independent of the mark. An archived contact may be a favourite. |
| `is_partial` | boolean, default false | Independent of the mark. A partial favourite is remembered and shows nowhere until filled in. |
| `sort_name`, `sort_key` | string, blob | How the list orders within a group. Untouched. |

**No index is added.** `contacts` carries `['vault_id', 'sort_name']` and `['vault_id', 'sort_key']`.
The favourites filter already runs `where('is_favourite', true)` against a vault scoped query and is
not made hotter by this feature, which changes how the flag is set and not how it is read.

### State

`is_favourite` has two states and both transitions are reachable from the contact page.

```text
              press the star (favourite=1)
   not favourite  ─────────────────────────▶  favourite
                  ◀─────────────────────────
              press the star (favourite=0)
```

- Both transitions are idempotent. Setting a mark that is already set stores the same value and
  writes another activity entry, which is correct: the activity records what somebody did, not only
  what changed.
- Neither transition creates a record snapshot, and neither creates a template version. The mark is a
  property of the contact, not of the structure it renders from, so Principle VI is not engaged.
- Neither transition deletes anything, so no confirmation is owed under Principle V.
- There is no third state. A contact is a favourite or is not, per account, and the product is
  mono-user so there is no per-viewer variant.

### Validation rules, from the requirements

| Rule | Source | Where it is enforced |
|------|--------|----------------------|
| The submitted state must be present and boolean | FR-002, research decision 3 | Controller, `['favourite' => ['required', 'boolean']]` |
| A contact in another account is not found | FR-011 | `FavouriteContact::validate()` and `UnfavouriteContact::validate()`, already written, throwing `ModelNotFoundException` |
| A contact reached through the wrong vault is not found | FR-011 | Controller, `abort_unless($contact->isReachableThrough($request->user(), $vault), 404)`, the same line `ContactController::show()` uses |
| The drawn state equals the stored state | FR-012 | The view model reads the column; the redraw comes from a redirect to `contacts.show`, so there is one source |

## Activity entry

Written by the two actions, unchanged by this feature. Recorded here because FR-010 depends on it
and because the plan must not accidentally add a second way to log the same thing.

| Attribute | Value |
|-----------|-------|
| Action | `UserActionEnum::ContactFavourited` or `UserActionEnum::ContactUnfavourited` |
| Sentence | "Pinned the contact :name" and "Unpinned the contact :name", already translated in all nine locales |
| Parameters | `['name' => $contact->name]`, captured at the moment of the press so the entry still reads after a rename |
| Loggable | The contact |
| Vault | The contact's vault |
| Queue | `low`, dispatched through `LogUserAction` |

The entry is append only. Repeated presses produce repeated entries, which the spec's edge cases
accept explicitly.

## What reads the mark today

Listed so the plan can state what a press affects, and so the tests know what to assert beyond the
column.

| Reader | File | Behaviour |
|--------|------|-----------|
| The people list ordering | `ContactsViewModel` | Favourites are grouped and ordered above the rest |
| The favourites filter | `ContactsViewModel::FILTER_FAVOURITES` | `where('is_favourite', true)` |
| A row on the people list | `ContactsViewModel`, `isFavourite` per row | Draws its own mark |
| The contact page | `ContactViewModel::isFavourite()` | The star this feature makes operable |
| The creation form | `ContactFormViewModel::flags()` | A checkbox, on the "add someone" screen only |
| A record snapshot | `CreateRecordSnapshot`, `RestoreRecordSnapshot` | The mark is captured and restored with the rest of the contact |

The last row matters and is easy to miss: restoring a snapshot restores the favourite mark to what it
was at the time of the snapshot. That is existing behaviour, it is correct, and this feature does not
change it.
