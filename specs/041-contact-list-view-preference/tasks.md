---

description: "Task list for the contact list view preference"
---

# Tasks: Contact list view preference

**Input**: Design documents from `/specs/041-contact-list-view-preference/`

**Prerequisites**: [plan.md](./plan.md), [spec.md](./spec.md), [research.md](./research.md),
[data-model.md](./data-model.md), [contracts/](./contracts/)

**Tests**: included, and not optional. Constitution principle III is non-negotiable here: no change
ships without a test covering the behaviour it adds or alters, and the full suite passes before merge.

**Organization**: grouped by user story. Two things about the order are worth reading before starting.

- **The phases run in dependency order, not in priority order.** US2 (P2) ships before US1 (P1) because
  US1 cannot work without it: the redirect a new user gets sends them to `/contacts/all`, and that
  address answers `404` today. US2 is also shippable on its own, since it repairs the Names only chip.
- **The two controllers are each touched in several phases.** Nothing inside one file is parallel with
  anything else in that file.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: can run in parallel with the other `[P]` tasks of the same phase, once that phase's
  non-parallel prerequisites are done
- **[Story]**: US1, US2 or US3, matching the user stories in [spec.md](./spec.md)
- Every task names the file it touches

---

## Phase 1: Setup (shared schema)

**Purpose**: the three columns every story reads. The users migration is edited in place rather than
added to, which the migrations skill requires until the product is in production.

- [X] T001 Add three nullable columns to `database/migrations/0001_01_01_000000_create_users_table.php`, placed after `number_format` and before `uses_readable_addresses`: `$table->string('contact_list_grouping', 20)->nullable()` commented `grouping the people list was last looked at with, null until the user has looked at it`; `$table->string('contact_list_sort', 20)->nullable()` commented `field the people list was last sorted by, null until the user has looked at it`; `$table->string('contact_list_direction', 4)->nullable()` commented `direction the people list was last sorted in, null until the user has looked at it`. Only `up()` is written, as the file already does.
- [X] T002 Add three `@property ?string` lines to the class docblock of `app/Models/User.php`, one per column. Do **not** add them to `#[Fillable]`, and do **not** add them to `casts()`: an enum cast throws on a value that is no longer a case, which is exactly the fallback FR-012 requires, and [data-model.md](./data-model.md) carries the argument.
- [X] T003 Rebuild the database with `php artisan migrate:fresh --seed`. An edited `Schema::create` never reaches a database that already ran it, so nothing short of a rebuild picks the columns up. The rebuild also regenerates any contact slugged `all`, which T014 turns into a reserved word.

**Checkpoint**: `php artisan db:table users` lists the three columns.

---

## Phase 2: Foundational (blocking prerequisites)

**Purpose**: the sort direction stops being a bare string, and the view model stops deciding whether an
address states its grouping. Every story below depends on both.

**No user story work begins until this phase is complete.**

