---
description: "Task list for the Contacts and more screen"
---

# Tasks: Contacts and more

**Input**: Design documents from `specs/050-entity-types-screen/`

**Prerequisites**: plan.md, spec.md, research.md

**Tests**: Required (constitution III). Follow `.claude/rules/testing.md`: strict types, `#[Test]`, snake_case `it_` names, named arguments, The Office data, no comments, no class string or markup order assertion. Counts and examples in the view model test, copy and links in the screen test.

**Organization**: the view model is the foundation. US1 draws the header and the examples, US2 the create band, US3 the list. All three edit `index.blade.php`, so they run in order.

## Format: `[ID] [P?] [Story] Description`

## Phase 1: Setup

No setup: no dependency, configuration or data change.

## Phase 2: Foundational

- [X] T001 In `app/ViewModels/Settings/Account/EntityTypes/EntityTypesViewModel.php`, change `countLabel()` to return `__('No profiles yet')` at zero, else `trans_choice(':count profile|:count profiles', $count)` (research R4); delete `summary()` (research R5)
- [X] T002 In `app/ViewModels/Settings/Account/EntityTypes/EntityTypesViewModel.php`, add `contacts(): array{countLabel: string}` built from `(int) Vault::query()->where('account_id', $this->user->account_id)->sum('contact_count')` through `countLabel()` (research R1), and `hasTypes(): bool` from `$this->query()->exists()`
- [X] T003 In `app/ViewModels/Settings/Account/EntityTypes/EntityTypesViewModel.php`, add `examples()` with an array shape `@return`, giving three arrays `{icon, kind, tag, name, fields, caption}` (research R2): `profile`, `__('Contacts')`, `__('Built in')`, `Judy Geller`, `[__('Birthday'), __('Phone'), __('Notes')]`, `__('Your profiles for people')`; `pet`, `__('Pets')`, `__('Example')`, `Garfield`, `[__('Species'), __('Birthday'), __('Notes')]`, `__('A profile for each pet')`; `organization`, `__('Companies')`, `__('Example')`, `Central Perk`, `[__('Address'), __('Website'), __('Notes')]`, `__('A profile for each company')`. Names go through `__()` too
- [X] T004 In `tests/Unit/ViewModels/Settings/Account/EntityTypes/EntityTypesViewModelTest.php`, delete `it_sums_the_records_of_every_type_for_the_heading` and `it_says_so_when_the_account_has_one_of_each`; change `it_says_how_much_a_type_holds` to `No profiles yet`, `1 profile`, `9 profiles` and the `4 records` assertion to `4 profiles`; add `it_counts_the_contacts_of_every_vault_of_the_account` (two vaults of Michael's account with `contact_count` 3 and 4, one vault of Dwight's with 9: `7 profiles`), `it_says_whether_the_account_has_a_type_of_its_own` (false, then true after one `EntityType::factory()`), `it_gives_three_example_profiles_with_three_fields_each`

**Checkpoint**: T004 passes.

## Phase 3: User Story 1 - Understand what the screen is for (Priority: P1)

**Goal**: the heading, the explanation and the three example cards.

**Independent Test**: spec US1.

- [X] T005 [US1] In `resources/views/app/settings/account/entity-types/index.blade.php`, keep the first line and `@var` block (reword the first line to `{{-- The kinds of profile the account keeps: contacts, and the types it made itself. --}}`); set `x-settings.page :title` and `x-page-header :title` to `__('Contacts and more')`, the description to `__('You already have profiles for people. Create other kinds of profiles for the pets, companies, or things you want to remember. Each has its own information, organized into sections, just like a contact.')`, and remove the `actions` slot
- [X] T006 [US1] In the same file, add a `<section aria-label="{{ __('Kinds of profile') }}" class="flex flex-col gap-3">` with a `grid grid-cols-[repeat(auto-fit,minmax(240px,1fr))] gap-4` looping `$viewModel->examples()`: each an `x-panel :title="$example['kind']" :icon="$example['icon']" :flush="true"` with `<x-slot:action><x-badge>{{ $example['tag'] }}</x-badge></x-slot:action>`; inside, `p-4 flex flex-col gap-3.5`: `x-avatar-initials :name :size="36"` beside the name in `text-card-title text-ink`, then a `<dl class="m-0 flex flex-col gap-2">` of `grid grid-cols-[72px_minmax(0,1fr)] items-center gap-3` rows, `<dt class="text-base text-muted">` and `<dd aria-hidden="true" class="m-0 h-2 rounded-sm border border-dashed border-hairline-strong bg-card">`; `<x-slot:footer class="text-muted">` holding the caption. Under the grid, `<p class="text-base text-pretty text-muted">` with `__('Fields shown are examples. A type says what you keep track of, and its templates define the fields. Pets and companies do not use the relationships people have.')`. Read `x-panel` first: the `action` slot and `footer` exist; place named slots after the default content
- [X] T007 [US1] In `tests/Feature/Controllers/App/Settings/Account/EntityTypes/EntityTypeControllerTest.php`, add `it_explains_what_a_profile_type_is`: the index renders `Contacts and more`, `Garfield`, `Central Perk` and `Fields shown are examples`

