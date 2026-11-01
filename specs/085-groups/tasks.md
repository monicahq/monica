# Tasks: Groups

Design: [plan.md](plan.md), [research.md](research.md), [data-model.md](data-model.md), [contracts/api.md](contracts/api.md). Each task writes its test in the layer `.claude/rules/testing.md` assigns, and the test must pass before the task is done.

## Phase 1: Setup

- [X] T001 Create the `groups` flag in `app/Features/Groups.php` (`#[Name('groups')]`) and add it to the flags defined on in `tests/TestCase.php` (R2)
- [X] T002 [P] Add the eight cases of R11 to `app/Enums/UserActionEnum.php`, with their labels

## Phase 2: Foundational

- [X] T003 Write `database/migrations/2026_10_29_000000_create_groups_table.php` and `2026_10_29_000001_create_group_members_table.php`, one table each, columns and indexes exactly as in data-model.md; `up()` only, `foreignIdFor(...)->constrained()`
- [X] T004 [P] Create `app/Models/Group.php` and `database/factories/GroupFactory.php` as in data-model.md (`currentMembers()` filters `left_on` null and live contacts)
- [X] T005 [P] Create `app/Models/GroupMember.php` and `database/factories/GroupMemberFactory.php` with `ResolvesModelInAccount` and `isCurrent()`; add `groupMembers()` to `app/Models/Contact.php`
- [X] T006 Test `currentMembers()` and `isCurrent()`, including a deleted contact, in `tests/Unit/Models/GroupTest.php` and `tests/Unit/Models/GroupMemberTest.php`
- [X] T007 Add the `flag:groups` route group (empty) to `routes/web.php` and `routes/api.php`, and add each new route to `tests/Feature/RouteProtectionTest.php` as it lands

## Phase 3: User Story 1, create a group and put people in it (P1) MVP

Independent test: create the Halpert household, add three contacts, open the group page.

- [X] T008 [P] [US1] `app/Actions/CreateGroup.php`, `UpdateGroup.php`: name required max 255, description, started and ended dates with `DatePrecisionEnum`; account and vault check; changelog; tests in `tests/Unit/Actions/`
- [X] T009 [P] [US1] `app/Actions/DestroyGroup.php`, `RestoreGroup.php`: soft delete the group alone, memberships untouched, restore brings them back (FR-012); tests in `tests/Unit/Actions/`
- [X] T010 [US1] `app/Actions/CreateGroupMembership.php`: same vault and account, Me contact allowed, entities refused, one current membership per contact and group (FR-005, R4); touch the group's `updated_at`; test in `tests/Unit/Actions/`
- [X] T011 [P] [US1] `app/ViewModels/Vaults/GroupsViewModel.php`: the vault's groups with current member count, paginated with the app's page size and "Load more" (FR-008); test in `tests/Unit/ViewModels/Vaults/`
- [X] T012 [P] [US1] `app/ViewModels/Vaults/GroupViewModel.php`: current members, then former with their periods, paginated (FR-006); empty state; test in `tests/Unit/ViewModels/Vaults/`
- [X] T013 [P] [US1] `app/ViewModels/Vaults/GroupFormViewModel.php`: group fields and the member picker listing the vault's contacts
- [X] T014 [US1] `app/Http/Controllers/App/Vaults/GroupController.php`, `GroupRestorationController.php`, `GroupMemberController.php` (create only here), routes in `routes/web.php`; tests in `tests/Feature/Controllers/App/Vaults/` (wrong vault 404, one refusal on the form)
- [X] T015 [US1] Views `resources/views/app/vaults/groups/index.blade.php`, `show.blade.php`, `form.blade.php`, `_member-form.blade.php` from existing components; delete through `x-delete-section` with its warning; member picker usable by keyboard alone, with a view test of its label and `aria-*` contract in `tests/Feature/Views/` (FR-016)
- [X] T016 [US1] Link the vault's groups from the vault navigation in `resources/views/components/vault/sidebar.blade.php` behind `FlagState::isOn('groups', $account)`
- [X] T017 [P] [US1] `app/Http/Resources/GroupResource.php`, `GroupMembershipResource.php` with the shapes in contracts/api.md
- [X] T018 [US1] `app/Http/Controllers/Api/GroupController.php`, `GroupRestorationController.php`, `GroupMemberController.php` (index, create, destroy), routes in `routes/api.php`; tests in `tests/Feature/Controllers/Api/`
- [X] T019 [US1] MCP tools `ListGroups`, `GetGroup`, `CreateGroup`, `UpdateGroup`, `DeleteGroup`, `RestoreGroup`, `ListGroupMembers`, `AddGroupMember`, `RemoveGroupMember` in `app/Mcp/Tools/` through `MirrorsAnApiCapability`, registered in `app/Mcp/Servers/EricaServer.php` while the flag is on; tests in `tests/Unit/Mcp/Tools/`

## Phase 4: User Story 2, see somebody's groups on their page (P1)

Independent test: open a contact in two groups and in a group they left; see all three.