- [X] T004 [P] Create `app/Enums/SortDirectionEnum.php`, a backed string enum with cases `Ascending = 'asc'` and `Descending = 'desc'`, carrying `fromValueOrAscending(?string $value): self` and `values(): array` that mirror `ContactGroupingEnum::fromValueOrEveryone()` and `ContactSortEnum::values()` beside it. It implements no `Translatable` and carries no `label()`: what a direction is called depends on the sort it belongs to, and that pairing stays in the view model.
- [X] T005 [P] Create `tests/Unit/Enums/SortDirectionEnumTest.php` proving the two cases, `values()`, and that `fromValueOrAscending()` answers `Ascending` for null, for an empty string and for a spelling that is no longer a case.
- [X] T006 Change `Contact::scopeSortedBy()` in `app/Models/Contact.php` to take `SortDirectionEnum $direction`, and delete its `$direction === 'desc' ? 'desc' : 'asc'`, which the type now settles. Pass `$direction->value` to `orderBy()`.
- [X] T007 [P] Update the four `sortedBy()` calls in `tests/Unit/Models/ContactTest.php` (around lines 391, 404, 405 and 419) to pass `SortDirectionEnum` cases instead of `'asc'` and `'desc'`.
- [X] T008 [P] In `app/Http/Controllers/Api/ContactController.php`, replace `Rule::in(['asc', 'desc'])` with `Rule::enum(SortDirectionEnum::class)` and pass `SortDirectionEnum::fromValueOrAscending($validated['direction'] ?? null)` to `sortedBy()`. The accepted values do not change, so the published contract does not either.
- [X] T009 [P] Make the same two changes in `app/Mcp/Tools/ListContacts.php`, which holds the same rule and the same loose string. The tool exposes the API and holds no rule of its own, so nothing else about it changes.
- [X] T010 Rework `app/ViewModels/Vaults/ContactsViewModel.php`: take `ContactGroupingEnum $grouping`, `ContactSortEnum $sort` and `SortDirectionEnum $direction` in the constructor instead of strings; delete `DIRECTION_ASC`, `DIRECTION_DESC`, the private `grouping()` and `sortOrder()`, and `segmentFor()`; make `url()` and `searchUrl()` state the grouping in every address they build, Everybody included.
- [X] T011 Update `app/Http/Controllers/App/Vaults/ContactController.php@index` to resolve the three enums from the query string and hand them to the view model. This phase resolves from the address alone; the record and the redirect arrive in Phase 4 and Phase 5.
- [X] T012 Update `tests/Unit/ViewModels/Vaults/ContactsViewModelTest.php` for the new constructor, and add a case proving that every address `filters()` builds states a grouping, `all` included.

**Checkpoint**: `php artisan test --compact --filter=ContactsViewModelTest` and `--filter=ContactTest` pass, and the People list still renders.

---

## Phase 3: US2, every grouping has an address of its own (Priority P2, ships first)

**Goal**: all four groupings are addressable, bookmarkable and shareable, and the Names only chip stops
leading to a page that cannot be found.

**Independent test**: open each of the four addresses directly, with nothing recorded, and see the
matching grouping. Nothing in this phase remembers anything.

- [X] T013 [US2] In `routes/web.php`, change the `contacts.index` constraint from the two values spelled out by hand to `->whereIn('filter', ContactGroupingEnum::values())`, so `all` and `names-only` stop falling through to `contacts.show`. The route stays declared above `vaults/{vault}/contacts/{contact}/{page?}`, which is what makes the literal segments win.
- [X] T014 [P] [US2] Add `ContactGroupingEnum::Everyone->value` to `ReservedAddressWords::forContact()` in `app/Helpers/ReservedAddressWords.php`, and correct its docblock, which names only `favourites` and `archived` as route words when all four groupings now sit where a contact's own address would.
- [X] T015 [P] [US2] Extend `tests/Unit/Helpers/ReservedAddressWordsTest.php`: `all` is among the contact words, and the list holds every value of `ContactGroupingEnum`.
- [X] T016 [US2] Extend `tests/Feature/Controllers/App/Vaults/ContactControllerTest.php` with a case per grouping, each proving the right people come back: `it_lists_everybody_at_its_own_address`, `it_lists_the_favourites`, `it_lists_the_archived`, `it_shows_the_people_written_down_as_a_first_name_only`. Add `it_does_not_find_a_contact_list_at_a_word_that_is_not_a_grouping` for the `404`.
- [X] T017 [US2] Extend `tests/Feature/Views/Vaults/RelationshipsTest.php` so the Names only link it already asserts is followed rather than only seen, which is what proves the defect is gone.

**Checkpoint**: the four addresses answer `200`, and the Names only chip works from both the People screen and the relationships panel.

---

## Phase 4: US1, the People list opens where I left it (Priority P1)

**Goal**: the grouping, sort field and direction somebody last looked at are recorded, and the bare
address opens on them.

