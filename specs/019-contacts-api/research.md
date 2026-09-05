# Research: Contact endpoints

Nine decisions. Everything else follows the four API groups already shipped.

## 1. Where the list query lives

The screen's narrowing, search and ordering sit in `ContactsViewModel`, and Principle I forbids any
surface but Blade from reading a view model. FR-004, FR-005 and FR-006 promise the API answers as the
screen does, so the query has to live somewhere both can reach.

**Decision**: move it onto `Contact` as three query scopes beside the `listed()` scope already there,
and move the vocabulary into two enums. Both surfaces call the scopes.

| | |
| --- | --- |
| `scopeInGrouping(ContactGroupingEnum)` | the `is_partial`, `is_archived` and `is_favourite` narrowing |
| `scopeMatchingSearch(string $term, string $locale)` | the fold against `search_key` plus the nickname match |
| `scopeSortedBy(ContactSortEnum, string $direction)` | nulls last, then the column, then the identifier |
| `ContactGroupingEnum` | `all`, `favourites`, `archived` |
| `ContactSortEnum` | `name`, `nickname`, `last-spoke`, `added` |

**Rejected**: duplicating the fifteen lines in the API controller. Nothing would hold the two together,
and the failure is silent: a sort changed on the screen and not in the API reads as correct on both.

The direction stays a plain string checked with `Rule::in`. `ContactsViewModel` keeps its URL building
and its chip labels, which are presentation.

## 2. The grouping is a query parameter, not a path segment

The screen puts it in the path so a chip is a place somebody can link to. The API cannot: `contacts/{filter?}`
and `contacts/{contact}` are the same shape. A list takes `filter`, `query`, `sort`, `direction`, `page`, `per_page`.

## 3. Changing a name replaces rather than patches

`UpdateContactName` writes an absent part as null, exactly as `UpdateVault` does. A caller sending only a
new last name would silently erase the prefix, the nickname and the rest. All eight parts are therefore
`present` and `nullable`, which is the rule the vault endpoint already settled under Principle VIII.

## 4. The picture is a multipart POST

`POST .../avatar`, field `avatar`, because PHP populates `$_FILES` on POST alone. That is why the web
route is a POST too. The validation mirrors the web controller (`image`, `mimes:jpeg,jpg,png,webp`,
`max:5120`), and `UpdateContactAvatar` checks the real mime type again on what actually arrived.

## 5. A defect found while writing this

`CreateFile` throws `NotEnoughStorageLeft` when an upload would take the account past its quota, and
`App\Http\Controllers\App\Vaults\ContactAvatarController` catches nothing, so a full account uploading an
avatar in the browser gets a 500. The spec requires the API to refuse it as invalid and say so. Both
controllers are fixed in this change, per the governance rule that a pull request discovering a conflict
between the constitution and the code says which and fixes it.

## 6. Where a picture can be read

The only route that serves a vault file is `files.show`, a web route behind the session. The resource
carries `avatar` as the file's uuid and that absolute URL, and the reference says plainly that the URL
needs the signed in session until a files endpoint exists. Stating the asymmetry beats omitting the field
and beats inventing a second file surface inside a contacts change.

## 7. Refusals

| Cause | Answer |
| --- | --- |
| Deleting the contact that represents the caller | 422, caught from `CannotDeleteTheMeContact` |
| Over quota picture | 422, caught from `NotEnoughStorageLeft` |
| Draft, foreign or wrong kind of structure on create | 422, from the `Rule::exists` the web form already uses |
| A version the move does not offer | 404, mirroring the web controller's `abort_if` |
| Any contact, vault or version of another account | 404, from an account scoped `firstOrFail` |

## 8. What needs no code

`last_consulted_at` is written by nothing in the application today, so FR-020 holds on its own.
`sort_key` and `search_key` are written in the model's `saving` hook from the account's locale, so a
contact created through the API sorts and searches identically to one created in the browser.

## 9. Deviations from the `api-writer` skill

The same three the vault endpoints declared, for the same reasons, plus one: the controllers are flat
under `Api` rather than under `Api/Vaults`, because `Api/VaultController` already mirrors
`App/Vaults/VaultController` that way. `.claude/CLAUDE.md` ranks the code already there above the skill.
