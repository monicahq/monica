# Implementation Plan: The Me contact in every new vault

**Branch**: `2026-10-02-vault-owner-contact` | **Date**: 2026-10-02 | **Spec**: [spec.md](spec.md)

## Summary

`CreateVault` writes the vault, its template defaults and the Me contact in one transaction, the contact through `CreateContact(isMe: true)`. Sign up builds the value lists and the contact template synchronously before the first vault. Queued work dispatched during the creation waits for the commit. The delete, merge, move and ceiling guards exist already and are not touched. Decisions and their reasons are in [research.md](research.md).

## Technical Context

**Language/Version**: PHP 8.4, current Laravel

**Primary Dependencies**: none added

**Storage**: no schema change. `contacts.is_me` exists.

**Testing**: PHPUnit, `tests/Unit/Actions` and `tests/Feature/Controllers`

**Project Type**: web application with a JSON API, CardDAV and MCP

**Constraints**: one transaction per vault creation (FR-009); dispatch after commit (FR-010); no change to existing vaults, the dashboard or the interaction count

**Scale/Scope**: 3 actions, 1 job docblock, 2 controllers, 1 seeder, 7 documents

## Constitution Check

| Principle | Result |
| --- | --- |
| I. Actions hold the logic | Pass. The rule lives in `CreateVault`; controllers only map one more refusal. Calling a job's `handle()` from `CreateAccount` is noted in R2. |
| II. Simplicity | Pass. No new class, no new column, one new private method. |
| III. Tested | Pass, see Tests below. |
| V. Localised | One new refusal sentence; `monica:localize` run in every locale. |
| VI. Nothing changes under anybody's feet | Pass. Existing vaults untouched; the Me contact is never renamed after the account. |
| VIII. Nothing disappears silently | Pass. The Me contact leaves only with its vault, which is soft deleted. |
| X. Through the API | Pass. `POST api/v1/vaults` goes through the same action; `contacts/me` and MCP `GetMeContact` read it. |
| Product invariant on the Me contact | Contradicts `spec/models/007`. Amended to one per vault (R7). |

Gate passes. Re-checked after design: unchanged.

## Changes

| File | Change |
| --- | --- |
| `app/Actions/CreateVault.php` | Wrap create, both template defaults, the Me contact and the log in `DB::transaction`; add `createTheMeContact()`; log `afterCommit`; class docblock names the Me contact |
| `app/Actions/CreateContact.php` | `afterCommit()` on `LogUserAction` and `DetectDuplicateContacts` |
| `app/Actions/CreateAccount.php` | Inside the transaction, after the user: `CreateDefaultValueLists`, then `CreateDefaultTemplate::handle()`; drop both from the chain; docblock |
| `app/Jobs/CreateDefaultTemplate.php` | Docblock: the contact template is now run inline at sign up |
| `app/Http/Controllers/App/Vaults/VaultController.php` | Catch `VaultHasNoContactDefault`, answer as for `AuthorizationException` with a translated sentence |
| `app/Http/Controllers/Api/VaultController.php` | Same, as a 422 on `name` |
| `database/seeders/ExampleAccountSeeder.php` | Reuse the vault's Me contact instead of creating it |
| `lang/*.json` | Through `monica:localize` only |
| Documents listed in R7 | Updated as the table says |

## Tests

New tests only for what nothing proves yet. Delete (`ContactPolicyTest`, `AddressBookTest`), merge (`PreviewContactMergeTest`, `MergeContactsTest`), device move (`AddressBookTest`), the ceiling (`AccountTest`, `CreateContactTest`) and one per vault (`CreateContactTest`) are already covered.

| Test file | Proves |
| --- | --- |
| `tests/Unit/Actions/CreateVaultTest.php` | the vault holds one Me contact named after the user, on the system template, with `contact_count` 1; a vault is created at the contact ceiling; no template leaves no vault, no default and no contact; a rolled back creation processes no job |
| `tests/Unit/Actions/CreateAccountTest.php` | the first vault holds the Me contact on the contact template; the chain no longer carries the lists or the contact template |
| `tests/Unit/Actions/UpdateUserProfileTest.php` | renaming the user leaves the Me contact's name alone |
| `tests/Unit/Actions/DestroyVaultTest.php` | the Me contact is soft deleted with the vault |
| `tests/Feature/Controllers/App/Vaults/VaultControllerTest.php` | no template answers with the error on the form |
| `tests/Feature/Controllers/Api/VaultControllerTest.php` | no template answers 422 |
| `tests/Unit/Seeders/ExampleAccountSeederTest.php` | still passes unchanged |

Existing tests that create a vault for a factory user give the account its template first with `new CreateDefaultTemplate(user: $user)->handle()`, as `ContactFormViewModelTest` does. The whole suite runs, since every sign up and vault creation path changes.

## Project Structure

No new folder or class. `data-model.md`, `contracts/` and `quickstart.md` are omitted: no entity or interface changes, the one new refusal is in Changes, and the Tests table is the validation guide.

## Complexity Tracking

None.
