---

description: "Task list for naming a template copy before it is made"
---

# Tasks: Name a template copy before it is made

**Input**: Design documents from `specs/142-name-template-copy/`

**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/web-routes.md, quickstart.md

**Tests**: Included. Constitution principle III (Tested Before Merged) is non-negotiable. Follow `.claude/rules/testing.md`: `declare(strict_types=1);`, `#[Test]`, snake_case names starting with `it_`, named arguments, The Office data, no comments in tests, no assertion on class strings.

**Organization**: Tasks are grouped by user story. Work stays on the current branch (no new branch).

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (US1, US2)

## Phase 1: Setup

No setup. No dependency, migration or configuration is added.

## Phase 2: Foundational

**Purpose**: The route both stories need.

- [X] T001 Add `Route::get('settings/account/templates/{template}/copy', [TemplateCopyController::class, 'new'])->name('settings.templateCopy.new');` on the line directly above the existing `settings.templateCopy.create` POST route in routes/web.php. The GET `templates/{template}/copy` does not collide with `templates/new` or `templates/import` because those have one segment; confirm with `php artisan route:list --name=templateCopy`

**Checkpoint**: `settings.templateCopy.new` is listed. `tests/Feature/RouteProtectionTest.php` covers it with no change, since the route is behind sign in and `Template` uses `ResolvesModelInAccount`.

---

## Phase 3: User Story 1 - Choose the name and slug of a copy (Priority: P1) MVP

**Goal**: Pressing Copy shows a form prefilled with "Copy of {name}" and the free slug the copy would get; confirming creates the copy with what the form holds.

**Independent Test**: Press Copy on a template, change the name and the slug, confirm; the new template carries both, its builder URL uses the chosen slug, the original is untouched. Leaving the form creates nothing.

### Tests for User Story 1

- [X] T002 [P] [US1] Create tests/Unit/ViewModels/Settings/Account/Templates/TemplateCopyViewModelTest.php (`use RefreshDatabase;`). Cases: `it_suggests_copy_of_the_original_name` (template "Dunder Mifflin contact" gives name "Copy of Dunder Mifflin contact"); `it_suggests_the_slug_the_copy_would_get` (gives `copy-of-dunder-mifflin-contact`); `it_skips_a_slug_already_taken` (an existing template with that slug gives `copy-of-dunder-mifflin-contact-2`); `it_skips_a_slug_held_by_a_deleted_template` (same, with the holder soft deleted); `it_ignores_a_slug_taken_in_another_account`; `it_marks_a_suggested_slug_as_untouched` (`slugTouched` false). Use `Template::factory()` with `account_id` set
- [X] T003 [P] [US1] Extend tests/Feature/Controllers/App/Settings/Account/Templates/TemplateCopyControllerTest.php. Add `it_shows_the_copy_form`: `get(route('settings.templateCopy.new', $template))` answers 200 and shows the page title and the confirm label, and creates no template (`assertDatabaseCount` unchanged). Rewrite `it_copies_a_template` to post `['name' => 'Scranton branch', 'slug' => 'scranton-branch']`, assert the redirect to `route('settings.templateBuilder.index', 'scranton-branch')`, `assertSessionHas('status', 'The template is copied.')`, and `assertDatabaseHas('templates', ['account_id' => ..., 'name' => 'Scranton branch', 'slug' => 'scranton-branch', 'is_system' => false])`. Add `it_derives_the_slug_when_it_is_left_empty`: post `['name' => 'Scranton branch', 'slug' => '']`, slug stored is `scranton-branch`. Each test creating a copy calls `Queue::fake()` and gives the template a published `StructureVersion` as the existing test does

### Implementation for User Story 1

