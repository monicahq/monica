---
description: "Task list for the time zone picker"
---

# Tasks: Time zone picker

**Input**: [spec.md](spec.md), [plan.md](plan.md), [research.md](research.md), [quickstart.md](quickstart.md)

**Format**: `[ID] [P?] [Story?] Description with file path`. `[P]` means it touches no file another
unfinished task touches. Tests are not optional here: Principle III is non-negotiable.

**Shape**: one line per task, phase headings and a dependency list, per Principle XI. The template's
strategy, parallel-example and notes sections are omitted rather than restated.

## Phase 1: Setup

- [X] T001 Run `php artisan test --compact` and record a green baseline before anything changes

---

## Phase 2: Foundational (blocks every story)

- [X] T002 [P] Add `UserTimezoneUpdate = 'user_timezone_updated'` to `app/Enums/UserActionEnum.php`, with its sentence in the label method beside `UserPreferencesUpdate`
- [X] T003 [P] Create `app/Helpers/TimezoneCatalogue.php`: every zone from `timezone_identifiers_list(DateTimeZone::ALL)` as identifier, city (last segment, underscores to spaces), country name via `Locale::getDisplayRegion`, latitude and longitude from `DateTimeZone::getLocation()`, plus `searchIndex()` keyed by identifier and lowercased, `find(string $timezone)`, and `nearest(float $latitude, float $longitude)` using the cosine corrected planar distance of research.md section 4
- [X] T004 [P] Create `tests/Unit/Helpers/TimezoneCatalogueTest.php`: the count, that `US/Pacific` and every `Etc/*` are absent and `UTC` present, the city and country of `America/Argentina/Buenos_Aires`, a lowercased index, and `nearest()` over Tokyo, mid-Pacific ocean and a point that has to wrap the antimeridian
- [X] T005 Create `app/Actions/UpdateTimezone.php`: promoted readonly `User $user` and `string $timezone`, one `execute()` writing the column and dispatching `LogUserAction` with `UserTimezoneUpdate` on the `low` queue (needs T002)
- [X] T006 [P] Create `tests/Unit/Actions/UpdateTimezoneTest.php`: the column is written, `User` comes back, the log is queued, and `locale`, `time_format`, `date_format` and `number_format` are all untouched (FR-011, FR-016) (needs T005)
- [X] T007 Create `app/ViewModels/Settings/Account/Preferences/TimezonePanel.php`: the stored zone, its UTC offset, the clock there in the account's own time format, the sentence naming city and country, the marker's x and y from the projection in research.md section 3, and `searchIndex()` for Alpine (needs T003)
- [X] T008 Teach `app/ViewModels/Settings/Account/Preferences/PreferencesViewModel.php` to read `timePreview()` and `datePreview()` in the user's zone, and to expose the `TimezonePanel` (needs T007)
- [X] T009 [P] Create `tests/Unit/ViewModels/Settings/Account/Preferences/TimezonePanelTest.php`: the offset and clock of a known zone, the sentence, the marker for a known longitude, and that the clock follows a 12 hour account (needs T007)
- [X] T010 [P] Update `tests/Unit/ViewModels/Settings/Account/Preferences/PreferencesViewModelTest.php` so the previews are asserted in a non-UTC zone (needs T008)
- [X] T011 Create `app/Http/Controllers/App/Settings/Account/Preferences/TimezoneController.php` with `update()`: validate the two shapes from plan.md's route table, resolve a coordinate pair through `nearest()`, call `UpdateTimezone`, and redirect to `settings.preferences.index` with `status`, `status_description` and, when the pair arrived, the flag FR-006's sentence reads (needs T003, T005)
- [X] T012 Add `Route::put('settings/account/preferences/timezone', [TimezoneController::class, 'update'])->name('settings.timezone.update')` to `routes/web.php`, beside the preferences routes (needs T011)
- [X] T013 [P] Create `app/Http/Controllers/Api/MeTimezoneController.php` with `update()` over the same action and the identifier shape only, returning `UserResource` (needs T005)
- [X] T014 Add `Route::put('me/timezone', [MeTimezoneController::class, 'update'])->name('me.timezone.update')` to the sanctum group of `routes/api.php` (needs T013)
- [X] T015 [P] Create `tests/Feature/Controllers/App/Settings/Account/Preferences/TimezoneControllerTest.php`: an identifier saves, a coordinate pair saves the nearest zone and flags the guess, an unknown identifier is 422 with the stored zone untouched, an out of range coordinate is 422, neither shape leaves the four formats changed, and a visitor is redirected to sign in (needs T012)
- [X] T016 [P] Create `tests/Feature/Controllers/Api/MeTimezoneControllerTest.php`: 200 with the user resource carrying the new zone, 422 on an unknown identifier, and 401 without a token (needs T014)

**Checkpoint**: the zone is changeable and covered from the action outwards, with no screen yet.

---

## Phase 3: User Story 1, find a time zone by name (P1) MVP

**Goal**: FR-001 to FR-004 and FR-009 to FR-012. **Independent test**: type `Lisbon`, press Enter,
reload, and the zone reads `Europe/Lisbon`.

