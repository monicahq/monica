# Tasks: Preview list and admission while registration is closed

**Input**: [plan.md](./plan.md), [spec.md](./spec.md), [research.md](./research.md), [data-model.md](./data-model.md), [contracts/api.md](./contracts/api.md), [quickstart.md](./quickstart.md)

Every task writes its test first and sees it fail, as principle III asks. A test sets `monica.preview_list_enabled` and `monica.registration_enabled` with `config([...])`.

## Phase 1: Setup

- [X] T001 Add `'preview_list_enabled' => (bool) env('MONICA_PREVIEW_LIST_ENABLED', false)` with a block comment after `registration_enabled` in `config/monica.php`, and nothing in `.env.example`

## Phase 2: Foundational

- [X] T002 Add `case PreviewListEnabled = 'preview_list_enabled'` to `app/Enums/InstanceSettingEnum.php` (group `SigningIn`, kind `Boolean`, no warning, description "Whether a visitor may leave an email address to wait for an account while registration is closed."), following `RegistrationEnabled` in every `match`, and add its row to `tests/Unit/Enums/InstanceSettingEnumTest.php`
- [X] T003 [P] Run `php artisan make:model PreviewListEntry -mf --no-interaction`; the migration creates `preview_list_entries` with `email` (string, unique, stored lowercased), `locale` (string), `token` (string, nullable, unique, SHA-256 of the token in the link), `token_expires_at`, `admitted_at`, `registered_at` (datetime, nullable) and timestamps, `up()` only; the factory in `database/factories/PreviewListEntryFactory.php` gets the states `admitted(string $token)` and `registered()`
- [X] T004 Write `app/Models/PreviewListEntry.php` with `#[Fillable]`, datetime casts, `Notifiable`, `HasLocalePreference` (`preferredLocale()` answers `locale`) and `findByAdmissionToken(string $token): ?self` (the hash matches, `registered_at` is null, `token_expires_at` is in the future), and prove an unknown, an expired, a replaced and a used token all answer null in `tests/Unit/Models/PreviewListEntryTest.php`
- [X] T005 [P] Add `PreviewListEntryAdmitted` ("Admitted :email from the preview list") and `PreviewListEntryRemoved` ("Removed :email from the preview list") to `app/Enums/AdministratorActionEnum.php`, icon `profile`, `namesTheAccountHolder()` false

## Phase 3: User Story 1, a visitor joins the preview list (P1)

**Independent test**: with the list on and registration closed, open `/` signed out, submit an address, find the row in `preview_list_entries`.

- [X] T006 [US1] Create `app/Actions/JoinPreviewList.php` (`email`, `locale`): lowercase the address, create a waiting entry, and do nothing when a user or an entry already holds it; prove the three cases and that no notification is sent in `tests/Unit/Actions/JoinPreviewListTest.php`
- [X] T007 [US1] Create `app/Http/Controllers/App/Auth/PreviewListEntryController.php` with `new()` on `GET preview` named `auth.previewList.new` and `create()` on `POST preview` named `auth.previewList.create` in the guest group of `routes/auth.php` behind `throttle:6,1`: not found unless the list is on and registration closed, `email` validated as `required`, `string`, `email`, `max:255`, `disposable_email` with no `unique`, Turnstile when `turnstile.enabled`, then a redirect to `auth.previewList.new` with the status "You are on the list. We will write to you when you can create your account."; prove in `tests/Feature/Controllers/App/Auth/PreviewListEntryControllerTest.php` the 404, the validation, and the same redirect and status for a new address, a listed one and an account holder
- [X] T008 [US1] In `app/Http/Controllers/App/HomeController.php` redirect a visitor to `auth.previewList.new` when the list is on and registration closed, and create `resources/views/app/auth/preview-list.blade.php` from `x-auth-page` (two sentences on the product, the `email` field, `x-turnstile` when enabled, the session status, `x-auth-footnote` to `auth.login.new`, no view model); prove in `tests/Feature/Controllers/App/HomeControllerTest.php` the card with its form action and field, and the redirect to sign in in the three other combinations

## Phase 4: User Story 2, an administrator admits somebody (P1)

**Independent test**: with registration closed, admit an entry, follow the link, create the account; `/register` without the link answers not found.