**Independent test**: choose a view, leave, come back to the bare address in a new session, and find the
three choices as they were left.

- [X] T018 [P] [US1] Create `app/Actions/UpdateContactListView.php`: promoted readonly `User $user`, `ContactGroupingEnum $grouping`, `ContactSortEnum $sort`, `SortDirectionEnum $direction`, one public `execute(): User`. It assigns the three columns their `->value` and saves, and it returns early without saving when all three already match. It dispatches no `LogUserAction`: this is written as a side effect of looking at a list, so logging it would put one journal entry per visit to People. One class docblock of a sentence or two, and nothing on the private methods.
- [X] T019 [P] [US1] Create `tests/Unit/Actions/UpdateContactListViewTest.php`: it writes the three columns, proved with `assertDatabaseHas()`; it writes nothing when all three already match; it returns the user; and `Queue::assertNothingPushed()` holds. Use named arguments and The Office for the data.
- [X] T020 [US1] In `app/Http/Controllers/App/Vaults/ContactController.php@index`, return `View|RedirectResponse`. When the address states no grouping, redirect to `route('contacts.index', ...)` stating the resolved grouping in the path and the sort and direction in the query string, and record nothing. `query` and `page` are not carried into the redirect: a search is not part of the view, and a page number means nothing until the list is known.
- [X] T021 [US1] In the same method, call `UpdateContactListView` when the address states a grouping, before the view model is built.
- [X] T022 [US1] Extend `tests/Feature/Controllers/App/Vaults/ContactControllerTest.php`: `it_shows_everybody_when_nothing_is_recorded` (the bare address redirects to `all`, `name`, `asc`), `it_redirects_the_bare_address_to_the_recorded_view`, `it_records_the_grouping_the_address_states`, `it_records_the_sort_and_the_direction_the_address_states`, `it_applies_the_recorded_view_in_every_vault_of_the_account`, and `it_records_nothing_for_a_contact_list_in_another_account`.

**Checkpoint**: steps 1 to 4 and step 7 of the walkthrough in [quickstart.md](./quickstart.md) pass by hand.

---

## Phase 5: US3, a stated address wins, and prefetching does not record (Priority P3)

**Goal**: following a link to a grouping shows it and records it, whatever was remembered; a choice the
address leaves out keeps its remembered value; and a browser preloading a link changes nothing.

**Independent test**: record one view, then open an address stating a different grouping and no sort.
The grouping changes, the sort does not, and hovering that link without clicking changes neither.

- [X] T023 [US3] In `app/Http/Controllers/App/Vaults/ContactController.php@index`, make each of the three choices resolve as: the value the address states, else the value recorded on the user, else the default. The three lines are in [data-model.md](./data-model.md). An address stating only a grouping is rendered as it is, with no second redirect to append the sort, so `/vaults/{vault}/contacts/names-only` stays a working permalink.
- [X] T024 [US3] In the same method, skip the `UpdateContactListView` call when `$request->prefetch()` is true. `Illuminate\Http\Request::prefetch()` reads `X-Moz`, `Purpose` and `Sec-Purpose`, which covers the browsers that implement `rel=prefetch`. Without this, hovering the Names only link in the relationships panel records Names only, because instant.page preloads it: that link carries no query string, and instant.page skips only links that do. The argument and the line references are in [research.md](./research.md).
- [X] T025 [US3] Extend `tests/Feature/Controllers/App/Vaults/ContactControllerTest.php`: `it_shows_the_grouping_the_address_states_over_the_recorded_one`, `it_keeps_the_recorded_sort_when_the_address_states_only_a_grouping`, `it_falls_back_to_everybody_when_the_recorded_grouping_is_not_a_grouping` (write a spelling straight to the column that is no longer a case, and prove the other two choices survive), and `it_records_nothing_when_the_request_is_a_prefetch`, which sends `Sec-Purpose: prefetch` and asserts the columns are untouched.