- [X] T020 [US2] `app/ViewModels/Vaults/ContactGroupsBlock.php`: the contact's groups, current first, with role; one sentence and a way to add when empty; test in `tests/Unit/ViewModels/Vaults/`
- [X] T021 [US2] `resources/views/app/vaults/contacts/_group-memberships.blade.php` included in the contact page (`_groups.blade.php` is already the contact table's row grouping); wire it in `app/ViewModels/Vaults/ContactViewModel.php`, behind `FlagState::isOn('groups', $account)`
- [X] T022 [US2] `app/Http/Controllers/Api/ContactGroupController.php` (`contacts.groups`) and MCP tool `ListContactGroups`; tests in `tests/Feature/Controllers/Api/` and `tests/Unit/Mcp/Tools/`
- [X] T023 [US2] Share link toggle for `includes_groups` in `resources/views/app/vaults/contacts/share-links/form.blade.php`, and the shared page block showing group names only, never other members, both behind `FlagState::isOn('groups', $account)` (FR-015, R9)

## Phase 5: User Story 3, record when somebody joined and left (P2)

Independent test: record a departure, then a return, and see two memberships, one current.

- [X] T024 [US3] `app/Actions/UpdateGroupMembership.php` (role, dates, leaving) and `DestroyGroupMembership.php` (a membership recorded by mistake); `left_on` not before `joined_on`; changelog; both touch the group (data-model.md rules); tests in `tests/Unit/Actions/`
- [X] T025 [US3] Extend `CreateGroupMembership` test: a former member rejoining gets a second membership and the first is unchanged
- [X] T026 [US3] Membership edit form with join and leave dates and precision, and the warning when a membership falls outside the group's dates, in `GroupFormViewModel` and `resources/views/app/vaults/groups/_member-form.blade.php`
- [X] T027 [US3] Membership update and destroy in the web and API `GroupMemberController`, MCP tool `UpdateGroupMember`; tests beside T014 and T018
- [X] T028 [US3] Join and leave entries in `app/ViewModels/Vaults/ContactTimelineBlock.php` inside the page's date span (R5), behind `FlagState::isOn('groups', $account)`; test in `tests/Unit/ViewModels/Vaults/ContactTimelineBlockTest.php`

## Phase 6: User Story 4, keep groups in step with a phone (P2)

Independent test: create a group in the app, see it on a synced device, change it there, see the app.

- [X] T029 [US4] Test first whether a contact a device created answers to the device's `UID` in `tests/Feature/Dav/AddressBookTest.php` (R7 risk); record the answer in research.md R7
- [X] T030 [P] [US4] `app/Http/Dav/GroupVCard.php`: the card in contracts/api.md, members visible in the address book only (R6); test in `tests/Unit/Http/Dav/`
- [X] T031 [US4] Serve group cards and move the sync token in `app/Http/Dav/AddressBookBackend.php` and `CollectionChanges.php`; touch the group when a member enters or leaves the address book; tests in `tests/Unit/Http/Dav/`
- [X] T032 [US4] `app/Actions/WriteGroupCard.php`: rename from `FN`, both `MEMBER` forms, start and end memberships today, ignore unknown members, never create a contact, never touch description, dates, roles or former members, touch the group (data-model.md rules) (FR-019 to FR-021); test in `tests/Unit/Actions/`
- [X] T033 [US4] Route group cards to `WriteGroupCard` in `createCard` and `updateCard`, and `deleteCard` to `DestroyGroup`, in `app/Http/Dav/AddressBookBackend.php`; `CATEGORIES` stays unread; tests in `tests/Feature/Dav/AddressBookTest.php`
- [X] T034 [US4] Tell DAVx5 users to store groups as separate vCards in `app/ViewModels/Docs/CarddavDocsViewModel.php` and its view

## Phase 7: Records that carry memberships

- [X] T035 Merge: report folds in `app/Actions/PreviewContactMerge.php`, move and fold in `MergeContacts.php`, reverse both in `UndoContactMerge.php` with ledger kinds `membership_moved` and `membership_folded`, touching each group (data-model.md rules) (FR-023, R8); tests in `tests/Unit/Actions/`
- [X] T036 Export and import groups and memberships in `app/Actions/WriteAccountExport.php`, `WritesMonicaItems.php`, `ReadMonicaFile.php`; round trip test in `tests/Unit/Actions/WriteAccountExportTest.php` (FR-014)
- [X] T037 Mark groups `deleted_with_vault` in `app/Actions/DestroyVault.php`; test in `tests/Unit/Actions/DestroyVaultTest.php`

## Phase 8: Polish

- [X] T038 [P] API reference `resources/docs/api/27-groups.php` and the tools in `app/ViewModels/Docs/McpDocsViewModel.php`
- [X] T039 [P] Rename every "group" of fields to "block" in `resources/views/app/settings/account/life-event-types/` (`_block`, `form`, `_field`) and the category descriptions in `life-event-types/index` and `relationship-types/index`; move the glossary row in `.claude/rules/translations.md` (FR-022, R10)
- [X] T040 Flag off gives 404 on every group route, no group card, no contact block, no timeline entry and no share link toggle; tests beside T014, T020, T028 and in `tests/Feature/Dav/AddressBookTest.php`
- [X] T041 Align `spec/models/032-group-not-yet.md` and `033-group-member-not-yet.md` with data-model.md (R3)
- [X] T042 `php artisan monica:localize` and translate every locale, then `--check`, `vendor/bin/phpstan analyse --no-progress`, `vendor/bin/pint --dirty --format agent`, full suite
- [ ] T043 Run the manual checks in quickstart.md
- [X] T044 Record the feature in docs/FEATURES.md with the feature-documentator skill

## Dependencies

- Phase 2 needs Phase 1; every story needs Phase 2.
- US2 needs memberships from T010; US3 needs T010 and T014; US4 needs T009 and T010.
- Phase 7 needs T010 and T024. Phase 8 needs every story.
- T029 comes before T032 and T033.
- Ship order: US1 and US2 make the smallest useful release, but the flag stays off until US4 and Phase 7 are done (spec Assumptions).