**Checkpoint**: US1 renders.

## Phase 4: User Story 2 - Create a profile type (Priority: P1)

**Goal**: the create band under the examples.

**Independent Test**: spec US2.

- [X] T008 [US2] In `index.blade.php`, after the examples, add `<section aria-label="{{ __('Create') }}" class="flex flex-wrap items-center gap-x-5 gap-y-3">` with `<x-button :href="route('settings.entityTypes.new')" data-turbo="true" class="max-sm:w-full">`, an `<x-icon name="plus" :size="16" />` and `__('Create a profile type')`, then `<p class="flex-[1_1_280px] text-base text-pretty text-muted">` with `__('Start with a kind, such as “Pets.” You’ll add individual pets, such as “Garfield,” afterward.')`
- [X] T009 [US2] In `EntityTypeControllerTest.php`, extend `it_explains_what_a_profile_type_is` to assert `Create a profile type` and `route('settings.entityTypes.new')`

## Phase 5: User Story 3 - See and order the profile types (Priority: P2)

**Goal**: the pinned Contacts row above the existing sortable list.

**Independent Test**: spec US3.

- [X] T010 [US3] In `index.blade.php`, replace the `x-page-section` title with `__('Your profile types')`, its description with `$viewModel->hasTypes() ? __('Drag a row to change the order in the vault navigation.') : null`, and remove the `aside` slot. In the `x-panel :flush`, before `#entity-types`, write the Contacts row (research R3): `flex items-center gap-3 border-b border-hairline py-3.5 pr-4 pl-9 last:border-b-0` holding a `size-8 rounded-sm bg-hover text-body` tile with `<x-icon name="profile" :size="16" />` (`aria-hidden="true"`), then `text-base font-semibold text-ink` `__('Contacts')` with `<x-badge>{{ __('Built in') }}</x-badge>`, and under it `text-xs text-muted` with `$viewModel->contacts()['countLabel']` · `<x-icon name="lock" :size="14" aria-hidden="true" />` `__('Can’t be deleted')`. Keep the `#entity-types` container; replace `@forelse … @empty … @endforelse` with `@foreach`; reword the footnote to `__('Turning a type off keeps its profiles and stops new ones being made.')`
- [X] T011 [US3] In `resources/views/app/settings/account/entity-types/_row.blade.php`, reword the first line to `{{-- One profile type, on the list that orders them. --}}`; show `namePlural` in `text-base font-semibold text-ink` in place of `nameSingular`, drop the singular and the slug line; keep the tile, `Not active`, `countLabel` and Edit
- [X] T012 [US3] In `EntityTypeControllerTest.php`, change `it_lists_the_types_of_the_account` to assert `Wine bottles`, `Contacts`, `Built in`, `Can’t be deleted` and the type's `settings.entityTypes.edit` route, without the slug; add `it_offers_no_ordering_without_a_type_of_its_own`: an account with no type renders `Contacts` and not `Drag a row to change the order`

**Checkpoint**: US3 renders; reorder still posts to `settings.entityTypePosition.update`.

## Phase 6: Polish

- [X] T013 Run `php artisan monica:localize`, translate every new string in every configured locale following `.claude/rules/translations.md` (typography per locale; one word for "profile" per locale, research R6), then `php artisan monica:localize --check`
- [X] T014 Run `php artisan test --compact tests/Unit/ViewModels/Settings/Account/EntityTypes tests/Feature/Controllers/App/Settings`, then the whole suite
- [X] T015 Run `vendor/bin/phpstan analyse --no-progress` and `vendor/bin/pint --dirty --format agent`
- [ ] T016 Check by hand at desktop and 390px, keyboard only: cards stack, create line wraps, rows reorder, Contacts row stays first

## Dependencies

- T001 → T002 → T003 → T004. Phase 2 blocks every story.
- T005 → T006 → T008 → T010 (all in `index.blade.php`). T011 [P] may run beside T010.
- Tests follow their story's markup: T007 after T006, T009 after T008, T012 after T011.
- Polish after all stories.

## Parallel Opportunities

T011 (`_row.blade.php`) beside T010. T013 beside T014 once the strings exist.

## Implementation Strategy

MVP is Phase 2 and US1: the screen explains itself. US2 moves the create button, US3 adds the Contacts row. Commit after each phase.