**Checkpoint**: step 5 and step 6 of the walkthrough pass. Step 6 is the one that fails silently if T024 is dropped.

---

## Phase 6: API and MCP coverage

**Purpose**: principle X. A capability is not complete until its API coverage exists and is tested.

**Order matters here**: `ApiDocsReturnFieldsTest` proves that every field every endpoint answers with is
documented, so T026 fails the suite until T029 lands. Do them together.

- [X] T026 [US1] Add `contact_list_grouping`, `contact_list_sort` and `contact_list_direction` to the `attributes` of `app/Http/Resources/UserResource.php`, beside `number_format`, each the stored string or `null`.
- [X] T027 [US1] Create `app/Http/Controllers/Api/MeContactListViewController.php` with one `update()` method, modelled on `MeTimezoneController` beside it: validate `grouping`, `sort` and `direction`, all `['required', 'string', Rule::enum(...)]` against `ContactGroupingEnum`, `ContactSortEnum` and `SortDirectionEnum`; call `UpdateContactListView`; return `new UserResource($request->user()->refresh())`. All three are required rather than optional, and [contracts/api.md](./contracts/api.md) says why.
- [X] T028 [US1] Declare `Route::put('me/contact-list-view', [MeContactListViewController::class, 'update'])->name('me.contactListView.update');` in `routes/api.php`, beside `me/timezone`.
- [X] T029 [US1] Document it in `resources/docs/api/03-profile.php`: the three attributes join the `$user` sample and the `$userAttributes` table, and the endpoint gets a section beside the time zone one. `BuildOpenApiDocument` reads these files, so the OpenAPI document follows with no separate task.
- [X] T030 [P] [US1] Create `tests/Feature/Controllers/Api/MeContactListViewControllerTest.php`: the write lands and the user comes back with it, each of the three fields fails with `assertUnprocessable()` and `assertJsonValidationErrors()` when it is missing or not a case, and an unauthenticated call is refused. Authenticate with `Sanctum::actingAs($user)` and call through `$this->json(...)`.
- [X] T031 [P] [US1] Add the three attributes to the `$jsonStructure` property of `tests/Feature/Controllers/Api/MeControllerTest.php` and assert their values with `assertJsonPath()`.

**Checkpoint**: `php artisan test --compact --filter=ApiDocs` and `--filter=MeContactListViewControllerTest` pass.

---

## Phase 7: Polish and the quality gates

**Purpose**: what the constitution requires before the change is considered done.

- [X] T032 Run `php artisan monica:localize --check`. Nothing is expected: the four chip labels, "Sorted by" and both direction wordings already exist. If it reports anything, run `php artisan monica:localize` and translate it in every configured locale, never by editing `lang/*.json` by hand.
- [X] T033 Run `vendor/bin/pint --dirty --format agent` and fix what it reports.
- [X] T034 Run `vendor/bin/phpstan analyse` and fix what it reports. `index()` returning `View|RedirectResponse` is the likely finding.
- [X] T035 Run the whole suite with `php artisan test --compact`. `Contact::scopeSortedBy()` and the `ContactsViewModel` constructor both changed signature, so the blast radius is wider than the six files this feature owns.
- [X] T036 Walk the People screen for principle IX: keyboard alone, focus visible on each chip, the chip in force announced through `aria-current`, no information carried by colour alone, readable zoomed to 200%, and usable at desktop, tablet and phone widths.
- [X] T037 Walk [quickstart.md](./quickstart.md) end to end in a browser, including the API section, which is what proves the two surfaces read one value.

---

## Dependencies

```
Phase 1 (T001-T003)
  └─ Phase 2 (T004-T012)          T006 before T007, T008, T009. T010 before T011, T012.
       └─ Phase 3, US2 (T013-T017)
            └─ Phase 4, US1 (T018-T022)
                 └─ Phase 5, US3 (T023-T025)
                      └─ Phase 6, API (T026-T031)
                           └─ Phase 7 (T032-T037)
```