- [X] T009 [US2] Give `app/ViewModels/Auth/RegisterViewModel.php` an optional `PreviewListEntry` and token (`admittedEmail()`, `admission()`), create `app/Http/Controllers/App/Auth/PreviewAdmissionController.php` with `show(string $token)` on `GET register/admission/{token}` named `auth.previewAdmission.show` (guest group, outside `registration`, `throttle:6,1`, not found unless `findByAdmissionToken()` answers), and in `resources/views/app/auth/register.blade.php` make the address read only, add the hidden `admission` field and label the password button "Create my account" when there is an admission; prove it in `tests/Feature/Controllers/App/Auth/PreviewAdmissionControllerTest.php` and `tests/Unit/ViewModels/Auth/RegisterViewModelTest.php`
- [X] T010 [US2] Add the optional `previewListEntry` to `app/Actions/CreateAccount.php`: the address comes from the entry, `markEmailAsVerified()` runs inside the transaction before `Registered` fires, and the entry gets `registered_at` now with `token` and `token_expires_at` null; prove in `tests/Unit/Actions/CreateAccountTest.php` the verified address, the registered entry and that no verification email is sent
- [X] T011 [US2] In `app/Http/Middleware/EnsureRegistrationIsEnabled.php` let a request through when `PreviewListEntry::findByAdmissionToken((string) $request->input('admission'))` answers, and in `create()` of `app/Http/Controllers/App/Auth/RegistrationController.php` resolve the entry, merge its address into `email` before validation, pass it to `CreateAccount` and redirect to `home.index` instead of `auth.verification.notice`; prove in `tests/Feature/Controllers/App/Auth/RegistrationControllerTest.php`, registration closed, that a usable admission creates a verified account on the admitted address whatever `email` holds, and that an unknown, an expired and a used one answer 404
- [X] T012 [P] [US2] Do the same in `create()` of `app/Http/Controllers/App/Auth/RegistrationPasskeyController.php` and prove the usable and the refused admission in `tests/Feature/Controllers/App/Auth/RegistrationPasskeyControllerTest.php`
- [X] T013 [P] [US2] Do the same in `store()` of `app/Http/Controllers/Api/Auth/RegistrationController.php` with `admission` validated as `nullable`, `string`, and prove the four rows of `contracts/api.md` in `tests/Feature/Controllers/Api/Auth/RegistrationControllerTest.php`
- [X] T014 [US2] Create `app/Notifications/PreviewListEntryAdmitted.php` after `MagicLinkCreated` (mail, queue `high`, no `HasEmailType`, action to `auth.previewAdmission.show`, a line saying the link works once and for 14 days), and prove the subject, the `actionUrl`, the outro and the queue in `tests/Unit/Notifications/PreviewListEntryAdmittedTest.php`
- [X] T015 [US2] Create `app/Actions/AdmitPreviewListEntry.php` (`administrator`, `entry`) and `app/Exceptions/AddressAlreadyHoldsAnAccount.php`: refuse a registered entry or an address a user holds, store the SHA-256 of `Str::random(64)`, `token_expires_at` 14 days ahead and `admitted_at`, notify the entry, dispatch `LogAdministratorAction` with `parameters: ['email' => ...]` and the entry as subject; prove in `tests/Unit/Actions/AdmitPreviewListEntryTest.php` the admission, the log, both refusals, and that admitting again makes the first token unusable
- [X] T016 [US2] Create `app/Actions/AdmitOldestPreviewListEntries.php` (`administrator`, `count`) looping `AdmitPreviewListEntry` over the waiting entries by `created_at` then `id`, skipping a refused one and returning how many were admitted; prove the order, the case where fewer wait and the skip in `tests/Unit/Actions/AdmitOldestPreviewListEntriesTest.php`
- [X] T017 [US2] Create `app/ViewModels/Admin/PreviewListViewModel.php` paged as `AccountsViewModel` is: entries ordered waiting, admitted, registered with a portable `CASE`, then `created_at`, then `id`, each with its address, the day it joined, its state label, the day it was admitted and whether it can be admitted, plus `isEnabled()` and the number waiting; prove the order, the labels and the empty list in `tests/Unit/ViewModels/Admin/PreviewListViewModelTest.php`
- [X] T018 [US2] Create `app/Http/Controllers/App/Admin/PreviewListEntryController.php` with `index()` on `GET admin/preview-list` named `admin.previewList.index` in `routes/administrator.php`, the view `resources/views/app/admin/preview-list/index.blade.php` from `x-page-header` and `x-list-panel` (one sentence linking to Configuration when the list is off), and the "Preview list" line after Accounts in `resources/views/layouts/admin.blade.php`; add `'the preview list' => ['admin/preview-list']` to `tests/Feature/Controllers/App/Admin/AdminAccessTest.php`, `PreviewListEntry::class` to `modelsScopedByTheirController` in `tests/Feature/RouteProtectionTest.php`, and prove the screen renders in `tests/Feature/Controllers/App/Admin/PreviewListEntryControllerTest.php`
- [X] T019 [US2] Create `app/Http/Controllers/App/Admin/PreviewListAdmissionController.php` with `create()` on `POST admin/preview-list/{previewListEntry}/admission` named `admin.previewList.admission.create` (`whereNumber`), an Admit button per waiting entry and an Admit again button per admitted entry in the view; prove the redirect with its status, and `AddressAlreadyHoldsAnAccount` reaching the screen as an error, in `tests/Feature/Controllers/App/Admin/PreviewListAdmissionControllerTest.php`
- [X] T020 [US2] Create `app/Http/Controllers/App/Admin/PreviewListOldestAdmissionController.php` with `create()` on `POST admin/preview-list/oldest-admissions` named `admin.previewList.oldestAdmissions.create`, `count` validated as `required`, `integer`, `min:1`, `max:100`, the status through `trans_choice(':count person was admitted.|:count people were admitted.', $admitted)` or "Nobody is waiting." at zero, and its field and button above the list; prove the validation and the redirect in `tests/Feature/Controllers/App/Admin/PreviewListOldestAdmissionControllerTest.php`