- [X] T004 [P] [US1] Create app/ViewModels/Settings/Account/Templates/TemplateCopyViewModel.php with constructor `private readonly User $user, private readonly Template $template`. Methods: `template(): Template`; `user(): User`; `action(): string` returning `route('settings.templateCopy.create', $this->template)`; `cancelUrl(): string` returning `route('settings.templates.index')`; `/** @return array{name: string, slug: string, slugTouched: bool} */ values(): array`. In `values()`: the suggested name is `__('Copy of :name', ['name' => $this->template->name])`; the suggested slug is `SlugBuilder::build(wanted: null, derivedFrom: $suggestedName, maxLength: Template::SLUG_LENGTH, whenEmpty: 'template', isTaken: fn (string $slug): bool => Template::query()->withTrashed()->where('account_id', $this->user->account_id)->where('slug', $slug)->exists())`, the same call as `DuplicateTemplate::slug()` in app/Actions/DuplicateTemplate.php; `name` is `(string) old('name', $suggestedName)`, `slug` is `(string) old('slug', $suggestedSlug)`, `slugTouched` is `old('slug') !== null && old('slug') !== ''`. Read sibling `TemplateFormViewModel.php` for style. No comments beyond the array shape docblock
- [X] T005 [US1] Add `public function new(Request $request, Template $template): View` to app/Http/Controllers/App/Settings/Account/Templates/TemplateCopyController.php returning `view('app.settings.account.templates.copy', ['viewModel' => new TemplateCopyViewModel(user: $request->user(), template: $template)])`. Change `create()` to pass the validated `name:` and `slug:` to `DuplicateTemplate`; the validation itself is T010, done in the same edit so the form never posts unvalidated input. Keep the redirect and both flash messages exactly as they are. Narrow the return type of `create()` to `RedirectResponse`
- [X] T006 [US1] Create resources/views/app/settings/account/templates/copy.blade.php. First line: `{{-- The form that names a copy of a template and its slug before the copy is made. --}}`, second line `{{-- @var \App\ViewModels\Settings\Account\Templates\TemplateCopyViewModel $viewModel --}}`. Wrap in `x-settings.page` with `current="templates"`, `:back-href="$viewModel->cancelUrl()"`, `:back-label="__('Back to the templates')"`. `x-page-header` title `__('Copy :name', ['name' => $viewModel->template()->name])`, description `__('The copy is a separate template. Change it as you like: the original and every record using it stay as they are.')`. One `x-page-section` titled `__('Name')` with the existing description `__('The name is what the picker shows when somebody makes a record. The slug is what the URL and the API use.')`, holding an `x-panel` with `x-input id="name"` (`maxlength="255"`, `required`, `:error="$errors->get('name')"`) and `x-input id="slug"` (`class="font-mono"`, `:maxlength="\App\Models\Template::SLUG_LENGTH"`, `:error="$errors->get('slug')"`, help `__('Lowercase letters, numbers and dashes. It is the address of the copy and cannot be changed once the copy exists. Left empty, it is made from the name.')`). Alpine on the wrapper: `x-data="{ name: @js($values['name']), slug: @js($values['slug']), slugTouched: @js($values['slugTouched']) }"`. Name input: `x-model="name"` and `@input="if (! slugTouched) slug = window.slugify(name)"`. Do NOT use `x-effect` (research R3: it runs on init and would replace the suggested free slug with a taken one). Slug input: `x-model="slug"`, `@input="slugTouched = $event.target.value.trim() !== ''"`, `@blur="slug = window.slugify(slug)"`. Form: `x-form method="post" :action="$viewModel->action()"`. Footer actions mirroring the bottom of form.blade.php: a primary submit `__('Make the copy')` and a secondary link `__('Cancel')` to `$viewModel->cancelUrl()`. Check `resources/views/components` before writing markup and copy the footer markup of resources/views/app/settings/account/templates/form.blade.php
- [X] T007 [US1] In resources/views/app/settings/account/templates/_row.blade.php replace the `x-form` posting to `settings.templateCopy.create` (around line 60) with `<x-button.secondary size="small" :href="route('settings.templateCopy.new', $template['slug'])">{{ __('Copy') }}</x-button.secondary>`
- [X] T008 [US1] Run `php artisan test --compact tests/Unit/ViewModels/Settings/Account/Templates/TemplateCopyViewModelTest.php tests/Feature/Controllers/App/Settings/Account/Templates/TemplateCopyControllerTest.php` and fix until green. Then grep tests for `templateCopy.create` used from a list view assertion (for example in a `TemplatesViewModel` or index view test) and update any that expected the form

**Checkpoint**: US1 works end to end with valid input.

---

## Phase 4: User Story 2 - Be told why a slug is refused (Priority: P2)

**Goal**: A taken, malformed or overlong slug, or an empty name, is refused on its field with the values kept, and nothing is created.

**Independent Test**: Post a taken slug, then `Work Contact`, then an empty name; each returns to the form with an error on the right field and no new template.

