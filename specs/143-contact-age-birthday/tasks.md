# Tasks: Contact age and upcoming birthday

**Input**: Design documents from `specs/143-contact-age-birthday/`

**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/

**Tests**: Included. Constitution principle III makes tests a merge condition. Follow
`.claude/rules/testing.md`: `declare(strict_types=1);`, `#[Test]`, snake_case `it_` names, named
arguments, The Office data, `CarbonImmutable::setTestNow('2026-11-20')` for every date.

**Organization**: Tasks are grouped by user story to enable independent implementation and testing of each story.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (US1, US2, US3)

## Path Conventions

Single Laravel project at the repository root: `app/`, `resources/`, `lang/`, `tests/`.

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: None needed. No dependency, no migration, no new folder.

- [X] T001 Read the rule files that cover the files in scope before writing code: `.claude/rules/code-style.md`, `.claude/rules/testing.md`, `.claude/rules/components.md`, `.claude/rules/translations.md`, and the skills `view-models`, `models`, `views`, `api-docs-writer`, `translations`, `changelog`, `definition-of-done` under `.claude/skills/`

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: The window constant, the birthday rule and the relationship every story reads.

- [X] T002 Add `public const int UpcomingDays = 7;` to `app/Enums/ReminderOccurrenceCategoryEnum.php`, above the cases' methods, with no comment
- [X] T003 Create `app/Helpers/Birthday.php`: constructor with promoted `private readonly FieldValue $value` and `private readonly CarbonImmutable $today` (start of the reader's day in their zone). Public `next(): ?CarbonImmutable`, null when `month_is_unknown` or `value_date` is null; the next anniversary on or after `$today`, moving 29 February to the last day of February in a common year. Move the bodies of `nextAnniversary()` and `inYear()` from `app/Helpers/VaultReminderDates.php` into private methods here. Public `daysUntilNext(): ?int`, `0` on the day, null when `next()` is null. Public `isUpcoming(): bool`, true when `daysUntilNext()` is not null and `<= ReminderOccurrenceCategoryEnum::UpcomingDays`. Public `age(): ?int`, null when `year_is_unknown`, `month_is_unknown`, `value_date` null, or `value_date` after `$today`; otherwise whole years on `$today`. No class docblock (it is a helper, not an action); no inline comment
- [X] T004 In `app/Helpers/VaultReminderDates.php`, replace every call of `self::nextAnniversary($date, $today)` with `new Birthday(value: $birthday, today: $today)->next()` and delete the two private methods; make `DefaultDays` equal `ReminderOccurrenceCategoryEnum::UpcomingDays`. Keep `ageOn()` as is (it computes the age on a given deadline date, not today)
- [X] T005 Add `birthday(): MorphOne` to `app/Models/Contact.php` with the docblock `@return MorphOne<FieldValue, $this>` and a sentence saying it is the contact's main birthday: `morphOne(FieldValue::class, 'valuable')->attached()->where('field_key', DefaultFieldEnum::Birthday->value)->whereNull('field_value_row_id')`
- [X] T006 [P] Create `tests/Unit/Helpers/BirthdayTest.php`: age on the day before, on and after the birthday; age null for year unknown, month unknown, a future date; `next()` this year, next year when passed, today on the day; 29 February 1980 gives 28 February 2027; `daysUntilNext()` 0, 1, 6; `isUpcoming()` true at 7 days and false at 8 days; `next()` null when month unknown
- [X] T007 [P] Add to `tests/Unit/Models/ContactTest.php` one test proving `birthday()` returns the top level attached birthday and ignores a detached value, a value inside a repeated row and another field's value
- [X] T008 Run `php artisan test --compact tests/Unit/Helpers/BirthdayTest.php tests/Unit/Helpers/VaultReminderDatesTest.php tests/Unit/Models/ContactTest.php`; the reminder dates tests must pass unchanged

**Checkpoint**: Foundation ready. The reminder dates behave as before.

---

## Phase 3: User Story 1 - The contact page says how old somebody is (Priority: P1) 🎯 MVP

**Goal**: "47 years old" under the name when the birthday is known with its year and month.

**Independent Test**: Jim born 10 March 1978 (far from the window, so no countdown), page opened on
20 November 2026, the line reads "48 years old"; with no birthday, no line.

### Tests for User Story 1

- [X] T009 [P] [US1] Add to `tests/Unit/ViewModels/Vaults/ContactViewModelTest.php`: `birthdayLine()` gives `['age' => '48 years old', 'upcoming' => null]` for Jim born 10 March 1978; null with no birthday; null for a birthday with the year unknown 10 March; null for the month unknown
- [X] T010 [P] [US1] Add to `tests/Feature/Controllers/Api/ContactControllerTest.php`: `show` returns `attributes.age` 48 for Jim born 10 March 1978; `age` null when the birthday field `is_sensitive` and the token lacks `sensitive-fields`; add `age` and `next_birthday_on` to the shared `$jsonStructure`

### Implementation for User Story 1

- [X] T011 [US1] In `app/ViewModels/Vaults/ContactAges.php`, add `->where('month_is_unknown', false)` to the query and update the class docblock sentence about what is left out
- [X] T012 [US1] Add `birthdayLine(): ?array` to `app/ViewModels/Vaults/ContactViewModel.php` with `@return array{age: string|null, upcoming: string|null}|null`: read `$this->contact->birthday`, build `new Birthday(value: ..., today: CarbonImmutable::now($this->user->timezone)->startOfDay())`, set `age` to `trans_choice(':count year old|:count years old', $age)` when `age()` is not null, `upcoming` null for now; return null when both are null. Wrap in `once()`
- [X] T013 [US1] In `resources/views/app/vaults/contacts/_facts.blade.php`, move `id="contact-facts"` and `x-merge="replace"` to a new wrapping `<div>`; inside it, before the `<ul>`, render `<p>` when `$viewModel->birthdayLine()` is not null, holding the age in `text-body` at the design 1b size (15px, the nearest theme text step); keep `mt-4` spacing on the list, the paragraph sits `mt-1.5` under the name. Keep the first line comment and `@var` block; reuse `x-icon` only, no new component
- [X] T014 [US1] In `app/Http/Resources/ContactResource.php`, after `is_me`, add `'age'` from `new Birthday(value: $this->birthday, today: CarbonImmutable::now($request->user()->timezone)->startOfDay())->age()`, null when `$this->birthday` is null, or when `$this->birthday->field->is_sensitive` and `! $this->reachesSensitiveFields($request)`
- [X] T015 [US1] In `app/Http/Controllers/Api/ContactController.php`, add `'birthday.field'` to both `->with([...])` lists (lines 73 and 189) so the index adds no query per contact
- [X] T016 [US1] Run `php artisan test --compact tests/Unit/ViewModels/Vaults/ContactViewModelTest.php tests/Feature/Controllers/Api/ContactControllerTest.php`, `tests/Unit/ViewModels/Vaults/RelationshipsBlockTest.php` and `tests/Unit/ViewModels/Vaults/ImmediateFamilyBlockTest.php`

**Checkpoint**: The age shows on the page and in the API.

---

## Phase 4: User Story 2 - The contact page warns that a birthday is coming (Priority: P2)

**Goal**: the countdown sentence when the next birthday is within 7 days.

**Independent Test**: on 20 November 2026, Jim born 26 November 1978 reads "47 years old · Turns 48 in
6 days, on Thursday 26 November"; born 28 November, "47 years old" alone.

### Tests for User Story 2

- [X] T017 [P] [US2] Add to `tests/Unit/ViewModels/Vaults/ContactViewModelTest.php` one test per row of the state table in `specs/143-contact-age-birthday/data-model.md`: today ("Turns 48 today", age null), tomorrow, in 6 days with "Thursday 26 November", 8 days away (upcoming null), year unknown today, tomorrow and in 6 days; and a reader whose date format starts with the month gets "Thursday November 26"
- [X] T018 [P] [US2] Add to `tests/Feature/Controllers/Api/ContactControllerTest.php`: `next_birthday_on` is `"2026-11-26"` for Jim born 26 November 1978; null for the month unknown; null when sensitive without the ability

### Implementation for User Story 2

- [X] T019 [US2] In `ContactViewModel::birthdayLine()` (`app/ViewModels/Vaults/ContactViewModel.php`), set `upcoming` when `isUpcoming()`, with the phrases of `specs/143-contact-age-birthday/research.md` R6 exactly: on the day with the age known set `age` to null and `upcoming` to `trans_choice('Turns :count today|Turns :count today', $age)`; tomorrow `trans_choice('Turns :count tomorrow|Turns :count tomorrow', $age + 1)`; 2 to 7 days `trans_choice('Turns :age in :count day, on :weekday :date|Turns :age in :count days, on :weekday :date', $days, [...])`; year unknown: `__('Birthday today')`, `__('Birthday tomorrow')`, `trans_choice('Birthday in :count day, on :weekday :date|Birthday in :count days, on :weekday :date', $days, [...])`. `weekday` is `$next->locale(app()->getLocale())->isoFormat('dddd')`; `date` is `isoFormat('MMMM D')` when the user's date format starts with `m`, otherwise `isoFormat('D MMMM')`, as `ReminderViewModel::scheduleLine()` does
- [X] T020 [US2] In `resources/views/app/vaults/contacts/_facts.blade.php`, render `upcoming` after the age: a separator `<span aria-hidden="true" class="text-muted-soft">·</span>` only when both are present, then a `<span>` in `font-semibold text-ink` holding `<x-icon name="gift" :size="15" aria-hidden="true" />` and the text. the `gift` glyph exists in `resources/views/components/icon.blade.php`
- [X] T021 [US2] In `app/Http/Resources/ContactResource.php`, add `'next_birthday_on'` from `->next()?->format('Y-m-d')`, under the same null and sensitive conditions as `age`
- [X] T022 [US2] Run `php artisan monica:localize`, then `php artisan monica:localize --check`. If it refuses two identical English segments, switch those two phrases to `[0,*]` and record the decision in `specs/143-contact-age-birthday/research.md` R6
- [X] T023 [US2] Run the tests of T017 and T018

**Checkpoint**: Age and countdown work on the page and in the API.

---

## Phase 5: User Story 3 - The window is a known quantity (Priority: P3)

**Goal**: the dashboard and the reminder dates read the same 7 days as the contact page.

**Independent Test**: dashboard "Next seven days" group unchanged; the existing dashboard and reminder
dates tests pass untouched.

- [X] T024 [US3] In `app/ViewModels/Vaults/VaultDashboardAttention.php`, delete `DAYS_AHEAD` and use `ReminderOccurrenceCategoryEnum::UpcomingDays` in `rowsUntil($today->addDays(...))`
- [X] T025 [US3] Run `php artisan test --compact tests/Unit/ViewModels/Vaults/VaultDashboardAttentionTest.php tests/Unit/ViewModels/Vaults/VaultDashboardViewModelTest.php` and `tests/Feature/Controllers/Api/VaultReminderOccurrenceControllerTest.php`; no test is added, the boundary itself is proven by `BirthdayTest` (T006)

**Checkpoint**: One window across the product.

---

## Phase 6: Polish & Cross-Cutting Concerns

- [X] T026 Translate every new phrase in every `lang/*.json` through Sonnet subagents, as the `translations` skill requires; counted phrases get the number of segments each locale asks for; then `php artisan monica:localize --check`
- [X] T027 [P] Document `age` and `next_birthday_on` in `resources/docs/api/06-contacts.php` and add an entry to `resources/docs/api-changelog/2026-11-20.php`, following the `api-docs-writer` skill and the null rules of `specs/143-contact-age-birthday/contracts/api-contact.md`
- [X] T028 [P] Write the What's new entry with the `changelog` skill: the age and coming birthday under the name, and that a birthday known only by its year no longer shows an age in the family and relationship blocks
- [X] T029 Run `vendor/bin/pint --dirty --format agent` and PHPStan
- [ ] T030 Run the `accessibility-reviewer` agent over `resources/views/app/vaults/contacts/_facts.blade.php`, and the `rules-reviewer` agent over the change
- [X] T031 Run `composer test:parallel`
- [ ] T032 Walk `specs/143-contact-age-birthday/quickstart.md` by hand, including the French screen

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: none
- **Foundational (Phase 2)**: after Setup; blocks every story
- **US1 (Phase 3)**: after Phase 2
- **US2 (Phase 4)**: after US1, since it extends `birthdayLine()`, the same Blade region and the same resource
- **US3 (Phase 5)**: after Phase 2 only; independent of US1 and US2
- **Polish (Phase 6)**: after the stories to ship

### Within Each User Story

- Tests are written with the implementation and must pass before the checkpoint
- View model before Blade; resource before the controller eager load is verified

### Parallel Opportunities

- T006 and T007 in parallel (different test files)
- T009 and T010; T017 and T018 (view model test against API test)
- Phase 5 in parallel with Phases 3 and 4
- T027 and T028 in parallel

---

## Parallel Example: User Story 1

```text
Task: "T009 [US1] birthdayLine() age tests in tests/Unit/ViewModels/Vaults/ContactViewModelTest.php"
Task: "T010 [US1] API age tests in tests/Feature/Controllers/Api/ContactControllerTest.php"
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Phases 1 and 2
2. Phase 3: the age on the page and in the API
3. Validate with T016, then continue

### Incremental Delivery

1. Foundation, then US1 (age), then US2 (countdown), then US3 (one window), then Polish
2. One commit for the whole feature after `definition-of-done`, since the stories share files

---

## Notes

- No migration: the age is never stored