## Phase 5: User Story 3, an administrator manages the list (P2)

**Independent test**: as an administrator, switch the list on in Configuration, read the list, remove one entry.

- [X] T021 [P] [US3] Create `app/Actions/RemovePreviewListEntry.php` (`administrator`, `entry`) deleting the row and dispatching `LogAdministratorAction` with `PreviewListEntryRemoved` and the address; prove the row is gone, the log, and that its token no longer resolves in `tests/Unit/Actions/RemovePreviewListEntryTest.php`
- [X] T022 [US3] Add `destroy()` to `app/Http/Controllers/App/Admin/PreviewListEntryController.php` on `DELETE admin/preview-list/{previewListEntry}` named `admin.previewList.destroy` (`whereNumber`) and an `x-delete-action` per entry in `resources/views/app/admin/preview-list/index.blade.php`, its warning saying the link stops working and no account is touched; prove the redirect and the missing row in `tests/Feature/Controllers/App/Admin/PreviewListEntryControllerTest.php`
- [X] T023 (dropped: the switch is already proven by `it_saves_a_change`, and a second request in one test cannot see a stored setting without a reboot) [P] [US3] Prove in `tests/Feature/Controllers/App/Admin/InstanceSettingControllerTest.php` that switching `preview_list_enabled` on dispatches `InstanceSettingUpdated` and that the next request to `/`, registration closed, shows the card

## Phase 6: Polish

- [X] T024 [P] Document the `admission` field and the answers of `contracts/api.md` in the `register-store` body of `resources/docs/api/02-authentication.php`
- [X] T025 Run `php artisan monica:localize`, translate every new string in every configured locale following `.claude/rules/translations.md`, and confirm `php artisan monica:localize --check` passes
- [X] T026 Run the whole suite, PHPStan and Pint as listed in `quickstart.md`, then walk its manual steps
- [X] T027 Add the feature to `docs/FEATURES.md` through the `feature-documentator` skill

## Dependencies

- T001 before everything. T003 before T004. T004 and T005 before Phases 3 to 5.
- US1: T006 before T007; T008 depends only on T001. US1 needs nothing from US2 or US3.
- US2: T009 before T011 and T014; T010 before T011, T012 and T013; T011 before T012 and T013 (the middleware); T014 before T015; T015 before T016 and T019; T017 before T018; T018 before T019 and T020; T016 before T020.
- US3: T021 before T022; T022 after T018 (same controller and view); T023 after T002 and T008.
- T012 with T013, T021 with T023, and T003 with T005 touch different files and can run together.
- T025 after every task that adds a string; T026 after everything else; T027 last.
- MVP: Phases 1 to 4. A list nobody is admitted from is a dead end, so US1 alone does not ship. Phase 5 adds removal.