### Tests for User Story 2

- [X] T009 [P] [US2] In tests/Feature/Controllers/App/Settings/Account/Templates/TemplateCopyControllerTest.php add: `it_refuses_a_slug_already_used_in_the_account` (an existing template with slug `scranton-branch`; post it; `assertSessionHasErrors('slug')`; template count unchanged); `it_refuses_a_slug_held_by_a_deleted_template`; `it_refuses_a_slug_with_capitals_or_spaces` (post `Scranton Branch`, `assertSessionHasErrors('slug')`); `it_refuses_an_empty_name` (`assertSessionHasErrors('name')`). Assert one refusal message verbatim: "Another template in this account already uses that slug."

### Implementation for User Story 2

- [X] T010 [US2] In `create()` of app/Http/Controllers/App/Settings/Account/Templates/TemplateCopyController.php, validate inline before calling the action: `'name' => ['required', 'string', 'max:255']`, `'slug' => ['nullable', 'string', 'max:'.Template::SLUG_LENGTH, 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('templates', 'slug')->where('account_id', $request->user()->account_id)]`, with messages `'slug.regex' => __('Use lowercase letters, numbers and dashes only, with no spaces and no accents.')` and `'slug.unique' => __('Another template in this account already uses that slug.')`, copied from `TemplateController::create()`. Pass `name: $validated['name']`, `slug: $validated['slug'] ?? null`. Repeat the rules inline; do not extract a shared helper (research R4)
- [X] T011 [US2] Run the controller test file and fix until green

**Checkpoint**: Both stories pass independently.

---

## Phase 5: Polish & Cross-Cutting Concerns

- [X] T012 [P] Rewrite Step 1 and Step 2 of docs/portal/en/9-templates-and-customisation/3-build-your-own-template.md: choosing **Copy** opens a form; type `Friends` in **Name** (the slug follows and becomes `friends`), then choose **Make the copy**. "Check your work" says Monica shows **The template is copied** and opens the builder of **Friends**, marked **Draft**, with `friends` in the address. Remove the rename step, renumber the following steps and any "Step N" references in the file. No dash used as punctuation. Keep existing screenshot references valid
- [X] T013 [P] Add a What's new entry with the `changelog` skill (type improvement): copying a template now asks for the name and the slug of the copy before it is made. Run `php artisan test --compact tests/Unit/Helpers/ChangelogEntriesTest.php`
- [X] T014 Run `php artisan monica:localize` to register the new strings, then have the translations written by Sonnet subagents per the `translations` skill and the memory rule; run `php artisan monica:localize --check`
- [X] T015 Run `vendor/bin/pint --dirty --format agent` and PHPStan as the `definition-of-done` skill specifies
- [X] T016 Run the full suite with `composer test:parallel`
- [X] T017 Run the `accessibility-reviewer` and `rules-reviewer` agents on the change and address confirmed findings
- [ ] T018 Walk through quickstart.md "By hand" steps 1 to 8 in the running app

---

## Dependencies & Execution Order

- **Phase 2 (T001)** blocks everything that renders or links to the new route (T003, T005, T006, T007).
- **US1**: T002, T003, T004 can start in parallel after T001. T005 depends on T004. T006 depends on T004 and T005. T007 depends on T001. T008 closes the story.
- **US2**: T010 edits the same method as T005, so it runs after T005. T009 can be written in parallel with T010. In practice T005 and T010 are one edit of `create()`.
- **Polish**: T012 and T013 can run any time after T006 fixes the labels. T014 needs every string in place (after T006, T010). T015 to T018 run last, in order.

## Parallel Example: User Story 1

```text
T002 TemplateCopyViewModelTest.php
T003 TemplateCopyControllerTest.php
T004 TemplateCopyViewModel.php
```

## Implementation Strategy

### MVP

T001, then US1 (T002 to T008) with T010 folded into T005 so the form never posts unvalidated input. That alone answers the customer.

### Incremental

1. US1: the form and the copy with chosen values.
2. US2: refusal tests prove the validation added with US1.
3. Polish: guide, What's new, translations, definition of done.

## Notes

- `DuplicateTemplate`, the API controller, the MCP tool and `resources/docs/api/11-templates.php` are not touched (FR-012). Their tests must still pass.
- No API changelog entry: the API does not change.
- No help bubble (research R7).