**Why the stories are not in priority order**: US1 needs an address to redirect to. A user who has
recorded nothing is sent to `/vaults/{vault}/contacts/all`, which answers `404` until T013 lands. US2 is
therefore first, and it is a shippable increment on its own.

**Files touched by more than one phase**, and never in parallel with themselves:

| File | Phases |
| --- | --- |
| `app/Http/Controllers/App/Vaults/ContactController.php` | 2 (T011), 4 (T020, T021), 5 (T023, T024) |
| `tests/Feature/Controllers/App/Vaults/ContactControllerTest.php` | 3 (T016), 4 (T022), 5 (T025) |

## Parallel opportunities

- **Phase 2**: T004 and T005 together. Then T006, and after it T007, T008 and T009 together.
- **Phase 3**: T014 and T015 together, alongside T013.
- **Phase 4**: T018 and T019 together, before T020.
- **Phase 6**: T030 and T031 together, after T026 to T029.

Phases 5 and 7 hold no parallel work: Phase 5 is two edits to one method and the tests that prove them,
and Phase 7 is a sequence of gates.

## Implementation strategy

**The smallest thing worth shipping** is Phase 1 to Phase 3. It repairs a control that is on the screen
and does not work, it needs no new column to be read, and it stops there.

**The request as asked** is Phase 1 to Phase 5. Everything the user described works: the addresses are
addresses, and the view is remembered.

**Done, by the constitution's definition**, is Phase 1 to Phase 7. Principle X wants the endpoint and
its test, principle V wants the translations run, principle IX wants the screen walked, and the CI gate
wants Pint, PHPStan and the full suite.

## What the implementation changed about the plan

Two things worth recording, both found while doing the work.

- **T023's resolution landed inside T020.** Resolving a choice from the address, then the record, then
  the default is one expression, and the redirect in T020 needs it as much as the render does. Splitting
  it across two phases would have meant writing it twice. T024, the prefetch guard, stayed its own
  piece of work and is what Phase 5 actually delivered.
- **`ReservedAddressWordsTest` was not reading the hyphen.** Its regex for a route constraint was
  `/^[a-z]+(\|[a-z]+)*$/`, which cannot match `all|favourites|archived|names-only`, so the test that
  exists to catch an unreserved route word was silently skipping the contact list. Widening it to accept
  a hyphen is what makes that test able to catch this class of defect at all, and it is why
  `names-only` went unnoticed in the first place.

Three tests fail on this branch and failed before any of this work:
`VaultControllerTest::it_opens_a_vault_whose_name_is_written_in_another_alphabet`,
`App\Vaults\ContactControllerTest::it_opens_a_contact_named_in_another_alphabet_written_either_way`
and `Api\ContactControllerTest::it_reaches_a_contact_named_in_another_alphabet_written_either_way`.
All three answer 400 on an address carrying a multibyte slug. Verified by stashing this branch's
changes and running them. They are not this feature's, and nothing here touches them.

## Found after the tasks were written

- **The chips are Alpine Ajax targets, and a plain `x-target` does not touch the address bar.** Every
  task above was proved with feature tests, which exercise the HTTP layer, so all of them passed while
  clicking a chip in a browser changed the list and left the address behind. The three controls now
  carry `x-target.push`, which is what the reminders screen and the life event types rail already use.
  `it_moves_the_address_bar_with_the_view` covers it, and it fails against the old markup.
- **Back to the entry the page first loaded on does not re-render.** Alpine Ajax reloads on a popped
  state it wrote itself and ignores one it did not, and a full page load leaves a null state. Going
  back from a pushed view to the one the page opened on therefore moves the address and leaves the
  list where it was. This is how every `x-target.push` in the application already behaves, so it is not
  this feature's to fix here, but it is worth a decision of its own.