- [X] T017 [US1] Create `resources/views/app/settings/account/preferences/_timezone.blade.php`: the section heading, the panel, the current zone with its offset and clock, and the search field whose results are real submit buttons carrying `timezone`, inside `<x-form method="put" :action="route('settings.timezone.update')" x-target="timezone-panel preview">`, each button carrying the line it is matched on so no index ships separately, with arrow keys moving real focus, Escape clearing, and the empty-result line of FR-004. Carries FR-006's guess sentence too, that being panel content rather than map content (needs T007, T012)
- [X] T018 [US1] Include the section from `resources/views/app/settings/account/preferences/index.blade.php` and add `timezone-panel` to the `$rows` target list the four format forms share, so a format change redraws the zone panel's clock (needs T017)
- [X] T019 [P] [US1] Update `tests/Feature/Controllers/App/Settings/Account/Preferences/PreferenceControllerTest.php` so the index test asserts the zone section, the stored zone and its clock are on the screen (needs T018)

**Checkpoint**: the MVP. Every zone is reachable with a keyboard alone, and the map does not exist.

---

## Phase 4: User Story 2, point at the map (P2)

**Goal**: FR-005 to FR-007. **Independent test**: click over Japan and the zone becomes `Asia/Tokyo`
with the panel saying the nearest one was taken.

- [X] T020 [P] [US2] Add the map's land, water, hour band and marker colours to the light and dark blocks of `resources/css/app.css`, with their `--color-*` aliases beside the existing ones, drawn from the palette rather than the design's warmer hexadecimal values
- [X] T021 [US2] Generate the outline: fetch `world-atlas@2.0.2/countries-110m.json`, project its `countries` object per research.md section 3, and write `resources/views/components/world-map.blade.php` as the SVG with a header comment recording source, projection and clip. The converter is a throwaway and is not committed (needs T020)
- [X] T022 [US2] Add the map to `_timezone.blade.php`: the component, the hour band and marker positioned from the panel's x and y, hidden `latitude` and `longitude` fields, a click handler that inverts the projection and calls `requestSubmit()`, and the sentence FR-006 shows when the zone came from a click (needs T017, T021)
- [X] T023 [P] [US2] Create `tests/Feature/Views/Components/WorldMapTest.php`: the component renders its viewBox and path, and paints from the palette tokens rather than literal colours (needs T021)
- [X] T024 [US2] Give the zone form `#preview` in its target list so the format footer redraws in the new zone, and confirm the pairing T018 set up still holds (needs T018, T022)

**Checkpoint**: both ways in work, and story 1 still works with JavaScript switched off.

---

## Phase 5: User Story 3, ask the device (P3)

**Goal**: FR-008. **Independent test**: press the button and the zone becomes the browser's own.

- [X] T025 [US3] Add the "Use my device" button to `_timezone.blade.php`: it writes `Intl.DateTimeFormat().resolvedOptions().timeZone` into the identifier field and submits, and says so when the field comes back empty (needs T017)
- [X] T026 [P] [US3] Extend `tests/Feature/Controllers/App/Settings/Account/Preferences/TimezoneControllerTest.php` with the case the button produces, a zone the product does not know, asserting 422 and the stored zone untouched (needs T025)

**Checkpoint**: all three ways in work.

---

## Phase 6: Polish and cross-cutting

- [X] T027 Run `php artisan monica:localize` and translate every string it reports in all nine locales, then `php artisan monica:localize --check`
- [X] T028 [P] Update `spec/models/002-user-done.md` lines 57 and 63, which say the zone cannot be changed after sign up, and leave line 64 as it is
- [X] T029 [P] Run `vendor/bin/pint --dirty --format agent` and `vendor/bin/phpstan analyse`, fixing rather than ignoring what they report
- [X] T032 Document `PUT /me/timezone` in `resources/docs/api/04-profile.php`, which Principle X and the portal's own test both require, and register it in the two docs tests that enumerate the reference
- [X] T030 Walk the by-hand checks in [quickstart.md](quickstart.md): keyboard alone, JavaScript off, both themes, 200% zoom, and that no format moved
- [X] T031 Run `php artisan test --compact` in full and compare against T001's baseline

---

## Dependencies

- T001 first. T002, T003, T004 then run together.
- T005 needs T002. T006 needs T005. T007 needs T003. T008 needs T007. T009 needs T007. T010 needs T008.
- T011 needs T003 and T005. T012 needs T011. T013 needs T005. T014 needs T013. T015 needs T012. T016 needs T014.
- Phase 2 blocks every story. T004, T006, T009, T010, T015 and T016 are parallel among themselves.
- US1: T017 needs T007 and T012. T018 needs T017. T019 needs T018.
- US2: T020 alone. T021 needs T020. T022 needs T017 and T021. T023 needs T021. T024 needs T018 and T022.
- US3: T025 needs T017. T026 needs T025.
- US2 and US3 both need US1's T017, because all three ways in share one form. They are otherwise independent of each other.
- Phase 6 last. T027 needs every string to exist, so it follows the last story shipped.
