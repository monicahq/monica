# Test suite audit

Started 2026-10-28. The suite holds 7,811 tests in 1,019 files, and CI takes between 12 and 15 minutes
on MySQL and PostgreSQL. This file records what the audit found, area by area, what to keep, what to
retire, and the guidelines that follow from it. It is written as the audit goes, so the sections grow.

## Summary

Eleven areas were read, one agent each, between them 7,795 of the 7,811 test methods. Every finding
an agent reported was spot checked against the files by the coordinator; the checks and the six
corrections they produced are quoted under each section.

| Area | Tests | Keep | Duplicate | Cosmetic | Declaration | Low value | Removable |
| --- | --- | --- | --- | --- | --- | --- | --- |
| Feature/Controllers/App/Vaults | 477 | 240 | 115 | 15 | 8 | 99 | 237 (50%) |
| Feature/Controllers/App/Settings | 588 | 359 | 100 | 26 | 20 | 83 | 229 (39%) |
| Feature/Controllers/App Admin, Auth, Docs + Feature/Auth | 393 | 239 | 72 | 6 | 6 | 70 | 154 (39%) |
| Feature/Controllers/Api | 696 | 508 | 93 | 0 | 2 | 93 | 188 (27%) |
| Feature/Views and the other Feature tests | 600 | 389 | 74 | 40 | 15 | 82 | 211 (35%) |
| Unit/Actions A to E | 775 | 701 | 16 | 0 | 4 | 54 | 74 (10%) |
| Unit/Actions F to Z | 1,045 | 1,007 | 7 | 0 | 1 | 30 | 38 (4%) |
| Unit/ViewModels | 1,079 | 927 | 33 | 17 | 22 | 80 | 152 (14%) |
| Unit/Mcp + Feature/Mcp | 391 | 231 | 28 | 0 | 93 | 39 | 160 (41%) |
| Unit/Models, Enums, Helpers | 1,012 | 736 | 15 | 1 | 188 | 72 | 276 (27%) |
| Unit, the rest | 739 | 584 | 21 | 2 | 96 | 36 | 155 (21%) |
| **Total** | **7,795** | **5,921** | **574** | **107** | **455** | **738** | **1,874 (24%)** |

The coordinator corrections move about ten tests back to Keep, which does not change the picture.

### What this says

- **The controllers carry the redundancy.** Four controller areas hold 808 of the 1,874 removals, and
  a controller test is the most expensive kind (60 ms on sqlite, a full request and a page render).
  The pattern is the same everywhere: the controller test re-proves what the action or the view model
  test already proves (copy, order, paging, every refusal, the journal write).
- **The actions are healthy.** 4 to 10 percent removable, mostly "leaves the column alone" mirrors and
  separate journal tests that `testing.md` already says to fold in.
- **Declarations cluster in three places.** MCP (91 identical `mirrors` tests), Enums (89 of 147 restate
  a `match`) and Models (about 95 uuid, route key, soft delete, factory default tests). Each collapses
  into one reflection or data provider test.
- **Cosmetic tests are few (107)** and sit in Feature/Views, Settings and the view models. 14 component
  test files are look only and go whole.

### What it would save, estimated

Not measured; the estimate uses the per area milliseconds of the local sqlite run above.

| Lever | sqlite CPU saved |
| --- | --- |
| 808 controller tests at about 60 ms | about 48 s |
| 1,066 other tests at about 35 ms | about 37 s |
| `LocalizeCommandTest` rewritten against a fixture directory | about 28 s |
| Search tests that need no engine moved to the database driver | about 8 s |
| **Total** | **about 120 s of 371 s, a third** |

On MySQL the database cost per test is higher, so a third of the 11 min 17 s `Run tests` step, about
4 minutes, is a fair expectation and not a promise. Deleting tests alone does not get the pull request
under 5 minutes; the workflow levers (sqlite on pull requests, sharding) do, and they compound with it.

### Coverage gaps found on the way

These are not removals. They are assurance the suite believes it has and does not.

1. `ResolvesModelInVaultTest` has no test of the vault mismatch branch (line 104 of the trait). Add it
   before deleting any controller wrong vault test.
2. Nothing tests that the `password.confirm` routes ask for the password as a group. `RouteProtectionTest`
   does not mention it. Add one data provider test before deleting the 15 per route copies in Settings
   and the 7 in Auth.
3. The database search tests skip themselves unless the driver is sqlite, and CI runs MySQL only, so
   `SearchRecordsTest` lines 111 and 126 and `MaterializedDatabaseEngineTest` line 137 never run in CI.
4. The step that boots the application without `socialiteproviders/microsoft` is guarded by sqlite and
   never runs.
5. `monica:localize --check` is enforced only through `LocalizeCommandTest`. Keep one full run when that
   file is rewritten.

### Order of work

1. Close the five gaps above. Each is one small test or one workflow line.
2. Decide the workflow (sqlite on pull requests, MySQL on `main`; or sharding). This is the largest win
   and touches no test.
3. Rewrite `LocalizeCommandTest` and move the engine free search tests to the database driver.
4. Delete, area by area, one pull request per area, starting with the controllers. Each deletion
   pass opens the owning test it cites, since several agents judged duplicates from method names.
5. Collapse the declarations (MCP `mirrors`, enums, models, notifications) into reflection tests.
6. Amend the testing rules below, so the suite does not grow back.

## Where the time goes

Run 40791138732 on GitHub, the latest green one on main, step `Run tests`:

| Job | Suite duration |
| --- | --- |
| sqlite (in memory) | 4 min 46 s |
| mysql | 11 min 17 s |
| pgsql | 14 min 05 s |

The three jobs run the same 7,811 tests, so the gap between sqlite and the two servers is the cost of
the database per test, not the number of assertions. 883 of the 1,019 test files declare
`RefreshDatabase`, which wraps every test in a transaction and runs the migrations once per Paratest
process. Reducing the number of database-backed tests is what shortens the two slow jobs; reducing pure
unit tests barely moves them.

The GitHub runner has 4 cores. Paratest uses one process per core. The same suite on a 10 core laptop
with sqlite in memory runs in 42 seconds, which says that the wall clock on CI is set by the database
server and by the core count, and that counting tests alone understates what the slow jobs cost.

### CPU time per area, local run with sqlite in memory

371 seconds of test time across 7,940 tests, 47 ms per test on average. The ranking is what matters,
since on MySQL and PostgreSQL every database-backed test costs more.

| Area | Tests | Seconds | ms per test |
| --- | --- | --- | --- |
| Unit/Actions | 1,828 | 105 | 57 |
| Feature/Controllers/App | 1,413 | 85 | 60 |
| Unit/ViewModels | 1,083 | 39 | 36 |
| Unit/Console | 105 | 33 | 316 |
| Feature/Controllers/Api | 699 | 26 | 37 |
| Feature/Views | 483 | 15 | 32 |
| Unit/Models | 502 | 14 | 27 |
| Unit/Jobs | 172 | 11 | 66 |
| Unit/Helpers | 412 | 8 | 19 |
| Unit/Mcp | 319 | 8 | 24 |
| Everything else | about 640 | 27 | |

Two files stand out on their own. `tests/Unit/Console/Commands/LocalizeCommandTest.php` takes 31
seconds for 55 tests, because every test runs the real `monica:localize` command, which scans every
PHP and Blade file of the application. `tests/Unit/Actions/SearchRecordsTest.php` and
`tests/Feature/Controllers/Api/SearchControllerTest.php` take 10 and 4 seconds for 21 tests, because
they talk to a search engine. Together these three files are 12 percent of the suite's CPU time for
0.9 percent of its tests.

## Method

Every test method in the suite is read and put into one of five buckets.

| Bucket | Meaning |
| --- | --- |
| Keep | Proves a rule in the layer that owns it. Nothing else would catch the defect. |
| Duplicate | The owning layer already proves it. |
| Cosmetic | Asserts how something is drawn. `testing.md` forbids it. |
| Declaration | Restates a declaration (an enum lists its cases, a relationship exists) or is one of N identical conformance tests a single reflection test replaces. |
| Low value | Catches a defect nobody introduces, or a third variant of an already covered branch. |

The ownership of a rule follows the table in `.claude/rules/testing.md`: action, view model,
controller, `RouteProtectionTest`.

## What the workflow itself costs

Read from `.github/workflows/tests.yml` on 2026-10-28. These levers do not touch a single test, and
some of them move the wall clock more than deleting tests would.

- **One job runs today, on MySQL.** The matrix lists `connection: [mysql]`, with sqlite and pgsql
  commented out. The 12 minutes are therefore the MySQL job alone.
- **A step that never runs.** `Run tests without the suggested provider packages` is guarded by
  `matrix.connection == 'sqlite'`, and sqlite is no longer in the matrix. The assurance it was written
  for (the application boots without `socialiteproviders/microsoft`) is not checked at all at the
  moment. Either restore it on the job that runs, or delete the step.
- **The same suite on sqlite takes 4 min 46 s** on the same runner. Running sqlite on every pull
  request and MySQL (and PostgreSQL) on `main` or nightly would cut the pull request wait by about
  60 percent without deleting anything. The risk is a MySQL only defect reaching `main`; it is caught
  there, one merge later. This is a decision for the maintainer, not a finding.
- **Sharding.** Paratest runs one process per core on a 4 core runner. Splitting the suite across two
  or three matrix jobs (for example `Unit/Actions`, the rest of `Unit`, and `Feature`) divides the
  `Run tests` step roughly by the number of jobs, at the cost of repeating the setup steps (about
  one minute each, not measured precisely). PHPUnit has no built in shard option, so the split is by
  `--testsuite` or by directory.
- **More processes than cores.** On MySQL a process spends much of its time waiting on the server, so
  `--processes=8` on 4 cores may help. Not measured; worth one run before deciding.

## Feature/Controllers/App/Vaults

Files read in full: the 39 files under tests/Feature/Controllers/App/Vaults and the five top level files HomeControllerTest, NotFoundControllerTest, NotificationControllerTest, SharedContactControllerTest and WelcomeControllerTest (44 files, 477 `#[Test]` methods by `grep -c`). The owning layers I opened for citations: tests/Unit/Actions (about 40 action tests), tests/Unit/ViewModels/Vaults, tests/Unit/ViewModels/Shared, tests/Unit/ViewModels/NotificationBarViewModelTest, tests/Feature/RouteProtectionTest.php, tests/Unit/Models/Concerns/ResolvesModelInVaultTest.php, ContactTest, VaultTest, PageSizeTest, PublishedContactStructureVersionTest and the component tests (PaginationTest, ListPanelTest, FieldInputTest, FieldReminderTest, ActionsMenuTest). Per testing.md a controller test here should prove four things per route: it answers, inline validation refuses what it should, the response has its shape (redirect and flash, or fragment hooks), and one refusal from the action reaches the reader. Everything about what a list holds, groups, orders or says belongs to the view model. Everything an action writes belongs to the action. The two big files (ContactControllerTest, ContactSectionControllerTest) and VaultControllerTest, VaultReminderControllerTest, EntityControllerTest and ContactRelationshipControllerTest re-prove what those layers already prove.

### Totals
| Bucket | Tests |
| --- | --- |
| Keep | 240 |
| Duplicate | 115 |
| Cosmetic | 15 |
| Declaration | 8 |
| Low value | 99 |
| Total | 477 |

### Findings by file
Paths are relative to tests/. Files not listed (HomeControllerTest, NotFoundControllerTest, ContactLifeEventRestorationControllerTest, FieldChoiceControllerTest, VaultLifeEventCandidateControllerTest) have nothing to remove.

| File | Tests | Remove | Bucket(s) | Why, citing the owning test when Duplicate |
| --- | --- | --- | --- | --- |
| Feature/Controllers/App/Vaults/ContactControllerTest.php | 88 | 51 | Declaration (2), Low value (20), Cosmetic (6), Duplicate (23) | ContactsViewModelTest owns chips, search, order, paging and empty states. ContactFormViewModelTest owns the form, its ceiling and its disabled state. ContactViewModelTest owns the version history, the star and the timeline. ContactTest owns slugs. PublishedContactStructureVersionTest owns the template rule. ActionsMenuTest owns the menu contract. PageSizeTest owns the remembered size. Fifteen tests assert a link, an aria attribute, a count of hrefs or an SVG fill. |
| Feature/Controllers/App/Vaults/VaultControllerTest.php | 39 | 28 | Low value (12), Cosmetic (4), Duplicate (12) | Dashboard facts: VaultDashboardViewModelTest and VaultDashboardAttentionTest. Template choice: CreateVaultTest::it_gives_the_vault_the_template_new_contacts_start_from and PublishedContactStructureVersionTest::it_refuses_the_version_of_another_account. Ceiling: VaultFormViewModelTest::it_names_the_ceiling_once_the_account_is_at_it and CreateVaultTest::it_keeps_what_an_account_over_the_ceiling_already_has. Addresses: VaultTest (readable address, identifier, alphabet). Fifteen tests assert only that a link, a logo or an aria attribute is on the screen. |
| Feature/Controllers/App/Vaults/ContactSectionControllerTest.php | 41 | 28 | Duplicate (21), Low value (6), Declaration (1) | SetFieldValuesTest owns address, label, country, file, repetition and reminder writes. FieldInputTest owns the boxes of an address and the names inside a repetition. FieldReminderTest and ContactReminderBlockTest own the reminder panel. ContactViewModelTest owns notes. See the list for the single test cited per method. |
| Feature/Controllers/App/Vaults/VaultReminderControllerTest.php | 18 | 13 | Duplicate (10), Cosmetic (2), Low value (1) | Grouping, paging, answers and order: VaultRemindersViewModelTest, VaultRemindersHistoryViewModelTest, VaultRemindersSentViewModelTest and ReminderRowTest. Two tests assert tab order or a repeated heading count. The paging test creates 26 reminders. |
| Feature/Controllers/App/Vaults/EntityControllerTest.php | 21 | 13 | Duplicate (8), Low value (5) | EntitiesViewModelTest (paging, empty state, turned off), PageSizeTest::it_pages_a_query_at_the_size_asked, SetFieldValuesTest (caregivers, other vault, required field), CreateEntityTest (turned off type, name emptied once cleaned). Wrong vault on the form is the "type hidden in this vault" check again. |
| Feature/Controllers/App/Vaults/ContactRelationshipControllerTest.php | 24 | 12 | Duplicate (7), Low value (5) | CreateRelationshipTest (swapped, regular contact, two unknown people, other vault), UpdateRelationshipTest::it_renames_a_partial_contact_on_the_other_side, RelationshipFormViewModelTest::it_chooses_the_type_it_was_opened_with, RelationshipsBlockTest::it_gathers_the_links_under_their_categories_in_the_accounts_order. Four tests are the trait branch or a variant of a refusal already proven. |
| Feature/Controllers/App/Vaults/ContactFavouriteControllerTest.php | 13 | 9 | Low value (7), Duplicate (2) | Journal entries: FavouriteContactTest::it_pins_a_contact_to_the_top_of_the_list and UnfavouriteContactTest::it_takes_a_contact_off_the_top_of_the_list. The rest are second variants of mark, clear, flash and validation, plus the wrong vault case. Four tests stay: mark, confirm, clear, refuse a missing state. |
| Feature/Controllers/App/Vaults/ContactShareLinkControllerTest.php | 22 | 9 | Low value (7), Duplicate (2) | UpdateShareLinkTest::it_takes_the_password_away_when_asked. ContactShareLinksViewModelTest::it_shows_twenty_links_a_page_newest_first_with_the_revoked_ones_kept plus PaginationTest. The rest are empty state copy, a link on a screen, a second validation variant or the trait branch. |
| Feature/Controllers/App/Vaults/ContactMergeControllerTest.php | 13 | 7 | Duplicate (4), Low value (3) | The picker: ContactMergeViewModelTest::it_offers_every_other_contact_of_the_vault, ::it_narrows_the_candidates_to_the_typed_name, ::it_pages_the_candidates_twenty_at_a_time. The absorbed address: AbsorbedContactControllerTest::the_address_of_an_absorbed_contact_names_who_absorbed_them. The paging test creates 26 contacts. Three tests are the trait branch. |
| Feature/Controllers/App/Vaults/ReminderControllerTest.php | 8 | 6 | Duplicate (3), Cosmetic (1), Low value (2) | ReminderViewModelTest::it_edits_a_written_reminder_on_its_own_form_and_names_no_source, ::it_explains_every_reason_a_reminder_stops and ::it_pages_the_delivery_log_fifty_at_a_time_on_its_own_page_name. The delete card is the DeleteAction contract. The test that pages the delivery log inserts 60 rows. Two tests are the trait branch. |
| Feature/Controllers/App/Vaults/ContactReminderControllerTest.php | 17 | 6 | Duplicate (3), Low value (3) | ContactRemindersViewModelTest::it_says_what_an_empty_filter_would_hold, ::it_reads_a_filter_nobody_offers_as_all, ::the_history_lists_the_latest_answer_first_and_keeps_a_deleted_reminder_s_answer, ::the_history_shows_the_newest_twenty_five_answers_and_counts_them_all. The history paging test inserts 26 deadlines. The rest are the trait branch or a second validation variant. |
| Feature/Controllers/App/Vaults/ContactNameControllerTest.php | 12 | 5 | Duplicate (2), Low value (3) | The name order: UpdateContactNameTest::it_changes_how_a_contact_is_called. The ways of writing: UpdateContactNameRepresentationsTest::it_creates_updates_and_deletes_the_ways_a_name_is_written_in_one_go. Slugs are ContactTest. One test is the trait branch. |
| Feature/Controllers/App/SharedContactControllerTest.php | 12 | 3 | Duplicate (2), Declaration (1) | Sensitive fields: SharedContactViewModelTest::a_field_marked_sensitive_stays_hidden_inside_a_shared_section. Visit counting: OpenShareLinkTest::it_counts_the_opening_and_notes_when_it_happened and ::it_writes_the_visit_down. The token shape is a route constraint (declaration). |
| Feature/Controllers/App/NotificationControllerTest.php | 9 | 3 | Duplicate | Paging: NotificationBarViewModelTest::it_reads_the_newest_twenty_five_and_points_at_the_next_page and ::the_second_page_holds_the_rest_and_points_nowhere; the footer rules: ListPanelTest::it_offers_more_only_while_there_is_more and ::it_ends_the_list_only_when_asked_to_and_only_on_the_last_page. Each test inserts 30 rows one by one. |
| Feature/Controllers/App/Vaults/ContactLifeEventControllerTest.php | 14 | 3 | Duplicate (1), Low value (2) | Participant in another vault: CreateLifeEventTest::it_refuses_a_participant_of_another_vault. Two wrong vault tests are the trait branch. |
| Feature/Controllers/App/Vaults/ContactVCardControllerTest.php | 4 | 3 | Duplicate (1), Low value (1), Declaration (1) | Sensitive values: ExportContactVCardTest::it_carries_a_sensitive_value_when_the_reader_reaches_them. Flag off: EnsureFlagIsOnTest::it_answers_not_found_while_the_flag_is_off and ContactViewModelTest::it_offers_the_vcard_only_while_vcard_export_is_on_for_the_account. Wrong vault is the trait branch. |
| Feature/Controllers/App/Vaults/NotesNewestFirstTest.php | 3 | 3 | Duplicate (2), Declaration (1) | The default template declaring Notes newest first: CreateDefaultTemplateTest::it_makes_the_notes_collect_at_the_top_and_leaves_the_rest_collecting_at_the_end. The page it sits on: ::it_reads_the_template_in_the_pages_the_product_supplies. The mechanism: RepetitionOrderTest::it_reads_a_block_in_the_same_order_it_offers_it_as_a_form and ::it_draws_a_blank_before_what_is_there_when_the_repeater_collects_at_the_front. Each test runs CreateDefaultTemplate. The whole file can go. |
| Feature/Controllers/App/Vaults/ReadableAddressPreferenceTest.php | 6 | 3 | Low value (1), Declaration (1), Duplicate (1) | The preference toggle: UpdatePreferencesTest::it_turns_readable_addresses_off_and_on and PreferenceControllerTest. One test never calls the API despite its name and asserts a slug only. One is the mirror of the test before it. |
| Feature/Controllers/App/Vaults/ReminderOccurrenceTestNotificationControllerTest.php | 5 | 3 | Duplicate (2), Low value (1) | SendTestReminderNotificationTest::it_never_reaches_the_list_of_what_was_sent covers the first two. The third is the trait branch. |
| Feature/Controllers/App/Vaults/VaultLifeEventControllerTest.php | 8 | 3 | Low value | Flash wording and participant position variants of the first happy path. The old input test repeats what the "type can no longer be chosen" test asserts. |
| Feature/Controllers/App/Vaults/VaultSettingsControllerTest.php | 6 | 3 | Cosmetic (1), Duplicate (2) | The deletion copy: VaultSettingsViewModelTest::it_says_what_a_deletion_takes_with_it and ::it_knows_when_the_vault_is_the_only_one_the_account_has. The navigation test asserts an aria-label and an element id. |
| Feature/Controllers/App/Vaults/AbsorbedContactControllerTest.php | 7 | 2 | Low value (1), Cosmetic (1) | One test asserts a page does not contain the word "Location". One asserts a bare `<a>` tag and the absence of `onclick=`. |
| Feature/Controllers/App/Vaults/ContactAvatarControllerTest.php | 6 | 2 | Declaration (1), Low value (1) | Flag wiring of a route is a declaration, and EnsureFlagIsOnTest owns the middleware. The wrong vault case is the ResolvesModelInVault branch (see Patterns). |
| Feature/Controllers/App/Vaults/ContactDuplicateControllerTest.php | 5 | 2 | Duplicate (1), Low value (1) | Paging: ContactDuplicatesViewModelTest::it_pages_the_pairs_twenty_at_a_time and PaginationTest::it_marks_the_current_page_and_links_the_others. The test creates 50 contacts. Wrong vault is the trait branch. |
| Feature/Controllers/App/Vaults/ContactStructureVersionControllerTest.php | 7 | 2 | Low value | Retired and draft are the same `status = Published` clause of versionAskedFor(). Wrong vault is the trait branch. |
| Feature/Controllers/App/Vaults/VaultDefaultTemplateControllerTest.php | 7 | 2 | Duplicate (1), Low value (1) | SetVaultDefaultTemplateTest::it_replaces_the_default_already_there_for_that_kind_of_record. The draft refusal is another clause of the same Rule::exists as the other-account test, and SetVaultDefaultTemplateTest::it_refuses_a_version_that_is_still_a_draft covers it too. |
| Feature/Controllers/App/WelcomeControllerTest.php | 4 | 1 | Duplicate | MarkUserAsWelcomedTest::it_keeps_the_first_moment_when_somebody_is_welcomed_again owns the rule. |
| Feature/Controllers/App/Vaults/ContactDuplicateSearchControllerTest.php | 2 | 1 | Low value | A route appears in a view. The first test already proves the route. |
| Feature/Controllers/App/Vaults/ContactFieldRowControllerTest.php | 4 | 1 | Low value | Wrong vault is the trait branch. |
| Feature/Controllers/App/Vaults/ContactLifeEventCandidateControllerTest.php | 3 | 1 | Low value | Wrong vault is the trait branch. |
| Feature/Controllers/App/Vaults/ContactLifeEventFieldControllerTest.php | 3 | 1 | Low value | Wrong vault is the trait branch. |
| Feature/Controllers/App/Vaults/ContactListColumnControllerTest.php | 5 | 1 | Low value | Second variant of the refusal reaching the form. The flash and error shape is proven by the test before it. |
| Feature/Controllers/App/Vaults/ContactReminderFormControllerTest.php | 2 | 1 | Low value | Wrong vault is the trait branch. |
| Feature/Controllers/App/Vaults/FileControllerTest.php | 3 | 1 | Low value | Wrong vault is the trait branch (the controller repeats the check with abort_unless). |
| Feature/Controllers/App/Vaults/FileVariantControllerTest.php | 3 | 1 | Low value | Wrong vault is the trait branch. |
| Feature/Controllers/App/Vaults/RepetitionOrderTest.php | 5 | 1 | Low value | Third variant of where the blank is drawn. SetFieldValuesTest::it_keeps_two_repetitions_added_in_one_save_in_the_order_they_were_typed covers the write side. |
| Feature/Controllers/App/Vaults/SearchControllerTest.php | 5 | 1 | Duplicate | SearchRecordsTest::it_searches_the_current_vault_unless_told_to_look_across_the_account owns the scope rule. This test runs a real search engine. |
| Feature/Controllers/App/Vaults/SetAsideValueControllerTest.php | 4 | 1 | Low value | Wrong vault is the trait branch. |
| Feature/Controllers/App/Vaults/ReminderOccurrenceAnswerControllerTest.php | 4 | 1 | Low value | Wrong vault is the trait branch. |

Method names to remove, grouped by file (bucket in brackets):

- Feature/Controllers/App/Vaults/ContactControllerTest.php
  - it_offers_no_archived_list (Declaration)
  - either_spelling_of_an_address_reaches_the_page_of_one_person (Declaration)
  - it_lists_everybody_at_its_own_address (Low value)
  - it_does_not_find_a_contact_list_at_a_word_that_is_not_a_grouping (Low value)
  - it_applies_the_recorded_view_in_every_vault_of_the_account (Low value)
  - it_shows_the_grouping_the_address_states_over_the_recorded_one (Low value)
  - it_keeps_the_recorded_sort_when_the_address_states_only_a_grouping (Low value)
  - it_does_not_find_a_uuid_nobody_owns (Low value)
  - it_says_which_vault_and_where_in_it_at_the_top_of_every_screen (Low value)
  - it_keeps_the_box_that_opens_the_form_again_with_the_buttons (Low value)
  - it_says_the_contact_is_written_down_on_the_page_it_opens (Low value)
  - it_hands_back_the_empty_form_to_somebody_working_through_a_list (Low value)
  - it_offers_one_way_in_to_the_things_the_template_has_no_say_over (Low value)
  - the_edit_screen_offers_a_picture_and_nothing_to_remove_until_there_is_one (Low value)
  - the_edit_screen_draws_the_picture_and_the_way_to_be_rid_of_it (Low value)
  - the_edit_screen_of_any_other_contact_offers_the_way_out (Low value)
  - it_does_not_edit_or_delete_somebody_reached_through_the_wrong_vault (Low value)
  - it_offers_to_record_a_life_event_from_the_page (Low value)
  - it_does_not_find_the_relationships_page_of_another_account_or_another_vault (Low value)
  - it_opens_a_contact_at_its_readable_address (Low value)
  - it_does_not_find_a_contact_through_the_wrong_vault_of_the_same_account (Low value)
  - the_contacts_screen_offers_the_creation_below_the_ceiling (Low value)
  - it_moves_the_address_bar_with_the_view (Cosmetic)
  - it_falls_back_to_the_name_when_the_url_asks_for_nonsense (Cosmetic)
  - it_marks_the_people_of_the_vault_as_where_you_are_in_the_column (Cosmetic)
  - the_record_offers_the_way_back_to_the_list_in_the_breadcrumb_and_the_sidebar_only (Cosmetic)
  - it_asks_before_deleting_a_life_event_through_one_shared_card (Cosmetic)
  - it_shows_the_star_pressed_for_a_contact_who_is_a_favourite (Cosmetic)
  - it_searches_the_name_and_the_nickname (Duplicate)
  - it_sorts_on_the_column_it_was_asked_for (Duplicate)
  - it_shows_a_chip_for_every_way_of_filtering_the_list (Duplicate)
  - it_still_reaches_the_form_that_sits_under_the_same_path (Duplicate)
  - it_says_the_vault_is_empty_rather_than_showing_a_bare_table (Duplicate)
  - it_says_where_the_partial_contacts_went_rather_than_claiming_the_vault_is_bare (Duplicate)
  - it_says_so_when_the_search_matched_nobody (Duplicate)
  - it_offers_numbered_pages_and_a_choice_of_how_many_rows_a_page_holds (Duplicate)
  - it_remembers_the_rows_a_page_holds_for_the_next_visit (Duplicate)
  - it_leads_from_the_form_back_up_to_the_list_it_adds_to (Duplicate)
  - it_refuses_the_button_only_when_the_vault_has_nothing_to_start_a_contact_from (Duplicate)
  - it_starts_the_contact_on_the_template_that_was_asked_for (Duplicate)
  - it_will_not_start_a_contact_from_a_version_of_another_account (Duplicate)
  - it_refuses_a_template_belonging_to_another_account (Duplicate)
  - it_keeps_the_share_the_merge_and_the_card_behind_one_menu (Duplicate)
  - it_links_the_other_people_on_the_timeline_who_have_a_page (Duplicate)
  - the_version_history_names_every_version_the_record_has_been_on (Duplicate)
  - the_version_history_offers_a_newer_version_like_every_other_page (Duplicate)
  - it_offers_the_star_to_a_contact_who_is_not_a_favourite (Duplicate)
  - it_shows_the_offers_from_the_form_of_an_account_at_the_ceiling (Duplicate)
  - it_builds_the_address_of_a_contact_from_their_name (Duplicate)
  - two_people_of_one_name_each_get_an_address_of_their_own (Duplicate)
  - it_opens_a_contact_named_in_another_alphabet_written_either_way (Duplicate)
- Feature/Controllers/App/Vaults/VaultControllerTest.php
  - it_says_the_list_is_empty_rather_than_showing_nothing (Low value)
  - it_offers_a_way_to_the_account_since_it_has_no_dock_to_reach_it_from (Cosmetic)
  - it_shows_my_timeline_without_deleting_from_it (Duplicate)
  - it_offers_to_answer_an_overdue_date_from_the_dashboard (Duplicate)
  - it_leads_to_the_settings_of_the_vault (Low value)
  - it_offers_every_vault_of_the_account_in_the_launcher (Low value)
  - it_marks_the_dashboard_as_where_you_are_in_the_dock (Cosmetic)
  - it_leads_from_the_bar_to_the_screen_that_writes_somebody_down (Low value)
  - it_leads_back_to_the_list_of_vaults (Low value)
  - it_signs_the_screen_with_the_mark_of_the_application (Cosmetic)
  - it_reaches_the_account_settings_from_the_launcher_behind_the_dock (Low value)
  - the_form_that_opens_a_vault_carries_no_breadcrumb (Cosmetic)
  - it_leaves_the_default_where_it_is_when_the_box_is_left_alone (Low value)
  - it_starts_the_contacts_of_the_vault_on_the_template_that_was_picked (Duplicate)
  - it_refuses_a_template_belonging_to_another_account (Duplicate)
  - it_refuses_a_vault_with_no_name (Low value)
  - it_gives_the_vault_the_template_the_account_was_seeded_with_when_nothing_is_picked (Duplicate)
  - it_refuses_to_leave_a_vault_without_a_name (Low value)
  - it_refuses_to_recolour_a_vault_with_a_colour_that_is_not_on_offer (Low value)
  - it_sends_somebody_who_deleted_their_last_vault_to_the_empty_vault_list (Low value)
  - it_refuses_a_vault_when_the_account_has_no_contact_template (Duplicate)
  - it_shows_the_offers_from_the_form_of_an_account_at_the_ceiling (Duplicate)
  - it_still_lets_an_account_over_the_ceiling_edit_what_it_has (Duplicate)
  - it_opens_a_vault_at_its_readable_address (Duplicate)
  - it_builds_the_address_of_a_vault_from_its_name (Duplicate)
  - a_vault_still_opens_at_its_identifier (Duplicate)
  - it_opens_a_vault_whose_name_is_written_in_another_alphabet (Duplicate)
  - the_vaults_screen_offers_the_creation_below_the_ceiling (Low value)
- Feature/Controllers/App/Vaults/ContactSectionControllerTest.php
  - it_saves_an_address_and_what_it_is_for (Duplicate)
  - it_saves_the_wording_somebody_typed_for_the_label_of_an_address (Duplicate)
  - it_clears_an_address_whose_boxes_all_came_back_empty (Duplicate)
  - it_sends_the_block_back_open_when_a_required_address_is_left_empty (Duplicate)
  - it_refuses_a_street_longer_than_its_box_and_names_the_box (Low value)
  - it_refuses_a_country_the_account_does_not_offer (Duplicate)
  - it_opens_an_address_as_seven_boxes_to_fill_in (Duplicate)
  - it_opens_an_address_a_device_wrote_with_every_part_it_wrote (Duplicate)
  - it_keeps_every_part_a_device_wrote_when_only_the_city_changes (Duplicate)
  - it_does_not_find_a_block_of_somebody_kept_in_another_vault (Low value)
  - it_offers_a_way_to_add_a_repetition_and_to_take_one_away (Low value)
  - it_writes_the_upload_a_repetition_carries (Duplicate)
  - it_writes_the_type_beside_the_email_of_a_repetition (Duplicate)
  - it_names_the_type_of_a_repeated_email_inside_its_own_repetition (Duplicate)
  - it_writes_two_repeated_addresses_each_with_what_it_is_for (Duplicate)
  - it_takes_away_a_repetition_somebody_removed (Duplicate)
  - it_reads_a_repetition_back_as_what_it_holds (Low value)
  - a_block_that_takes_a_file_says_so_on_its_form (Declaration)
  - it_offers_a_reminder_under_every_date_it_draws (Duplicate)
  - it_offers_a_reminder_under_a_complete_date_as_well_as_a_recurring_one (Duplicate)
  - it_saves_the_reminder_with_the_block (Duplicate)
  - it_sends_the_block_back_open_when_a_reminder_is_asked_for_over_an_empty_date (Duplicate)
  - it_reads_a_saved_reminder_back_into_the_block (Duplicate)
  - it_reads_how_often_from_the_one_list_the_panel_offers (Low value)
  - it_saves_the_date_alone_when_nobody_asked_for_a_reminder (Duplicate)
  - it_shows_a_note_across_its_section (Duplicate)
  - it_folds_a_long_note_behind_read_more (Duplicate)
  - it_offers_to_add_a_note_when_there_is_none (Low value)
- Feature/Controllers/App/Vaults/VaultReminderControllerTest.php
  - it_says_a_reminder_is_late_with_more_than_a_colour (Duplicate)
  - it_groups_by_contact_at_its_own_address (Duplicate)
  - it_ignores_a_grouping_asked_for_in_the_query_string (Duplicate)
  - it_shows_the_next_date_of_a_rule_that_has_produced_nothing_yet (Duplicate)
  - it_groups_what_is_waiting_into_overdue_today_and_upcoming (Duplicate)
  - it_draws_the_stamp_the_cadence_the_count_and_the_exact_instant (Duplicate)
  - an_overdue_reminder_offers_both_answers (Duplicate)
  - it_offers_both_tabs_with_what_is_behind_each (Cosmetic)
  - the_history_tab_lists_what_was_answered_latest_answer_first (Duplicate)
  - the_history_tab_says_so_when_nothing_was_answered (Low value)
  - it_lists_what_the_vault_has_already_sent_newest_first (Duplicate)
  - it_offers_the_next_page_and_answers_it (Duplicate)
  - it_asks_before_sending_a_test_with_one_card_for_the_whole_page (Cosmetic)
- Feature/Controllers/App/Vaults/EntityControllerTest.php
  - it_draws_the_numbered_pages_under_the_records (Duplicate)
  - it_reads_the_page_size_from_the_query (Duplicate)
  - it_answers_not_found_for_a_page_through_the_wrong_vault (Low value)
  - it_says_so_when_the_type_is_turned_off (Duplicate)
  - it_says_when_the_vault_holds_none_of_them (Duplicate)
  - it_answers_not_found_for_the_form_through_the_wrong_vault (Low value)
  - it_offers_a_search_for_the_person_each_caregiver_names (Low value)
  - it_creates_an_animal_with_two_caregivers (Duplicate)
  - it_refuses_a_caregiver_from_another_vault (Duplicate)
  - it_refuses_an_upload_of_a_kind_the_field_does_not_take (Low value)
  - it_refuses_a_required_field_left_empty (Duplicate)
  - it_goes_back_to_the_list_when_the_type_was_turned_off_while_the_form_was_open (Duplicate)
  - it_refuses_a_name_made_only_of_markup (Low value)
- Feature/Controllers/App/Vaults/ContactRelationshipControllerTest.php
  - it_opens_the_form_with_the_type_it_was_asked_for (Duplicate)
  - it_offers_the_way_in_from_the_record (Low value)
  - it_puts_the_viewed_contact_on_the_other_side_when_the_roles_are_swapped (Duplicate)
  - it_writes_somebody_new_down_as_a_regular_contact_when_asked (Duplicate)
  - it_writes_two_people_down_when_neither_name_is_known (Duplicate)
  - it_refuses_a_contact_from_another_vault (Duplicate)
  - it_refuses_somebody_in_another_vault (Low value)
  - it_shows_the_relationships_tab_grouped_and_counted (Duplicate)
  - it_renames_a_partial_contact_through_the_form (Duplicate)
  - it_asks_for_a_first_name_when_the_name_is_said_to_be_known_now (Low value)
  - it_refuses_a_second_confirmation_of_the_same_removal (Low value)
  - it_refuses_a_relationship_reached_through_the_wrong_vault (Low value)
- Feature/Controllers/App/Vaults/ContactFavouriteControllerTest.php
  - it_leaves_a_contact_who_is_already_a_favourite_marked (Low value)
  - it_shows_the_confirmation_on_the_page_the_redirect_lands_on (Low value)
  - it_records_the_marking_in_the_life_event (Duplicate)
  - it_leaves_a_contact_who_is_not_a_favourite_unmarked (Low value)
  - it_records_the_clearing_in_the_life_event (Duplicate)
  - it_confirms_the_removal (Low value)
  - it_destroys_nothing_when_it_clears_the_mark (Low value)
  - it_refuses_a_state_that_is_not_a_boolean (Low value)
  - it_does_not_find_a_contact_reached_through_another_vault_of_the_same_account (Low value)
- Feature/Controllers/App/Vaults/ContactShareLinkControllerTest.php
  - it_offers_a_first_link_when_nothing_is_shared (Low value)
  - it_offers_the_form_with_every_section_going_out (Low value)
  - it_refuses_a_link_with_no_name (Low value)
  - it_stops_asking_for_a_password_when_the_form_says_to (Duplicate)
  - it_leaves_the_inclusions_the_form_does_not_ask_about_alone (Low value)
  - it_refuses_a_page_of_visits_of_a_link_that_is_onto_another_contact (Low value)
  - it_offers_the_page_size_when_the_links_fill_more_than_one_page (Duplicate)
  - it_refuses_somebody_in_another_vault (Low value)
  - it_offers_the_way_in_from_the_record (Low value)
- Feature/Controllers/App/Vaults/ContactMergeControllerTest.php
  - it_offers_the_other_contacts_of_the_vault (Duplicate)
  - it_narrows_the_picker_to_the_typed_name (Duplicate)
  - it_pages_the_picker_and_offers_a_search_field (Duplicate)
  - it_does_not_find_a_page_of_the_picker_reached_through_the_wrong_vault (Low value)
  - it_tells_the_reader_of_an_absorbed_address_who_absorbed_them (Duplicate)
  - it_does_not_find_a_contact_reached_through_the_wrong_vault (Low value)
  - it_does_not_find_a_merge_reached_through_the_wrong_vault (Low value)
- Feature/Controllers/App/Vaults/ReminderControllerTest.php
  - it_shows_a_reminder_written_by_hand (Duplicate)
  - it_says_why_a_stopped_reminder_sends_nothing (Duplicate)
  - it_pages_the_delivery_details_fifty_at_a_time (Duplicate)
  - it_asks_before_it_deletes (Cosmetic)
  - a_reminder_of_another_vault_of_the_same_account_is_not_found (Low value)
  - deleting_a_reminder_of_another_vault_of_the_same_account_is_not_found (Low value)
- Feature/Controllers/App/Vaults/ContactReminderControllerTest.php
  - every_filter_answers_at_its_own_address (Duplicate)
  - the_history_shows_what_was_answered (Duplicate)
  - the_history_loads_twenty_five_answers_at_a_time (Duplicate)
  - a_history_page_reached_through_the_wrong_vault_is_not_found (Low value)
  - it_refuses_a_reminder_without_a_wording (Low value)
  - a_contact_reached_through_the_wrong_vault_is_not_found (Low value)
- Feature/Controllers/App/Vaults/ContactNameControllerTest.php
  - it_keeps_the_order_a_name_reads_in (Duplicate)
  - it_refuses_a_name_order_it_does_not_know (Low value)
  - it_refuses_somebody_in_another_vault (Low value)
  - the_address_somebody_was_read_at_before_a_rename_stops_answering (Low value)
  - it_saves_every_way_of_writing_the_name_and_the_one_shown (Duplicate)
- Feature/Controllers/App/SharedContactControllerTest.php
  - it_leaves_out_a_field_marked_sensitive (Duplicate)
  - it_writes_the_visit_down_and_counts_it (Duplicate)
  - it_refuses_an_address_that_is_not_shaped_like_a_token (Declaration)
- Feature/Controllers/App/NotificationControllerTest.php
  - a_full_page_offers_to_load_more (Duplicate)
  - the_last_page_holds_the_rest_and_says_it_is_the_end (Duplicate)
  - a_short_list_offers_nothing_more (Duplicate)
- Feature/Controllers/App/Vaults/ContactLifeEventControllerTest.php
  - it_does_not_find_a_participant_kept_in_another_vault (Duplicate)
  - it_does_not_find_a_contact_through_another_vault (Low value)
  - it_does_not_open_the_panel_for_a_contact_through_another_vault (Low value)
- Feature/Controllers/App/Vaults/ContactVCardControllerTest.php
  - it_carries_the_values_the_reader_marked_sensitive (Duplicate)
  - it_answers_not_found_for_a_contact_reached_through_the_wrong_vault (Low value)
  - it_answers_not_found_while_vcard_export_is_off_for_the_account (Declaration)
- Feature/Controllers/App/Vaults/NotesNewestFirstTest.php
  - a_note_somebody_writes_lands_above_the_ones_already_there (Duplicate)
  - the_notes_are_on_the_tab_a_contact_opens_on (Declaration)
  - the_blank_is_drawn_above_the_notes_already_there (Duplicate)
- Feature/Controllers/App/Vaults/ReadableAddressPreferenceTest.php
  - an_address_saved_while_it_was_off_still_answers_once_it_is_on (Low value)
  - the_api_reports_the_readable_name_whatever_the_preference_says (Declaration)
  - turning_it_off_changes_what_is_built_and_nothing_else (Duplicate)
- Feature/Controllers/App/Vaults/ReminderOccurrenceTestNotificationControllerTest.php
  - it_produces_no_row_on_the_list_of_what_was_sent (Duplicate)
  - pressing_it_twice_still_produces_no_row (Duplicate)
  - it_refuses_a_deadline_reached_through_the_wrong_vault (Low value)
- Feature/Controllers/App/Vaults/VaultLifeEventControllerTest.php
  - it_puts_the_life_event_on_the_timeline_of_everybody_who_was_there (Low value)
  - it_records_a_life_event_for_somebody_else_without_the_signed_in_person (Low value)
  - it_brings_back_the_person_chosen_when_the_form_is_refused (Low value)
- Feature/Controllers/App/Vaults/VaultSettingsControllerTest.php
  - it_navigates_the_screen_with_the_vault_column (Cosmetic)
  - it_says_what_deleting_the_last_vault_leaves_behind (Duplicate)
  - it_says_another_vault_takes_the_default_over_when_one_is_left (Duplicate)
- Feature/Controllers/App/Vaults/AbsorbedContactControllerTest.php
  - it_explains_rather_than_sending_the_reader_to_somebody_they_did_not_ask_for (Low value)
  - the_screen_is_operable_with_the_keyboard_alone_and_announced (Cosmetic)
- Feature/Controllers/App/Vaults/ContactAvatarControllerTest.php
  - it_refuses_an_upload_but_still_takes_the_picture_off_while_file_upload_is_off (Declaration)
  - it_cannot_reach_a_contact_kept_in_another_vault (Low value)
- Feature/Controllers/App/Vaults/ContactDuplicateControllerTest.php
  - it_draws_the_numbered_pages_under_the_pairs (Duplicate)
  - it_does_not_find_a_pair_reached_through_the_wrong_vault (Low value)
- Feature/Controllers/App/Vaults/ContactStructureVersionControllerTest.php
  - it_refuses_a_version_that_has_been_retired (Low value)
  - a_contact_in_another_vault_cannot_be_reached (Low value)
- Feature/Controllers/App/Vaults/VaultDefaultTemplateControllerTest.php
  - it_replaces_the_template_the_vault_was_pointing_at (Duplicate)
  - it_refuses_a_version_nobody_has_published (Low value)
- Feature/Controllers/App/WelcomeControllerTest.php
  - it_sends_somebody_already_welcomed_home_without_moving_the_moment (Duplicate)
- Feature/Controllers/App/Vaults/ContactDuplicateSearchControllerTest.php
  - it_offers_the_search_on_the_screen (Low value)
- Feature/Controllers/App/Vaults/ContactFieldRowControllerTest.php
  - it_does_not_find_the_rows_through_the_wrong_vault (Low value)
- Feature/Controllers/App/Vaults/ContactLifeEventCandidateControllerTest.php
  - it_does_not_find_a_contact_through_another_vault (Low value)
- Feature/Controllers/App/Vaults/ContactLifeEventFieldControllerTest.php
  - it_does_not_find_a_contact_through_another_vault (Low value)
- Feature/Controllers/App/Vaults/ContactListColumnControllerTest.php
  - it_keeps_what_somebody_typed_when_the_columns_come_back_refused (Low value)
- Feature/Controllers/App/Vaults/ContactReminderFormControllerTest.php
  - it_does_not_open_the_form_for_a_contact_through_another_vault (Low value)
- Feature/Controllers/App/Vaults/FileControllerTest.php
  - it_does_not_find_a_file_kept_in_another_vault (Low value)
- Feature/Controllers/App/Vaults/FileVariantControllerTest.php
  - it_does_not_find_a_file_kept_in_another_vault (Low value)
- Feature/Controllers/App/Vaults/RepetitionOrderTest.php
  - it_puts_a_new_document_last_on_the_template_an_account_starts_with (Low value)
- Feature/Controllers/App/Vaults/SearchControllerTest.php
  - it_lists_what_was_found_and_where_when_searching_every_vault (Duplicate)
- Feature/Controllers/App/Vaults/SetAsideValueControllerTest.php
  - it_refuses_somebody_in_another_vault (Low value)
- Feature/Controllers/App/Vaults/ReminderOccurrenceAnswerControllerTest.php
  - a_deadline_reached_through_the_wrong_vault_is_not_found (Low value)

### Patterns
- Wrong vault of the same account tested once per controller: 33 tests (Low value, listed above with names containing another_vault, wrong_vault or other_vault). Ten of the bound models (Contact, ContactMerge, ContactDuplicateCandidate, File, LifeEvent, Reminder, ReminderOccurrence, Entity, Relationship, ShareLink) use ResolvesModelInVault, which adds `where vault_id` from the route. ResolvesModelInVaultTest has four tests and none covers a vault mismatch, so the branch is only proven through controllers. Examples: ContactFavouriteControllerTest::it_does_not_find_a_contact_reached_through_another_vault_of_the_same_account, FileVariantControllerTest::it_does_not_find_a_file_kept_in_another_vault, ContactVCardControllerTest::it_answers_not_found_for_a_contact_reached_through_the_wrong_vault. The removal depends on adding one vault mismatch test to ResolvesModelInVaultTest first. I kept ContactControllerTest::it_does_not_find_somebody_kept_in_another_vault as the route plus trait integration test. Controllers that run their own query by string or by a non trait model (ContactLifeEventRestoration, FieldChoice, FieldRow field lookup, EntityType hidden in a vault, the absorbed contact lookup) keep their test.
- Screens re-proving the view model: 115 Duplicates, of which about 60 are copy, ordering, paging or empty state assertions already owned by a view model. Examples: VaultReminderControllerTest::it_groups_what_is_waiting_into_overdue_today_and_upcoming (VaultRemindersViewModelTest::it_groups_what_waits_into_overdue_today_and_upcoming), ContactControllerTest::it_says_so_when_the_search_matched_nobody (ContactsViewModelTest::it_tells_an_empty_vault_apart_from_a_search_that_matched_nobody), EntityControllerTest::it_says_when_the_vault_holds_none_of_them.
- Controllers re-proving the action: about 40 Duplicates assert rows an action writes through an HTTP call. Examples: ContactSectionControllerTest::it_clears_an_address_whose_boxes_all_came_back_empty (SetFieldValuesTest::it_clears_an_address_whose_seven_boxes_came_back_empty), ContactRelationshipControllerTest::it_writes_two_people_down_when_neither_name_is_known (CreateRelationshipTest::it_writes_two_separate_people_down_when_neither_name_is_known), VaultControllerTest::it_opens_a_vault_whose_name_is_written_in_another_alphabet (VaultTest::it_keeps_the_alphabet_a_name_is_written_in).
- Paging proven again at four layers: 11 tests create 20 to 60 rows to see a "Load more" link or numbered pages that PaginationTest, ListPanelTest, PageSizeTest and a view model already prove. Examples: ContactControllerTest::it_offers_numbered_pages_and_a_choice_of_how_many_rows_a_page_holds (51 contacts), ReminderControllerTest::it_pages_the_delivery_details_fifty_at_a_time (60 log rows), VaultReminderControllerTest::it_offers_the_next_page_and_answers_it.
- Look assertions: 15 Cosmetic. They assert aria-label strings used as markers, an exact count of `x-target.push` attributes, a count of `href` occurrences, an SVG `fill`, an element id, or a bare `<a>`. Examples: ContactControllerTest::it_moves_the_address_bar_with_the_view, ContactControllerTest::the_record_offers_the_way_back_to_the_list_in_the_breadcrumb_and_the_sidebar_only, VaultControllerTest::it_signs_the_screen_with_the_mark_of_the_application.
- A link or a route appears on a screen: about 15 Low value tests (assertSee of a route, "Add somebody", "Settings"). Examples: VaultControllerTest::it_leads_back_to_the_list_of_vaults, ContactShareLinkControllerTest::it_offers_the_way_in_from_the_record, ContactDuplicateSearchControllerTest::it_offers_the_search_on_the_screen.
- Second variants of one rule: ContactFavouriteControllerTest asserts the flash twice, the validation twice and the no op twice. VaultControllerTest and ContactShareLinkControllerTest each have a "refuses no name" test beside a test that already proves the validation is wired.
- Misplaced tests that should move, not go: ContactControllerTest::it_leaves_out_the_people_the_main_list_never_shows, ::it_shows_only_the_favourites_under_their_own_chip, ::it_shows_the_people_written_down_as_a_first_name_only and ::it_pushes_the_people_it_knows_nothing_about_to_the_end_either_way are rules about which rows the list holds and in what order. ContactsViewModelTest tests chip counts but not the filtered rows. The same goes for VaultReminderControllerTest::it_leaves_out_the_reminders_of_another_vault_of_the_same_account and ::a_sent_row_survives_the_rule_that_made_it, and for the two query count tests. I kept all of them.

### Cost hot spots
- RepetitionOrderTest (5 tests) and NotesNewestFirstTest (3 tests): every test runs `CreateDefaultTemplate` (a full template with pages, sections, fields), creates a contact and sends two to four PUT requests. Removing NotesNewestFirstTest and the fifth RepetitionOrderTest test drops 4 of 8 default template builds. The remaining four could share one test with a data provider over the order.
- SearchControllerTest::it_lists_what_was_found_and_where_when_searching_every_vault starts the search engine (`useSearchEngine`, `waitForSearchEngine`). It is the only test of this scope that talks to an external service. SearchRecordsTest already proves the scope rule.
- Many row loops to reach a page boundary: ContactControllerTest (51 contacts, then 21 contacts), ContactDuplicateControllerTest (25 pairs, so 50 contacts and 25 candidates), ContactMergeControllerTest (26 contacts), ContactFieldRowControllerTest (25 rows with 25 field values), ReminderControllerTest (60 log rows), ContactReminderControllerTest (26 deadlines), VaultReminderControllerTest (26 rules and deadlines), EntityControllerTest (25 and 21 entities), ContactShareLinkControllerTest (25 links). All of them are Duplicate or covered by view model paging tests with smaller page sizes.
- Query count tests: ContactControllerTest::it_fills_a_section_in_in_the_same_number_of_queries_however_many_choices_it_offers and ::it_reads_a_record_in_the_same_number_of_queries_whatever_its_structure_holds build two contacts on generated versions (up to 8 sections), and VaultReminderControllerTest::it_does_not_ask_more_of_the_database_as_the_list_grows and ::the_sent_tab_asks_the_same_number_of_questions_whatever_it_holds each build 8 rules and request the page twice. I kept them because nothing else guards N+1 on these screens, but a one off helper (`assertSameQueryCount`) would remove the repeated setup.
- Fixture builders repeated per test with no sharing: SharedContactControllerTest::sharedContact (two pages, five sections, five values for every one of its 12 tests, of which 9 remain), ContactShareLinkControllerTest::versionWithTwoPages, ContactRelationshipControllerTest::contactOn (creates a template and a VaultDefaultTemplate per contact), ContactSectionControllerTest::vaultReadyForContacts. Most tests of these files use one or two of the objects created. After the removals the cost drops with the test count.
- Because all tests use `RefreshDatabase` and are database bound, the 237 removals matter more on the MySQL and PostgreSQL jobs than on sqlite. The suite audit records 60 ms per Feature/Controllers/App test on sqlite.

### Proposed guidelines
- Do not write a "wrong vault" test for a model that uses `ResolvesModelInVault`. Add the case once to ResolvesModelInVaultTest, and keep one route level test per vault scoped screen at most.
- A controller test proves the redirect, the flash, the inline validation and one refusal from the action. It does not assert the rows an action writes. If the action test already asserts the columns, the controller test asserts only the response.
- Do not assert a screen's copy, order, grouping, empty state or paging in a controller test. Write it in the view model test, and keep one render test per screen with one distinctive sentence.
- Do not create 20 or more rows to see a pagination link. The page size is a parameter of the view model, so test the boundary there with a small size.
- A refusal is proven once per controller. A second "refuses X" test is allowed only when X is a different inline validation rule or a different branch of the controller's own code.
- A feature flag on a route is not tested per route. EnsureFlagIsOnTest owns the middleware, and the route group declaration is a declaration.
- A test that needs a default template, a search engine or an assertion on element position states in its first line why a lower layer cannot prove it.
- Never assert an `aria-*` attribute, an element id or a count of href occurrences as a stand in for "the link is on the page". Assert the route, or the accessibility contract of the component, in the component test.

### Open questions
- ContactControllerTest::it_leaves_out_the_people_the_main_list_never_shows and the other list filtering tests: I could not find a ContactsViewModelTest test that asserts which rows a chip returns (only counts, links and ordering), so I kept them. I looked at every test name in tests/Unit/ViewModels/Vaults/ContactsViewModelTest.php and grepped it for is_archived and is_partial. If a rows test exists under another name these four are Duplicate.
- The 33 wrong vault removals depend on adding a vault mismatch test to tests/Unit/Models/Concerns/ResolvesModelInVaultTest.php. Without it, ContactControllerTest::it_does_not_find_somebody_kept_in_another_vault is the only proof left, and it covers `contacts.show` only.
- NotificationControllerTest::marking_one_read_twice_keeps_the_instant_it_was_first_read and ::clearing_everything_leaves_another_account_alone are action rules proven at controller level, because tests/Unit/Actions has no MarkNotificationsAsRead test. I kept them. Better placement: an action test.
- ContactSectionControllerTest::it_refuses_a_file_and_saves_nothing_while_file_upload_is_off_for_the_account has no action level twin (SetFieldValuesTest has no file upload flag test), so I kept it.
- ContactSectionControllerTest::it_keeps_every_note_when_a_section_holding_more_than_a_page_is_saved_unchanged is a data loss guard across the view model (all rows in the form) and the action. I did not find a single test that proves both halves, so I kept it.
- RepetitionOrderTest asserts HTML position of form indices. It tests the order of data in a form rather than the look, so I did not call it Cosmetic, but it is the nearest case in this area to "element order". A view model assertion on the row order handed to the form would be cheaper (ContactViewModelTest::it_hands_the_edit_form_every_row_of_a_repeater is the candidate).
- ContactLifeEventControllerTest::it_does_not_find_a_participant_kept_in_another_vault is marked Duplicate of CreateLifeEventTest::it_refuses_a_participant_of_another_vault, but VaultLifeEventControllerTest::it_does_not_find_somebody_of_another_vault is kept. The two controllers resolve their contact differently (a participant list against the route contact). I did not read the action to confirm the participant check maps to a 404 in the same way for both, so treat the Duplicate as likely rather than confirmed.
- SharedContactControllerTest: SharedContactViewModelTest::it_offers_a_tab_for_each_page_that_has_something_on_it and OpenShareLinkTest cover what the cited tests cover, but the share.show page query parameter is read in the controller. I kept it_opens_the_page_named_in_the_address as the proof.
- I did not run any test, so I cannot confirm that removed tests do not exercise code no other test reaches (for example Blade partials that only these screens render).

> Checked by the coordinator: partly confirmed. `ResolvesModelInVault::findInVault()` does narrow the query to the vault the URL names (line 104), and `ResolvesModelInVaultTest` has no test of that branch, only the account one (line 52). But only 10 models use the trait, and the vault controllers hold 68 `abort_unless` calls. Where the bound model uses `ResolvesModelInAccount`, the controller's `abort_unless` is the only guard and its wrong vault test is the owner, as `testing.md` says. So: add the vault mismatch test to `ResolvesModelInVaultTest` first, then delete a controller's wrong vault test only after checking that its bound model uses `ResolvesModelInVault`. The 33 is an upper bound.

## Feature/Controllers/App/Settings

I read all 74 files under `tests/Feature/Controllers/App/Settings` in full (588 `#[Test]` methods, about 11,800 lines), plus the controllers where ownership was unclear (`EmailController`, `AccountController`, `TimezoneController`, `LifeEventTypeController`, `RelationshipTypeController`), `RouteProtectionTest`, the model binding trait tests, and the names (and, for every cited Duplicate that was not self evident, the bodies) of the owning tests under `tests/Unit/Actions`, `tests/Unit/ViewModels/Settings`, `tests/Unit/Policies`, `tests/Unit/Models/EmailSentTest.php` and `tests/Feature/Views`. Per `testing.md`, this layer should own: that each route answers, validation written inline in the controller, the response shape (redirect, flash, JSON), the controller's own queries (ValueList, ValueListItem, StructureVersion, StructureVersionPage, Passkey, SecurityKey, Payment, API keys are listed in `RouteProtectionTest::$modelsScopedByTheirController` or resolve their own record), one refusal from the action per controller, and a flag gate where the controller or route is gated. It should not own what a screen lists or says (view models), business rules (actions), or "another account gets 404" for models that use `ResolvesModelInAccount` (trait tests plus `RouteProtectionTest`). The area has zero `#[DataProvider]` tests and no test that is shared across controllers, so the same check is written many times.

### Totals
| Bucket | Tests |
| --- | --- |
| Keep | 359 |
| Duplicate | 100 |
| Cosmetic | 26 |
| Declaration | 20 |
| Low value | 83 |
| Total | 588 |

The 229 removable tests are 39 percent of the area. Several Keep tests are also candidates to fold into data providers (see Patterns), which would cut the method count further without losing a branch. The Declaration bucket is the "without a confirmed password" family: 15 tests assert the same `password.confirm` middleware redirect on 15 routes. One test over a list of the gated routes would replace them, and that single test is a Keep that does not exist yet.

### Findings by file
Only files with something to remove. Bucket letters: D = Duplicate, C = Cosmetic, Dc = Declaration, L = Low value.

| File (path relative to tests/) | Tests | Remove | Bucket(s) | Why, citing the owning test when Duplicate |
| --- | --- | --- | --- | --- |
| Feature/Controllers/App/Settings/SettingsControllerTest.php | 7 | 6 | Dc 1, L 5 | The rows and links are owned by `SettingsViewModelTest::it_groups_the_rows_into_you_this_account_and_leaving` and `::it_links_notifications_to_the_notification_channels`. One render test remains. The rest assert that a vault name is absent from a page that never lists vaults. |
| Feature/Controllers/App/Settings/Account/AccountControllerTest.php | 10 | 5 | Dc 1, L 4 | Middleware redirect; empty input variant of the mismatch test; "other account untouched" is already `DestroyAccountTest::it_destroys_the_account` plus the account check; two static copy checks on the profile screen. |
| Feature/Controllers/App/Settings/Account/Profile/EmailControllerTest.php | 5 | 2 | Dc 1, L 1 | Middleware; second validation variant (the unique rule test already proves the inline `validate` is wired). |
| Feature/Controllers/App/Settings/Account/Profile/PhotoControllerTest.php | 10 | 3 | D 2, C 1 | Too large is `UpdateUserPhotoTest::it_refuses_a_file_larger_than_five_megabytes`. Initials fallback is `AvatarInitialsTest` plus `ProfileViewModelTest::it_knows_whether_there_is_a_photo`. Both densities asserts the drawing. |
| Feature/Controllers/App/Settings/Account/Profile/ProfileControllerTest.php | 9 | 5 | D 1, C 3, L 1 | Back link is `SettingsPageTest::it_leads_back_to_the_settings_unless_it_is_told_otherwise`. Settings column, two forms and the button wording assert ids, Alpine strings and layout. |
| Feature/Controllers/App/Settings/Account/Preferences/PreferenceControllerTest.php | 21 | 12 | D 3, C 2, L 7 | Time zone section and the "nearest" copy are `PreferencesViewModelTest::it_hands_the_time_zone_section_its_own_block` and `TimezonePanelTest::it_says_nothing_about_a_guess_unless_the_zone_came_from_one`. Map and zones asserts check Alpine/JS strings. Date, time and number format are three copies of one save and one refusal. |
| Feature/Controllers/App/Settings/Account/Preferences/TimezoneControllerTest.php | 14 | 6 | D 1, L 5 | "Leaves the language alone" is `UpdateTimezoneTest::it_leaves_the_language_and_the_formats_alone`. Three tests post an empty form, one more posts a second named zone, one posts a second point. |
| Feature/Controllers/App/Settings/Account/Security/SecurityControllerTest.php | 16 | 7 | D 1, C 1, Dc 1, L 4 | Key list is `SecurityViewModelTest::it_lists_the_security_keys_with_when_they_were_last_used`. The focus test asserts literal Alpine expressions. Four static copy checks. |
| Feature/Controllers/App/Settings/Account/Security/PasswordControllerTest.php | 16 | 4 | Dc 2, L 2 | Two middleware redirects; "not remembered" negative; "too short" variant of the confirmation refusal. |
| Feature/Controllers/App/Settings/Account/Security/ApiKeyControllerTest.php | 13 | 7 | D 4, Dc 1, L 2 | "Prints the key once" repeats `it_hands_the_key_over_once`. Three copy button tests are `CopyButtonTest::it_announces_both_what_worked_and_what_did_not` and `::it_carries_the_value_rather_than_reading_it_back_off_the_screen`. |
| Feature/Controllers/App/Settings/Account/Security/TwoFactorControllerTest.php | 11 | 3 | Dc 2, L 1 | Two middleware redirects; "asks for a code" variant. |
| Feature/Controllers/App/Settings/Account/Security/SsoControllerTest.php | 9 | 3 | D 1, Dc 2 | Passkey left is `DisconnectProviderFromUserTest::it_disconnects_the_provider_of_somebody_who_still_has_a_passkey`. Two middleware redirects. |
| Feature/Controllers/App/Settings/Account/Security/SecurityKeyControllerTest.php | 9 | 1 | L 1 | Rename without name; the passkey rename refusal already proves the shape. |
| Feature/Controllers/App/Settings/Account/Security/McpCallControllerTest.php | 7 | 3 | Dc 1, L 2 | Link from security screen; two empty state copy checks. |
| Feature/Controllers/App/Settings/Account/Security/RecoveryCodeControllerTest.php | 6 | 2 | D 1, Dc 1 | "Shows the codes once after regenerating" repeats `TwoFactorControllerTest::it_shows_the_codes_once_after_enrolment` (same `show` route). Middleware. |
| Feature/Controllers/App/Settings/Account/Security/SecurityKeyOptionsControllerTest.php | 4 | 1 | Dc 1 | Middleware. |
| Feature/Controllers/App/Settings/Account/Security/ConnectedAssistantControllerTest.php | 3 | 2 | D 1, Dc 1 | Listing is `SecurityViewModelTest::it_gives_one_line_per_connected_assistant_however_often_it_renewed_its_token`. Middleware. |
| Feature/Controllers/App/Settings/Account/Notifications/ChannelProofControllerTest.php | 7 | 1 | L 1 | "A code needs typing in" is a second required field check. |
| Feature/Controllers/App/Settings/Account/Notifications/ChannelTestControllerTest.php | 5 | 2 | L 2 | `a_test_writes_no_notification_and_touches_no_deadline` asserts zero rows after `Notification::fake()`, which cannot write any, so it can never fail. The "server cannot send" refusal is a second refusal. |
| Feature/Controllers/App/Settings/Account/Notifications/NotificationChannelControllerTest.php | 13 | 4 | D 2, L 2 | Connected Telegram copy is `NotificationChannelsViewModelTest::it_names_the_state_each_channel_is_in`. No bot is `StartChannelConnectionTest::it_refuses_when_the_server_has_no_bot`. Two tests assert absent ids or repeat a refusal already asserted. |
| Feature/Controllers/App/Settings/Account/Notifications/TelegramWebhookControllerTest.php | 11 | 2 | L 2 | "No secret at all" is a variant of the wrong secret test. "No message in the update" is a variant of the chatter test. |
| Feature/Controllers/App/Settings/Account/Logs/EmailSentControllerTest.php | 10 | 8 | D 4, Dc 3, L 1 | Precedence of bounced, complained, delivered is `EmailSentTest::it_reads_as_bounced_when_the_provider_reported_both` and `::it_reads_as_complained_even_when_it_also_bounced` (two tests). Load more twice is `EmailsSentViewModelTest::it_gives_ten_emails_a_page` plus `ListPanelTest`. Three badge tests are one data provider. |
| Feature/Controllers/App/Settings/Account/Logs/LogControllerTest.php | 7 | 6 | D 4, L 2 | Load more twice is `LogsViewModelTest::it_gives_five_logs_a_page`; latest emails is `LogsViewModelTest::it_gives_the_last_five_emails_sent_newest_first`; no address is `::it_gives_the_entries_the_application_wrote_on_its_own`. |
| Feature/Controllers/App/Settings/Account/Billing/BillingControllerTest.php | 9 | 8 | D 7, L 1 | Each state is a `BillingViewModelTest` rule (`it_names_the_subscription_a_subscriber_is_on`, `it_says_when_a_cancelled_subscription_ends`, `it_says_access_was_granted_rather_than_bought`, `it_sums_up_lifetime_access_with_the_day_it_was_paid`, `it_offers_nothing_on_a_self_hosted_instance`, `it_says_the_payment_is_being_confirmed_on_the_way_back_from_stripe`, `it_says_the_offers_could_not_be_read`). See Open questions on the Blade branch. |
| Feature/Controllers/App/Settings/Account/Billing/BillingCurrencyControllerTest.php | 4 | 2 | D 1, L 1 | Currency shown is `BillingViewModelTest::it_prices_every_offer_in_the_currency_it_shows`. Missing currency repeats the refusal. |
| Feature/Controllers/App/Settings/Account/Billing/CheckoutControllerTest.php | 4 | 1 | D 1 | Stripe down is `StartCheckoutTest::it_says_stripe_could_not_be_reached_rather_than_leaking_its_error`; the other refusal test stays. |
| Feature/Controllers/App/Settings/Account/Billing/CheckoutReturnControllerTest.php | 6 | 2 | D 2 | Stripe unreachable is `SettleOrderTest::it_settles_nothing_when_stripe_will_not_answer_for_the_session`. Other account is `SettleOrderTest::it_settles_nothing_for_an_order_that_belongs_to_another_account`. |
| Feature/Controllers/App/Settings/Account/Billing/InvoiceControllerTest.php | 7 | 3 | D 2, L 1 | Paging 20 is `InvoicesViewModelTest::it_shows_twenty_payments_a_page` (the test also creates 25 rows). Other account's payments is `::it_never_lists_another_account_payments`. "Counts the payments" only asserts a link. |
| Feature/Controllers/App/Settings/Account/Billing/SubscriptionCancellationControllerTest.php | 3 | 1 | D 1 | The keep button repeats `BillingControllerTest::it_offers_a_subscription_that_is_ending_to_be_kept`, itself a `BillingViewModelTest` rule. |
| Feature/Controllers/App/Settings/Account/Billing/SubscriptionControllerTest.php | 7 | 3 | D 2, L 1 | No subscription is `UpgradeSubscriptionTest::it_refuses_an_account_with_no_subscription`. Yearly switch is `BillingViewModelTest::it_marks_a_longer_subscription_as_a_switch_rather_than_a_purchase`. Cancel warning is static copy. |
| Feature/Controllers/App/Settings/Account/Billing/SubscriptionPortalControllerTest.php | 8 | 4 | D 3, L 1 | Three "links to the portal" tests are `BillingViewModelTest::it_says_whether_stripe_knows_the_account` and `::it_says_access_was_granted_rather_than_bought`. "Granted only" repeats the not found refusal. |
| Feature/Controllers/App/Settings/Account/Transfer/AccountExportControllerTest.php | 7 | 3 | D 1, C 1, Dc 1 | Every vault is `CreateAccountExportTest::it_asks_for_every_vault_of_the_account_and_has_the_file_built`. Refresh target asserts an Alpine string and is `AccountTransferViewModelTest::it_knows_when_an_export_of_the_account_is_being_prepared`. Middleware. |
| Feature/Controllers/App/Settings/Account/Transfer/AccountImportControllerTest.php | 12 | 3 | D 2, Dc 1 | Several vaults is `StartAccountImportTest::it_leaves_each_vault_of_a_file_of_several_to_be_created_from_the_file`. Other account's vault is `StartAccountImportTest::it_refuses_a_vault_of_another_account`. Middleware. |
| Feature/Controllers/App/Settings/Account/ValueLists/ValueListControllerTest.php | 12 | 4 | D 1, C 2, L 1 | Delete card is `DeleteActionTest::it_always_warns_that_the_action_is_final` and `::it_asks_before_it_destroys`. Add button order and the add card assert layout. Rename without name repeats the create refusal. |
| Feature/Controllers/App/Settings/Account/ValueLists/ValueListItemActivationControllerTest.php | 5 | 1 | D 1 | On screen "no longer offered" is `ValueListsViewModelTest::it_says_whether_a_value_is_still_active`. |
| Feature/Controllers/App/Settings/Account/ValueLists/ValueListItemControllerTest.php | 10 | 3 | D 2, L 1 | Stop offering is `ValueListsViewModelTest::it_says_a_value_can_be_deleted_only_while_nothing_points_at_it`. Delete card is `DeleteActionTest`. Rename without name repeats the add refusal. |
| Feature/Controllers/App/Settings/Account/EntityTypes/EntityTypeControllerTest.php | 25 | 10 | D 5, L 5 | Examples is `EntityTypesViewModelTest::it_gives_three_example_profiles_with_three_fields_each`; ordering and "not active" are `::it_says_whether_the_account_has_a_type_of_its_own` and `::it_gives_a_row_everything_it_is_drawn_from`; slug given is `CreateEntityTypeTest::it_takes_the_slug_it_is_given_rather_than_one_made_from_the_name`; slug ignored on update is `UpdateEntityTypeTest::it_leaves_the_slug_alone`. |
| Feature/Controllers/App/Settings/Account/LifeEventTypes/LifeEventCategoryActivationControllerTest.php | 2 | 1 | D 1 | `ActivateLifeEventCategoryTest::it_activates_a_supplied_category_like_any_other`. |
| Feature/Controllers/App/Settings/Account/LifeEventTypes/LifeEventTypeControllerTest.php | 19 | 6 | D 1, C 1, L 4 | Category list is `LifeEventTypesViewModelTest::it_shows_only_the_types_of_the_selected_category`. `x-target.push` assert is markup. Three validation variants of one nested rule and one `is_active` variant. |
| Feature/Controllers/App/Settings/Account/RelationshipTypes/RelationshipCategoryControllerTest.php | 6 | 1 | L 1 | Rename without name repeats the add refusal. |
| Feature/Controllers/App/Settings/Account/RelationshipTypes/RelationshipTypeControllerTest.php | 15 | 5 | D 1, C 1, L 3 | Name in use is `CreateRelationshipTypeTest::it_refuses_a_name_the_account_already_uses`. `the_list_carries_the_target_the_move_buttons_swap` asserts ids. Two static form copy checks and one negative of the delete reason test. |
| Feature/Controllers/App/Settings/Account/Templates/TemplateControllerTest.php | 13 | 4 | D 2, L 2 | Query string is `TemplatesViewModelTest::it_narrows_the_list_by_name_or_slug`. Kind of record is `CreateTemplateTest::it_names_the_kind_of_record_it_describes`. Missing name and a static form check. |
| Feature/Controllers/App/Settings/Account/Templates/TemplateCopyControllerTest.php | 2 | 1 | D 1 | Same name and same assertion as `DuplicateTemplateTest::it_copies_a_supplied_template_into_an_ordinary_one`. |
| Feature/Controllers/App/Settings/Account/Templates/TemplateSectionPositionControllerTest.php | 4 | 1 | D 1 | `StructureVersionSection` uses `ResolvesModelInAccount`: `ResolvesModelInAccountTest` plus `RouteProtectionTest`. |
| Feature/Controllers/App/Settings/Account/Templates/FieldControllerTest.php | 32 | 13 | D 9, C 2, L 2 | Nine tests re-assert `FieldCatalogueViewModelTest` rules through Alpine strings (`it_describes_every_type_it_offers`, `it_says_in_one_sentence_what_the_panel_is_previewing`, `it_indexes_every_type_for_the_filter`, `it_names_the_section_and_the_type_in_the_footer`, `it_says_which_types_need_a_value_list`, `it_offers_a_label_list_for_an_email_a_phone_and_an_address`, `it_names_the_supplied_list_of_labels_each_labelled_type_starts_from`). Key left alone is `UpdateFieldTest::it_leaves_the_key_alone_when_the_label_changes`. Assembly of another account is the trait. |
| Feature/Controllers/App/Settings/Account/Templates/TemplateBuilderControllerTest.php | 27 | 22 | C 12, D 8, L 2 | 12 tests assert aria attributes counts, `cursor-grab`, `absolute top-full`, `x-anchor`, `x-show` regexes, `@click.stop`, `background:` styles and a DOM lint of `$refs`. 8 repeat `TemplateBuilderViewModelTest` rules (opens the field asked for, pages as tabs, move to another page, history, sharing, glyph and colour lists, read only version). |
| Feature/Controllers/App/Settings/Account/Templates/TemplateSectionControllerTest.php | 20 | 12 | D 9, L 3 | Nine repeat `AddSectionToStructureVersionTest`, `CreateSectionTest`, `UpdateSectionTest`, `UpdateStructureVersionSectionTest` or the trait. Three are second or third validation variants of the mark rule. |
| Feature/Controllers/App/Settings/Account/Templates/BuilderFlowTest.php | 2 | 1 | D 1 | `it_gives_a_shared_section_its_own_version_before_editing_it` is `MakeSectionVersionEditableTest::it_gives_a_published_section_a_new_version` and `FieldControllerTest::it_copies_a_published_section_rather_than_writing_to_it`. |
| Feature/Controllers/App/Settings/Account/Templates/TemplatePageControllerTest.php | 9 | 2 | D 1, L 1 | Published refusal is `CreateStructureVersionPageTest::it_refuses_to_add_a_page_to_a_published_structure_version`. A second "page of another account" test with the same mechanism. |
| Feature/Controllers/App/Settings/Account/Templates/TemplateSectionPageControllerTest.php | 4 | 2 | D 2 | Published is `MoveSectionToStructureVersionPageTest::it_refuses_to_move_a_section_on_a_published_structure_version`; other account is the trait. |
| Feature/Controllers/App/Settings/Account/Templates/SectionImportControllerTest.php | 9 | 4 | D 1, L 3 | Page not on template is `ImportSectionTest::it_refuses_a_page_that_is_not_on_this_template`. Two link presence tests and the "no mark" variant. |
| Feature/Controllers/App/Settings/Account/Templates/SectionImportCheckControllerTest.php | 4 | 1 | D 1 | Published is `ImportSectionTest::it_refuses_a_version_nobody_can_change` (and is already asserted in `SectionImportControllerTest`). |
| Feature/Controllers/App/Settings/Account/Templates/SectionShareControllerTest.php | 4 | 1 | D 1 | Other template is `ExportSectionTest::it_refuses_a_section_on_another_template`. |
| Feature/Controllers/App/Settings/Account/Templates/TemplateShareControllerTest.php | 4 | 2 | D 1, L 1 | Unpublished is `TemplateExportControllerTest::it_is_not_offered_for_a_template_nobody_has_published` and `ExportTemplateTest::it_refuses_a_template_nobody_has_published`. Static copy. |
| Feature/Controllers/App/Settings/Account/Templates/TemplateImportControllerTest.php | 5 | 2 | L 2 | Link presence; empty file variant of the unreadable file refusal. |
| Feature/Controllers/App/Settings/Account/Templates/TemplateImportCheckControllerTest.php | 6 | 2 | D 1, L 1 | One section file is `ImportTemplateTest::it_refuses_a_file_holding_one_section`. Echo of the pasted text. |
| Feature/Controllers/App/Settings/Account/Templates/StructureVersionHistoryControllerTest.php | 2 | 1 | L 1 | A GET that does not write. |
| Feature/Controllers/App/Settings/Account/Templates/StructureVersionPublicationControllerTest.php | 6 | 2 | D 2 | `PublishStructureVersionTest::it_publishes_the_draft_sections_it_carries` and `::it_publishes_a_structure_version_whose_sections_are_all_published`. |

Methods to remove, grouped by file. Paths are relative to `tests/Feature/Controllers/App/Settings/`.

- `SettingsControllerTest`: it_points_to_everything_it_names; it_navigates_the_settings_rather_than_any_vault; it_leads_back_out_to_the_vaults_whatever_the_default_is; it_navigates_an_account_that_has_made_no_vault_at_all; it_marks_no_destination_as_current_on_the_settings_home; it_only_reaches_the_vaults_of_the_account
- `Account/AccountControllerTest`: it_refuses_to_delete_the_account_without_a_confirmed_password; it_refuses_to_delete_the_account_when_nothing_was_typed; it_leaves_the_account_of_somebody_else_alone; it_shows_the_way_to_delete_the_account_on_the_profile_screen; it_says_nothing_about_a_subscription_there_is_none_of
- `Account/Profile/EmailControllerTest`: it_refuses_an_address_that_is_not_one; it_refuses_the_address_without_a_confirmed_password
- `Account/Profile/PhotoControllerTest`: it_refuses_a_file_that_is_too_large; it_shows_the_photo_at_both_densities_on_the_profile_screen; it_falls_back_to_the_initials_when_there_is_no_photo
- `Account/Profile/ProfileControllerTest`: it_points_back_to_the_settings; it_carries_the_settings_column; it_offers_the_photo_and_the_details_as_two_separate_forms; the_button_that_removes_the_photo_is_worded; it_saves_the_names_without_a_confirmed_password
- `Account/Preferences/PreferenceControllerTest`: it_shows_the_time_zone_section; it_hands_the_map_to_the_registered_picker_rather_than_writing_it_in_an_attribute; it_hands_the_zones_to_the_picker_rather_than_writing_a_row_for_each; it_says_the_zone_was_the_nearest_one_when_it_came_from_the_map; it_says_nothing_about_the_nearest_one_when_the_zone_was_named; it_refuses_a_time_format_that_does_not_exist; it_saves_the_date_format; it_hands_the_numbers_back_to_the_language_when_the_format_is_left_empty; it_saves_the_number_format; it_refuses_a_number_format_that_does_not_exist; it_offers_the_choice_of_what_an_address_is_written_with; it_refuses_a_choice_that_is_neither
- `Account/Preferences/TimezoneControllerTest`: it_says_a_zone_reached_by_pointing_was_a_guess (fold `timezone_guess` true into the point test); it_leaves_the_language_and_the_formats_where_they_were; it_sets_the_zone_the_device_reports; it_refuses_a_device_that_reported_nothing; it_refuses_a_form_that_arrives_entirely_empty; it_sets_the_nearest_zone_when_the_map_posts_a_point_alone
- `Account/Security/SecurityControllerTest`: it_offers_to_turn_two_factor_authentication_on_when_it_is_off; it_says_how_many_recovery_codes_are_left; it_says_so_when_every_recovery_code_has_been_used; it_refuses_to_remove_a_passkey_without_a_confirmed_password; it_lists_the_security_keys_and_offers_to_add_one; it_says_a_passkey_skips_the_two_factor_code_where_passkeys_and_two_factor_are; it_moves_focus_through_the_removal_of_a_passkey_and_ties_the_warning_to_the_button
- `Account/Security/PasswordControllerTest`: it_leaves_a_browser_that_was_not_remembered_unremembered; it_refuses_a_new_password_that_is_too_short; it_refuses_to_remove_the_password_without_a_confirmed_one; it_refuses_to_change_the_password_without_a_confirmed_one
- `Account/Security/ApiKeyControllerTest`: it_prints_the_new_key_once_it_is_made; it_has_somewhere_to_announce_what_a_copy_did; it_offers_to_copy_the_new_key_and_says_when_it_did; it_says_when_the_copy_did_not_happen; it_refuses_a_missing_name; it_refuses_to_create_a_key_without_a_confirmed_password; it_leaves_sensitive_fields_out_of_a_key_when_the_box_is_left_alone
- `Account/Security/TwoFactorControllerTest`: it_asks_for_a_code; it_refuses_to_turn_two_factor_off_without_a_confirmed_password; it_refuses_to_start_enrolling_without_a_confirmed_password
- `Account/Security/SsoControllerTest`: it_disconnects_when_a_passkey_is_left; it_refuses_to_reach_the_provider_without_a_confirmed_password; it_refuses_to_disconnect_without_a_confirmed_password
- `Account/Security/SecurityKeyControllerTest`: it_asks_for_a_name_when_renaming_a_key
- `Account/Security/McpCallControllerTest`: it_says_what_will_land_here_when_nothing_has_been_called; it_says_nothing_matches_rather_than_nothing_happened; it_is_reached_from_the_security_screen
- `Account/Security/RecoveryCodeControllerTest`: it_refuses_new_codes_without_a_confirmed_password; it_shows_the_codes_once_after_regenerating
- `Account/Security/SecurityKeyOptionsControllerTest`: it_asks_somebody_with_a_password_to_confirm_it_first
- `Account/Security/ConnectedAssistantControllerTest`: it_asks_for_the_password_before_disconnecting; it_lists_the_connected_assistants_on_the_security_screen
- `Account/Notifications/ChannelProofControllerTest`: a_code_needs_typing_in
- `Account/Notifications/ChannelTestControllerTest`: a_test_writes_no_notification_and_touches_no_deadline; it_refuses_a_test_on_a_channel_this_server_cannot_send_on
- `Account/Notifications/NotificationChannelControllerTest`: telegram_asks_nobody_to_type_a_conversation_identifier; a_connected_telegram_says_so_and_offers_to_move; connecting_is_refused_when_the_server_has_no_bot; nothing_can_be_switched_on_when_no_integration_is_installed
- `Account/Notifications/TelegramWebhookControllerTest`: it_refuses_a_call_carrying_no_secret_at_all; an_update_carrying_no_message_is_ignored
- `Account/Logs/EmailSentControllerTest`: it_shows_a_blank_state_when_nothing_was_ever_sent; it_offers_to_load_more_when_a_page_is_not_enough; it_stops_offering_to_load_more_on_the_last_page; it_badges_an_email_that_arrived; it_badges_an_email_that_came_back; it_badges_an_email_that_came_back_after_it_was_reported_delivered; it_badges_an_email_the_recipient_marked_as_spam; it_badges_a_complaint_over_a_bounce_and_a_delivery (keep `it_badges_an_email_that_is_still_on_its_way` and turn the three badge tests above into one data provider)
- `Account/Logs/LogControllerTest`: it_shows_the_address_each_action_came_from; it_shows_no_address_for_an_action_that_came_from_nowhere; it_shows_a_blank_state_when_there_is_nothing_to_read_yet; it_offers_to_load_more_when_a_page_is_not_enough; it_stops_offering_to_load_more_on_the_last_page; it_shows_the_latest_emails_sent_and_offers_to_browse_the_rest
- `Account/Billing/BillingControllerTest`: it_tells_a_subscriber_where_they_stand_and_how_to_stop_the_renewal; it_offers_a_subscription_that_is_ending_to_be_kept; it_offers_nothing_further_to_an_account_granted_full_access; it_shows_what_a_lifetime_holder_paid_and_leads_to_the_invoices; it_says_there_is_nothing_to_pay_for_on_a_self_hosted_instance; it_says_the_payment_is_being_confirmed_on_the_way_back_from_stripe; it_changes_nothing_when_the_buyer_came_back_from_an_abandoned_checkout; it_keeps_working_when_stripe_does_not_answer
- `Account/Billing/BillingCurrencyControllerTest`: it_needs_a_currency; it_shows_the_offers_in_the_currency_that_was_picked
- `Account/Billing/CheckoutControllerTest`: it_says_so_and_charges_nothing_when_stripe_does_not_answer
- `Account/Billing/CheckoutReturnControllerTest`: it_says_the_same_when_stripe_cannot_be_reached; it_settles_nothing_for_a_session_belonging_to_another_account
- `Account/Billing/InvoiceControllerTest`: it_pages_the_payments_twenty_at_a_time; it_leaves_another_account_payments_out_of_the_list; it_counts_the_payments_on_the_tab
- `Account/Billing/SubscriptionCancellationControllerTest`: it_shows_the_keep_button_while_a_subscription_is_ending
- `Account/Billing/SubscriptionControllerTest`: it_refuses_a_move_on_an_account_with_no_subscription; it_shows_the_cancel_warning_and_the_end_date_to_a_subscriber; it_offers_the_yearly_switch_as_a_confirmed_change_rather_than_a_purchase
- `Account/Billing/SubscriptionPortalControllerTest`: it_is_not_there_for_an_account_that_was_only_granted_access; it_links_to_the_portal_from_the_billing_screen; it_links_to_the_portal_from_the_lifetime_panel; it_offers_no_such_link_to_somebody_who_was_granted_access
- `Account/Transfer/AccountExportControllerTest`: it_refreshes_the_screen_only_while_an_export_is_being_prepared; it_asks_for_the_password_before_preparing_an_export; it_asks_for_every_vault_when_none_is_chosen
- `Account/Transfer/AccountImportControllerTest`: it_asks_for_the_password_before_taking_a_file; it_starts_a_file_of_several_vaults_without_a_vault_name; it_answers_not_found_for_a_vault_of_another_account
- `Account/ValueLists/ValueListControllerTest`: it_offers_to_add_a_list_beside_the_title_rather_than_under_the_lists; it_asks_for_the_name_of_a_new_list_in_a_card; it_warns_in_a_card_before_a_list_is_deleted; it_refuses_a_rename_without_a_name
- `Account/ValueLists/ValueListItemActivationControllerTest`: it_says_on_the_screen_that_a_value_is_no_longer_offered
- `Account/ValueLists/ValueListItemControllerTest`: it_refuses_a_rename_without_a_name; it_shows_stop_offering_rather_than_delete_for_a_value_in_use; it_warns_in_a_card_before_a_value_is_deleted
- `Account/EntityTypes/EntityTypeControllerTest`: it_explains_what_a_profile_type_is; it_offers_no_ordering_without_a_type_of_its_own; it_says_when_a_type_is_not_active; it_shows_the_form_that_declares_a_type; it_creates_a_type_that_is_not_active_yet; it_takes_the_slug_it_is_given; it_falls_back_to_the_singular_name_when_no_slug_is_given; it_refuses_a_slug_a_deleted_type_still_holds; it_ignores_a_slug_sent_to_the_update_route; it_says_the_description_may_be_left_out
- `Account/LifeEventTypes/LifeEventCategoryActivationControllerTest`: it_switches_a_supplied_category_back_on
- `Account/LifeEventTypes/LifeEventTypeControllerTest`: it_lists_the_types_of_one_category; it_sends_the_reader_to_the_address_of_the_category_they_choose; it_shows_a_type_that_is_switched_off_rather_than_hiding_it; it_refuses_a_kind_of_field_that_does_not_exist; it_refuses_a_group_with_no_name; it_offers_a_retired_type_again_in_the_same_save
- `Account/RelationshipTypes/RelationshipCategoryControllerTest`: it_refuses_a_rename_with_no_name_and_says_so_beside_the_category
- `Account/RelationshipTypes/RelationshipTypeControllerTest`: the_list_carries_the_target_the_move_buttons_swap; it_shows_the_form_that_adds_a_type; it_refuses_a_name_the_account_already_uses; it_shows_the_form_that_rewords_a_type; it_offers_deletion_for_a_type_nobody_uses
- `Account/Templates/TemplateControllerTest`: it_narrows_the_list_from_the_query_string; it_creates_a_template_that_describes_a_kind_of_record; it_refuses_a_template_with_no_name; it_shows_the_form_that_renames_a_template
- `Account/Templates/TemplateCopyControllerTest`: it_copies_a_supplied_template_into_an_ordinary_one
- `Account/Templates/TemplateSectionPositionControllerTest`: it_refuses_an_assembly_from_another_account
- `Account/Templates/FieldControllerTest`: it_draws_the_field_every_type_produces; it_says_in_words_what_the_panel_is_previewing; it_offers_a_filter_over_the_catalogue; it_names_the_section_and_the_chosen_type_in_the_footer; it_puts_the_bar_outside_the_form; it_asks_where_the_choices_come_from; it_asks_where_the_labels_come_from; it_picks_the_supplied_list_of_labels_for_a_type_that_has_one; it_offers_an_email_and_a_phone_without_demanding_a_list_of_them; it_adds_a_choice_field_with_the_list_it_reads_from; it_leaves_the_key_alone_when_the_label_changes; it_refuses_a_field_with_no_label; it_refuses_an_assembly_from_another_account
- `Account/Templates/TemplateBuilderControllerTest`: it_says_that_a_section_and_a_field_can_be_dragged; it_hides_the_grip_from_a_screen_reader; it_marks_a_repeater_on_the_list_only_when_it_collects_at_the_front; it_opens_the_field_the_address_names; it_stays_on_the_page_a_field_was_opened_from; it_offers_sharing_the_template_and_its_sections_once_they_are_published; it_says_a_template_with_no_field_open_has_none; it_shows_the_pages_as_tabs; it_offers_to_move_a_section_only_once_there_is_another_page; it_offers_the_version_history_once_the_template_has_more_than_one_version; it_offers_the_mark_picker_on_the_add_block; it_names_every_glyph_and_colour_in_the_picker_so_they_can_be_heard; it_draws_the_picker_only_where_the_version_can_be_changed; it_offers_the_mark_picker_on_each_section_as_well_as_the_add_block; it_never_leaves_the_mark_as_the_only_difference_between_two_sections; it_pins_the_mark_picker_out_of_the_flow_so_the_section_card_cannot_clip_it; it_closes_the_mark_picker_when_a_save_is_on_its_way; it_leaves_exactly_one_glyph_showing_in_the_tile_before_alpine_boots; it_picks_a_colour_from_a_solid_swatch_rather_than_from_the_pale_pair_it_draws; it_keeps_the_mark_picker_open_while_a_glyph_and_a_colour_are_being_picked; it_posts_the_stored_mark_even_before_alpine_has_bound_the_fields; it_declares_every_ref_in_the_scope_that_reaches_for_it
- `Account/Templates/TemplateSectionControllerTest`: it_puts_the_section_on_the_first_page_when_none_is_named; it_gives_the_new_section_a_draft_version; it_refuses_an_assembly_from_another_account; it_puts_the_new_section_on_the_template_wearing_its_mark; it_creates_the_section_unmarked_when_no_mark_is_asked_for; it_refuses_a_colour_the_set_does_not_offer; it_changes_the_mark_on_the_section_itself; it_changes_the_mark_on_every_template_carrying_the_section; it_clears_the_mark_when_the_form_asks_for_none; it_refuses_a_mark_from_outside_the_set_when_changing_one; it_explains_why_a_published_version_takes_no_change_of_mark; it_refuses_a_change_of_mark_reached_through_another_account
- `Account/Templates/BuilderFlowTest`: it_gives_a_shared_section_its_own_version_before_editing_it
- `Account/Templates/TemplatePageControllerTest`: it_refuses_a_page_on_a_published_version; it_does_not_find_a_page_of_another_account
- `Account/Templates/TemplateSectionPageControllerTest`: it_refuses_to_move_a_section_on_a_published_version; it_does_not_find_a_section_of_another_account
- `Account/Templates/SectionImportControllerTest`: it_offers_the_import_from_the_builder_of_a_draft; it_does_not_offer_the_import_on_a_published_version; it_imports_a_section_carrying_no_mark_onto_the_defaults; it_refuses_a_page_that_is_not_on_this_template
- `Account/Templates/SectionImportCheckControllerTest`: it_refuses_a_published_version
- `Account/Templates/SectionShareControllerTest`: it_does_not_show_a_section_on_another_template
- `Account/Templates/TemplateShareControllerTest`: it_says_what_never_leaves; it_is_not_offered_for_a_template_nobody_has_published
- `Account/Templates/TemplateImportControllerTest`: it_offers_the_import_from_the_list_of_templates; it_asks_for_something_to_read
- `Account/Templates/TemplateImportCheckControllerTest`: it_refuses_a_file_holding_one_section; it_puts_what_was_pasted_back_in_the_box
- `Account/Templates/StructureVersionHistoryControllerTest`: it_changes_nothing_it_shows
- `Account/Templates/StructureVersionPublicationControllerTest`: it_publishes_the_sections_it_carries; it_leaves_a_section_that_was_already_published_alone

### Patterns
- **A route gated by `password.confirm` is proven once per route** (15 Declaration tests, 20 including other declarations). Examples: `PasswordControllerTest::it_refuses_to_remove_the_password_without_a_confirmed_one`, `ApiKeyControllerTest::it_refuses_to_create_a_key_without_a_confirmed_password`, `AccountExportControllerTest::it_asks_for_the_password_before_preparing_an_export`. `RouteProtectionTest` does not check this middleware, and the framework owns the redirect. One data provider listing the gated routes and asserting the redirect to `auth.confirmPassword.new` replaces all of them. The Keep tests of the same controllers keep `withConfirmedPassword()` and still prove the routes work behind the gate.
- **The controller test re-asserts the view model** (about 55 tests, mostly in Duplicate). Examples: eight in `TemplateBuilderControllerTest` against `TemplateBuilderViewModelTest`, nine in `FieldControllerTest` against `FieldCatalogueViewModelTest`, seven in `BillingControllerTest` against `BillingViewModelTest`, four in `LogControllerTest` against `LogsViewModelTest`. `testing.md` says a screen needs one rendering test and the rest is the view model's.
- **Action refusals repeated one layer up** (about 25 tests). Examples: `TemplateSectionControllerTest::it_explains_why_a_published_version_takes_no_change_of_mark`, `CheckoutReturnControllerTest::it_settles_nothing_for_a_session_belonging_to_another_account`, `TemplateCopyControllerTest::it_copies_a_supplied_template_into_an_ordinary_one` (same name as `DuplicateTemplateTest`). `testing.md` allows one per controller.
- **"Another account" tests for models that already use `ResolvesModelInAccount`** (5 tests): `TemplateSectionControllerTest::it_refuses_an_assembly_from_another_account`, `TemplateSectionPositionControllerTest::it_refuses_an_assembly_from_another_account`, `FieldControllerTest::it_refuses_an_assembly_from_another_account`, `TemplateSectionPageControllerTest::it_does_not_find_a_section_of_another_account`, `TemplateSectionControllerTest::it_refuses_a_change_of_mark_reached_through_another_account`. Controller scoped models (ValueList, ValueListItem, StructureVersion, StructureVersionPage, Passkey, SecurityKey, Payment, API keys) correctly keep theirs.
- **Cosmetic assertions of Alpine, ids and classes** (26 tests). `TemplateBuilderControllerTest` is the worst: 12 of 27 assert `cursor-grab`, `absolute top-full`, `x-anchor.bottom-start.offset.6`, `x-show` regexes, `@click.stop`, `background:` and a DOM lint of `$refs`. Others: `PreferenceControllerTest::it_hands_the_map_to_the_registered_picker_rather_than_writing_it_in_an_attribute`, `ProfileControllerTest::the_button_that_removes_the_photo_is_worded`, `SecurityControllerTest::it_moves_focus_through_the_removal_of_a_passkey_and_ties_the_warning_to_the_button`.
- **One validation test per field or per format** (83 Low value, most of them). Examples: three date, time and number format refusals and saves in `PreferenceControllerTest`; four empty form variants in `TimezoneControllerTest`; "rename without a name" repeating "add without a name" in `ValueListControllerTest`, `ValueListItemControllerTest` and `RelationshipCategoryControllerTest`. A `#[DataProvider]` of invalid payloads per route would hold the same branches in one method each.
- **Copy and link presence checks** (about 20). Examples: `SettingsControllerTest::it_points_to_everything_it_names`, `TemplateImportControllerTest::it_offers_the_import_from_the_list_of_templates`, `SubscriptionControllerTest::it_shows_the_cancel_warning_and_the_end_date_to_a_subscriber`.
- **Tests that cannot fail.** `ChannelTestControllerTest::a_test_writes_no_notification_and_touches_no_deadline` asserts `assertDatabaseCount('notifications', 0)` after `Notification::fake()`. `StructureVersionHistoryControllerTest::it_changes_nothing_it_shows` asserts a GET leaves a timestamp alone. `InvoiceControllerTest::it_counts_the_payments_on_the_tab` is named for a count but asserts a link.
- **A test in the wrong file.** `SsoControllerTest::it_connects_the_provider_when_the_visitor_comes_back` exercises `auth.sso.callback`, an Auth controller route, from the Settings folder.

### Cost hot spots
Nothing here reaches a real external service: Stripe is `fakeStripe()`, Telegram and Vonage are config switches, WebAuthn uses a software authenticator. The cost is volume and fixture size, all of it database backed (74 of 74 files use `RefreshDatabase`).
- `AccountImportControllerTest`: `setUp()` runs `CreateDefaultTemplate` for every one of 12 tests, and 7 call `readImport()`, which loads `tests/Fixtures/Imports/monica-4-small.json` and runs `ReadMonicaFourFile`. Build the default template only in the tests that start a write, and read the file once per test only where the page needs it. Removing three tests here also saves three of those setups.
- `PhotoControllerTest`: seven tests run the real `UpdateUserPhoto` (image decode, resize to several sizes, file writes to a faked disk). The upload test needs it; the serve tests could write a one pixel file per size straight to the fake disk, or share one prepared user per class.
- `TemplateBuilderControllerTest`: 27 tests each build a template, a version, a section, a section version and fields, then render the largest page in the settings area (37 glyph buttons and nine swatches per picker). Removing the 22 listed drops about the slowest sixth of the controller suite. The `$refs` lint parses the whole page with DOM and XPath.
- `FieldControllerTest`, `ChildFieldControllerTest`, `FieldPositionControllerTest`, `TemplateSectionControllerTest`: each re-creates the same six model graph (`templateCarryingASection`) in every test. Four files each define their own copy. A shared trait or fixture factory would write it once.
- Eight files (`SectionImportControllerTest`, `SectionImportCheckControllerTest`, `SectionExportControllerTest`, `SectionShareControllerTest`, `TemplateShareControllerTest`, `TemplateExportControllerTest`, `TemplateImportControllerTest`, `TemplateImportCheckControllerTest`) each hold a private 30 to 45 line `publishedTemplate`/`publishedRow` builder that creates about eight records and calls `save()` four extra times. They are copies of one another.
- Loops of requests in one test: `EntityTypeControllerTest::it_refuses_a_slug_that_is_not_a_slug` makes six full requests, `TelegramWebhookControllerTest::ordinary_chatter_in_the_conversation_is_ignored` makes four. A data provider would give the same coverage with a clearer failure message and no extra cost per branch.
- Row volume where one row would do: `InvoiceControllerTest::it_pages_the_payments_twenty_at_a_time` creates 25 payments and `LogControllerTest::it_offers_to_load_more_when_a_page_is_not_enough` creates 11 logs, both to prove a page size that the view model test already proves. `McpCallControllerTest::it_narrows_the_history_and_keeps_the_filters_across_pages` creates 12 rows; configuring the page size to 2 would need only 3.
- Both setups of `ChannelSetupControllerTest` and `ChannelProofControllerTest` create an `Account` and a `User` through two factories and then query the channel again in every helper call. Minor.

### Proposed guidelines
- A controller test proves one rendering of a screen, then stops. Anything about what the screen lists, counts, orders, pages or labels goes in the view model test (found: 55 Duplicate tests).
- Do not assert pagination, "Load more" or page size in a controller test. `ListPanelTest` and the view model own them (found: `LogControllerTest`, `EmailSentControllerTest`, `InvoiceControllerTest`).
- Prove `password.confirm` once, with one data provider over the gated routes, not once per controller (found: 15 tests).
- A controller test asserts one refusal from the action, not one per refusal the action has. List the refusal in the action test only (found: about 25 tests).
- Do not write an "another account" test for a model that uses `ResolvesModelInAccount`. Write it for a controller that scopes its own query (found: 5 tests).
- Validation tests per route are one `#[DataProvider]` of invalid payloads, never one method per field, format or variant (found: 83 Low value tests, mostly validation and empty input variants).
- Never assert an Alpine expression, an element id used only by JavaScript, a class, an inline style, or a count of aria attributes. The `x-delete-action`, `x-copy-button`, `x-position-form` and `x-sortable-row` component tests own those contracts (found: 26 Cosmetic tests, and four Duplicate ones against `CopyButtonTest` and `DeleteActionTest`).
- A test must be able to fail. Do not assert "nothing was written" after faking the writer, and do not assert that a GET leaves data alone unless the route could write.
- A helper that builds the same fixture graph belongs in one place under `tests/`, not in eight private methods (found: `publishedTemplate`, `publishedRow`, `templateCarryingASection`).
- A test for a route belongs in the file for the controller that owns the route (found: the SSO callback test in `Settings/Account/Security`).

### Open questions
- **Blade branches behind view model flags.** The seven `BillingControllerTest` Duplicates, `SubscriptionPortalControllerTest` link tests and `AccountControllerTest::it_warns_that_deleting_the_account_stops_the_subscription` (kept) depend on Blade choosing copy from a flag the view model proves (for example `BillingViewModelTest::it_says_the_payment_is_being_confirmed_on_the_way_back_from_stripe` asserts `isAwaitingConfirmation()` and nothing about what is drawn). If the team wants the Blade branch itself guarded, keep one data provider test over the six states (free, subscriber, ending, lifetime, granted, self hosted) instead of the eight tests. I looked at `BillingViewModelTest` and `BillingControllerTest` but did not read `billing/index.blade.php`.
- **`ProfileControllerTest::it_shows_the_toast_of_the_previous_save`** is the only test of the flash toast outside `ContactControllerTest`, and no layout test covers it (`tests/Feature/Views/Layouts` has none). I kept it. It probably belongs in a layout test, not in a Profile test.
- **`NotificationChannelControllerTest::it_says_when_the_channel_being_switched_off_is_the_last_one_that_works`** is kept. `NotificationChannelsViewModelTest::it_counts_the_channels_that_actually_work` counts, but I found no test that proves the warning sentence, so I treated the sentence as Blade logic.
- **`SectionImportControllerTest::it_imports_the_section_wearing_the_mark_it_was_exported_with`** is kept because none of the `ImportSectionTest` method names mention the mark. I did not open `ImportSectionTest` to see whether an assertion inside `it_puts_the_section_the_file_describes_on_the_page` already covers it.
- **`EntityTypeControllerTest::it_finds_the_type_of_the_signed_in_account_when_two_share_a_slug`** is kept. `ResolvesModelInAccountTest` proves resolving by a named column and refusing other spellings; I did not confirm it proves two accounts sharing the same slug. If it does, this one is a Duplicate.
- **`TemplatePagePositionControllerTest::it_refuses_to_reorder_the_pages_of_a_published_version`** is kept because `UpdateStructureVersionPagePositionTest` has no published version refusal test by name. If the action has such a guard it is untested at the action layer, and this controller test is the only coverage. A test should move down to the action.
- **Timings.** I did not run any test, so the cost section is from reading, not from measured durations. `docs/TEST_SUITE_AUDIT.md` gives 60 ms per test for `Feature/Controllers/App` overall (85 s for 1,413 tests locally), which puts this area at about 35 s locally if it is typical.

> Checked by the coordinator: `Settings/Account/Templates/TemplateBuilderControllerTest.php` matches `cursor-grab` with a regular expression (line 86), counts exactly 37 `aria-pressed` buttons (line 384), and asserts `absolute top-full` is absent (line 463); all three describe the drawing. `RouteProtectionTest` never mentions `password.confirm`, so removing the 15 per route redirect tests is only safe once the single data provider test over the gated routes exists. Write it first.

## Feature/Controllers/App/Admin, Auth, Docs, Marketplace and Feature/Auth

I read every file in tests/Feature/Controllers/App/Admin (20 files, 101 test methods), tests/Feature/Controllers/App/Auth (15 files, 154), tests/Feature/Controllers/App/Docs (11 files, 46), tests/Feature/Controllers/App/Marketplace (1 file, 15) and tests/Feature/Auth (12 files, 77), counted with grep -c '#[Test]'. Total 393 methods. Data provider cases are not counted separately. I compared them with the action tests (DeactivateUser, WithdrawAdministratorAccess, GrantLifetimeAccess, UpdateAccountStorageLimit, ApproveMarketplaceListingVersion, ResetPassword, AttemptLogin, ConsumeMagicLink, SignInWithProvider and others), the view model tests for Admin, Auth, Docs and Marketplace, Feature/Views/Components/PaginationTest, Unit/Helpers/PageSizeTest, Unit/Listeners/RecordEmailSentTest and RouteProtectionTest. By testing.md these controller tests should prove that the route answers, that inline validation refuses, that the redirect and flash have their shape, and that one action refusal reaches the reader. They should not re-prove what an action writes, how a view model counts or paginates, or the admin middleware. The main problems are: the same assertion repeated per controller (pagination, password confirmation, signed links, "remembers the browser"), whole files that re-test a login that is tested elsewhere (ProviderRegressionTest), and Blade copy and markup assertions that a view model test already owns.

### Totals
| Bucket | Tests |
| --- | --- |
| Keep | 239 |
| Duplicate | 72 |
| Cosmetic | 6 |
| Declaration | 6 |
| Low value | 70 |
| Total | 393 |

By area: Admin 101 (remove 44), Auth controllers 154 (remove 50), Docs 46 (remove 13), Marketplace 15 (remove 2), Feature/Auth 77 (remove 45).

### Findings by file
| File (path relative to tests/) | Tests | Remove | Bucket(s) | Why, citing the owning test when Duplicate |
| --- | --- | --- | --- | --- |
| Feature/Controllers/App/Admin/AdminAccessTest.php | 8 | 2 | Duplicate, Low value | The front door test is covered by it_opens_for_an_administrator over the same URL (same file). Guessed address comparison adds nothing to the not found provider tests. |
| Feature/Controllers/App/Admin/OverviewControllerTest.php | 7 | 6 | Duplicate, Low value | Email breakdown (three tests) duplicates Unit/ViewModels/Admin/OverviewViewModelTest::it_counts_the_emails_that_left_and_where_each_got_to and ::it_counts_no_email_on_an_instance_that_has_sent_none. "First thing an administrator sees" duplicates AdminAccessTest::it_opens_for_an_administrator. Moderation link and empty queue copy are one count each, owned by OverviewViewModelTest::it_counts_the_submissions_nobody_has_decided. Keep it_says_what_period_every_figure_covers as the one render test. |
| Feature/Controllers/App/Admin/AccountStorageLimitControllerTest.php | 5 | 2 | Duplicate, Low value | a_limit_under_what_is_held duplicates Unit/Actions/UpdateAccountStorageLimitTest::it_deletes_nothing_when_the_new_limit_is_under_what_is_already_held and Unit/Actions/CreateFileTest (NotEnoughStorageLeft). The text form test repeats it_sets_a_limit (an HTTP value is always text). |
| Feature/Controllers/App/Admin/InstanceSettingControllerTest.php | 10 | 4 | Duplicate | it_shows_the_configuration_screen duplicates Feature/Views/App/Admin/ConfigurationTest::it_names_every_setting_and_points_its_form_at_the_route_that_saves_it. it_governs_the_next_request duplicates Unit/Helpers/InstanceSettingsTest::it_puts_a_stored_value_in_front_of_the_deployment_one. Two of the three "asks for a credential" tests are the same middleware group (see Patterns). |
| Feature/Controllers/App/Admin/AccountControllerTest.php | 7 | 3 | Duplicate, Low value | Flags drawn only once declared duplicates Unit/ViewModels/Admin/AccountViewModelTest::it_lists_each_declared_flag_with_its_state_for_this_account and ::it_lists_no_flag_when_none_is_declared. Page size from the URL is owned by Unit/Helpers/PageSizeTest::it_pages_a_query_at_the_size_asked. Nothing matches copy is a view model case (AccountsViewModelTest::it_finds_nothing_when_nothing_matches). |
| Feature/Controllers/App/Admin/AccountLifetimeAccessControllerTest.php | 5 | 3 | Duplicate, Low value | Refunded order duplicates Unit/Actions/WithdrawLifetimeAccessTest::it_marks_the_order_refunded_when_the_purchase_is_taken_back. Own account duplicates GrantLifetimeAccessTest::it_lets_an_administrator_grant_it_to_their_own_account. "Writes no order" is a negative nobody would introduce. |
| Feature/Controllers/App/Admin/TelegramWebhookSecretControllerTest.php | 2 | 1 | Duplicate | Credential gate, same middleware group as the other six routes. |
| Feature/Controllers/App/Admin/AdministratorLogControllerTest.php | 7 | 5 | Duplicate, Low value | Narrowing by account duplicates Unit/ViewModels/Admin/AdministratorLogsViewModelTest::it_narrows_to_one_account. Both pagination tests duplicate PaginationTest and PageSizeTest. "Nothing done yet" is a copy variant. it_shows_everything_support_did_to_an_account drives five other controllers and re-asserts what each action writes (e.g. DeactivateUserTest::it_writes_the_deactivation_down). Keep the first two tests (render, and the administrator filter reaching the view model). |
| Feature/Controllers/App/Admin/TelegramRegistrationControllerTest.php | 4 | 2 | Duplicate | Credential gate (same middleware group). Non administrator not found duplicates RouteProtectionTest::every_route_of_the_admin_space_is_closed_to_whoever_does_not_run_the_instance and AdminAccessTest. |
| Feature/Controllers/App/Admin/ModerationApprovalControllerTest.php | 3 | 1 | Duplicate | Attribution duplicates Unit/Actions/ApproveMarketplaceListingVersionTest::it_writes_the_decision_down_against_the_administrator. |
| Feature/Controllers/App/Admin/AdministratorControllerTest.php | 9 | 5 | Duplicate, Low value | Closing the area duplicates WithdrawAdministratorAccessTest::it_closes_the_admin_space_to_somebody. Last administrator duplicates WithdrawAdministratorAccessTest::it_refuses_to_leave_the_instance_with_nobody and AdministratorsViewModelTest::it_knows_when_there_is_only_one_left. Two pagination tests. "Warns before a grant" is copy only. |
| Feature/Controllers/App/Admin/ModerationControllerTest.php | 8 | 5 | Duplicate, Low value | Decision only while open duplicates ModerationSubmissionViewModelTest::it_knows_a_decision_is_still_open and ::it_knows_a_decision_has_already_been_taken. Two pagination tests. Nothing waiting is a copy variant. The resubmission test asserts a rejection reason the factory just wrote. |
| Feature/Controllers/App/Admin/NoVaultContentTest.php | 3 | 1 | Duplicate | the_count_of_what_a_vault_holds duplicates AccountViewModelTest::it_counts_what_the_account_holds_without_naming_any_of_it. The other two are real privacy invariants and stay. |
| Feature/Controllers/App/Admin/UserActivationControllerTest.php | 6 | 2 | Duplicate | it_never_offers_to_deactivate_you duplicates AccountViewModelTest::it_knows_the_operator_is_looking_at_their_own_account. it_records_what_was_done duplicates DeactivateUserTest::it_writes_the_deactivation_down. |
| Feature/Controllers/App/Admin/FlagActivationControllerTest.php | 5 | 2 | Duplicate | Two credential gate tests, same middleware group. |
| Feature/Controllers/App/Auth/PasswordConfirmationStatusControllerTest.php | 4 | 1 | Low value | "Not confirmed when nothing was confirmed" is the default branch of the window test. |
| Feature/Controllers/App/Auth/EmailVerificationControllerTest.php | 7 | 1 | Low value | Unsigned link: the signed middleware is the framework. The wrong hash test stays because it is the app's own check. |
| Feature/Controllers/App/Auth/NewPasswordControllerTest.php | 10 | 4 | Duplicate, Low value | Token thrown away duplicates Unit/Actions/ResetPasswordTest::it_throws_the_token_away_once_it_is_used. Unsigned and expired link are variants of the signed URL check, which it_refuses_a_link_whose_email_was_changed already proves wired. Password not confirmed is a second validation variant. |
| Feature/Controllers/App/Auth/ConfirmPasswordControllerTest.php | 12 | 2 | Duplicate | Passkey alone and neither credential duplicate Unit/ViewModels/Auth/ConfirmPasswordViewModelTest::it_offers_the_passkey_alone and ::it_offers_nothing_when_the_account_holds_neither. |
| Feature/Controllers/App/Auth/RegistrationPasskeyControllerTest.php | 12 | 7 | Duplicate, Low value | First vault duplicates Unit/Actions/CreateAccountTest::it_gives_the_account_a_vault_to_put_contacts_in. Lowercased email and email already used duplicate RegistrationControllerTest::it_stores_an_email_address_typed_with_capitals_in_lowercase and ::it_refuses_an_email_address_that_is_already_taken. Signs in and remembers the browser are assertions that belong on the first test (each repeats a full registration request). Two copy tests of the step screen. |
| Feature/Controllers/App/Auth/LoginLinkTest.php | 6 | 2 | Low value | The two refusals test the vendor package (NotAllowedInCurrentEnvironment, DidNotFindUserToLogIn). |
| Feature/Controllers/App/Auth/MagicLinkControllerTest.php | 16 | 6 | Duplicate, Low value | it_records_the_email_it_sends duplicates Unit/Listeners/RecordEmailSentTest::it_writes_an_email_that_went_out_into_the_journal. Remembers the browser and carries the remember decision are one assertion each. Signature ran out is the same signed check as tampered. Two of the three "setting off" tests are variants of it_answers_not_found_on_every_route_while_the_setting_is_off. |
| Feature/Controllers/App/Auth/ScreenshotLinkControllerTest.php | 11 | 2 | Low value | Home screen when nothing asked duplicates the '' case of the somewhereElse provider. Expired signature is a variant of the missing and tampered signature tests. |
| Feature/Controllers/App/Auth/TwoFactorChallengeControllerTest.php | 18 | 7 | Duplicate, Cosmetic, Low value | Recovery code duplicates Unit/Actions/VerifyTwoFactorCodeTest::it_accepts_a_recovery_code_and_spends_it. Offers a key and offers no key duplicate Unit/ViewModels/Auth/TwoFactorViewModelTest::it_offers_a_key_and_asks_for_a_recovery_code_otherwise_over_a_secure_connection and ::it_asks_somebody_with_the_app_for_its_code. Autofocus test parses an input tag with a regex (cosmetic). No warning variants (two) and remember duration are third variants. |
| Feature/Controllers/App/Auth/RegistrationControllerTest.php | 16 | 7 | Duplicate, Low value | Cloudflare accepted and rejected duplicate LoginControllerTest::it_signs_a_user_in_when_cloudflare_accepts_the_human_check and ::it_refuses_the_sign_in_when_cloudflare_rejects_the_human_check. Password too short, not confirmed, invalid time zone and email already taken in capitals are variants of validation already proven by terms and email taken. Remembers the browser. |
| Feature/Controllers/App/Auth/LoginControllerTest.php | 16 | 5 | Cosmetic, Duplicate, Low value | The two remember checkbox tests match the checked attribute with a regex (cosmetic). Magic link offer hidden duplicates LoginViewModelTest::it_says_whether_the_magic_link_is_enabled. Human check absent and the links to other ways in assert markup that the view model decides. |
| Feature/Controllers/App/Auth/SsoControllerTest.php | 13 | 5 | Duplicate, Declaration, Low value | Account creation duplicates Unit/Actions/SignInWithProviderTest::it_creates_an_account_for_somebody_the_instance_has_never_seen. it_keeps_nothing_the_provider_issued asserts the users table has no token columns (declaration). Remembers the browser (two) and carries the decision to remember. |
| Feature/Controllers/App/Auth/PasswordResetLinkControllerTest.php | 7 | 1 | Duplicate | it_records_the_email_it_sends duplicates RecordEmailSentTest (same test as the magic link one). |
| Feature/Controllers/App/Docs/ApiDocsControllerTest.php | 14 | 9 | Duplicate, Cosmetic, Low value | Visitor can read duplicates every_section_of_the_reference_is_reachable (same file). Sample in three languages duplicates Unit/ViewModels/Docs/ApiDocsViewModelTest::it_builds_a_sample_in_three_languages. The sidebar test asserting no href="#" is markup. Title, sidebar aria-current, key issuing copy, rate limit copy, OpenAPI link and the "admitting null" copy pin static text. |
| Feature/Controllers/App/Docs/ApiDocsMarkdownControllerTest.php | 6 | 2 | Low value | Six headings from the whole reference and the "both ways" copy test. Formatting is owned by Unit/ViewModels/Docs/ApiDocsMarkdownTest. |
| Feature/Controllers/App/Docs/OpenApiDocumentControllerTest.php | 4 | 1 | Declaration | The route at the versioned address only asserts a route exists. |
| Feature/Controllers/App/Docs/ApiDocsReturnFieldsTest.php | 6 | 1 | Declaration | it_leaves_exactly_one_field_unchecked_on_purpose pins a list of two names. The other five are real contract tests but are costly (see Cost hot spots). |
| Feature/Controllers/App/Marketplace/MarketplaceControllerTest.php | 15 | 2 | Duplicate, Low value | it_marks_what_the_account_has_already_installed duplicates Unit/ViewModels/Marketplace/MarketplaceViewModelTest::it_marks_a_listing_the_account_has_installed. The plain search box test is subsumed by it_minds_neither_the_case_nor_which_column_the_words_are_in. |
| Feature/Auth/PasskeyManagementTest.php | 11 | 3 | Duplicate, Low value | Remove passkey duplicates Feature/Controllers/App/Settings/Account/Security/SecurityControllerTest::it_removes_a_passkey_with_a_confirmed_password. Guest test duplicates RouteProtectionTest::every_route_of_the_application_asks_who_is_signed_in_unless_it_is_listed_as_public. Name longer than the column is a second validation variant. |
| Feature/Auth/ProviderRegressionTest.php | 12 | 12 | Duplicate, Declaration | The whole file. The nine behaviour tests duplicate LoginControllerTest (it_signs_a_user_in, it_refuses_a_wrong_password, it_sends_somebody_who_uses_two_factor_to_the_challenge), Unit/Actions/AttemptLoginTest (suspended user, connected provider, single sign on without password, remember token) and MagicLinkControllerTest (sign in, two factor). The last three assert the framework provider class and its methods. |
| Feature/Auth/PasskeyScreensTest.php | 8 | 7 | Duplicate, Low value | Sign in screen offers and does not offer a passkey duplicate Unit/ViewModels/Auth/LoginViewModelTest::it_offers_passkeys_over_a_secure_connection and ::it_says_why_over_a_connection_that_is_not_secure. Another account's passkey duplicates PasskeyListViewModelTest::it_shows_no_passkey_from_another_account. The registration passkey list test is the same as the kept list test. The uncompiled @js test is a markup guard. Keep the_security_screen_lists_a_passkey_that_has_never_been_used. |
| Feature/Auth/PasskeyAccountDeletionTest.php | 2 | 1 | Low value | Another account's passkeys left alone. The first test is an action test and belongs in Unit/Actions/DestroyAccountTest. |
| Feature/Auth/PasskeyAccessibilityTest.php | 5 | 2 | Cosmetic, Low value | Passkey buttons are matched with a regex for a button tag (cosmetic). The sign in live region repeats the security screen one. |
| Feature/Auth/UnconfirmedEmailAccessTest.php | 3 | 1 | Low value | it_opens_once_the_address_is_confirmed is the normal path every other screen test already takes. |
| Feature/Auth/PasskeyChallengeTest.php | 12 | 7 | Low value | Five tests each request passkey.login-options and assert one key of the same document (names no credential, requires verification, timeout 60000, encoding, session). Resident key and fresh account are further parts of other tests. |
| Feature/Auth/RegistrationScreenTest.php | 6 | 5 | Duplicate, Cosmetic, Low value | Leads with passkey and insecure copy duplicate Unit/ViewModels/Auth/RegisterViewModelTest::it_offers_a_passkey_first_over_a_secure_connection and ::it_asks_for_a_password_over_a_connection_that_is_not_secure. "Other options" asserts an Alpine mode string (cosmetic). The uncompiled @js test and the neither credential test. Keep it_can_post_to_either_route. |
| Feature/Auth/PasskeyAvailabilityTest.php | 4 | 4 | Duplicate, Low value | Sign in says why duplicates LoginViewModelTest::it_says_why_over_a_connection_that_is_not_secure. Security screen says why duplicates PasskeyScreensTest::the_security_screen_says_why_passkeys_cannot_be_used_here (itself a removal). Two tests that the password and the link are still offered. |
| Feature/Auth/PasskeyRefusalTest.php | 8 | 3 | Duplicate, Low value | Deactivated user duplicates Unit/Actions/RefusePasskeyLoginTest::it_refuses_a_deactivated_user. Same words for every refusal duplicates RefusePasskeyLoginTest::it_reports_every_refusal_with_the_same_message. One hammered credential not locking another is a second variant of the throttle test. |

Methods to remove, by file:

- Feature/Controllers/App/Admin/AdminAccessTest.php
  - the_front_door_opens_on_the_state_of_the_instance
  - it_refuses_a_guessed_address_the_same_way_it_refuses_a_real_one
- Feature/Controllers/App/Admin/OverviewControllerTest.php
  - it_is_the_first_thing_an_administrator_sees
  - it_leads_to_the_moderation_queue_in_one_step
  - it_says_nothing_about_a_queue_that_is_empty
  - it_shows_how_many_emails_left_and_where_they_got_to
  - it_names_each_state_in_words_so_none_of_them_is_carried_by_colour
  - it_shows_the_email_breakdown_on_an_instance_that_has_sent_none
- Feature/Controllers/App/Admin/AccountStorageLimitControllerTest.php
  - it_sets_a_limit_arriving_as_text_the_way_a_form_sends_it
  - a_limit_under_what_is_held_deletes_nothing_and_stops_the_next_upload
- Feature/Controllers/App/Admin/InstanceSettingControllerTest.php
  - it_shows_the_configuration_screen
  - it_governs_the_next_request
  - it_asks_for_a_credential_before_putting_a_setting_back
  - it_asks_for_a_credential_before_removing_a_value_the_product_no_longer_reads
- Feature/Controllers/App/Admin/AccountControllerTest.php
  - it_says_so_plainly_when_a_search_matches_nothing
  - it_draws_the_flags_only_once_a_flag_is_declared
  - it_takes_the_page_size_from_the_url
- Feature/Controllers/App/Admin/AccountLifetimeAccessControllerTest.php
  - it_marks_a_paid_order_refunded_when_access_is_withdrawn
  - it_writes_no_order_when_access_is_granted
  - it_lets_an_administrator_grant_it_to_their_own_account
- Feature/Controllers/App/Admin/TelegramWebhookSecretControllerTest.php
  - it_asks_for_a_credential_before_generating
- Feature/Controllers/App/Admin/AdministratorLogControllerTest.php
  - it_narrows_the_record_to_one_account
  - it_says_so_plainly_when_nothing_has_been_done_yet
  - it_shows_everything_support_did_to_an_account
  - it_shows_the_record_in_numbered_pages
  - it_takes_the_page_size_from_the_url
- Feature/Controllers/App/Admin/TelegramRegistrationControllerTest.php
  - it_asks_for_a_credential_before_registering
  - it_is_not_found_for_somebody_who_does_not_administer_the_instance
- Feature/Controllers/App/Admin/ModerationApprovalControllerTest.php
  - it_attributes_the_decision_to_the_administrator_who_took_it
- Feature/Controllers/App/Admin/AdministratorControllerTest.php
  - it_warns_before_a_grant_and_before_a_withdrawal
  - it_closes_the_area_to_somebody_who_had_it_open
  - it_states_the_reason_the_last_administrator_cannot_be_withdrawn
  - it_shows_the_administrators_in_numbered_pages
  - it_takes_the_page_size_from_the_url
- Feature/Controllers/App/Admin/ModerationControllerTest.php
  - it_says_so_plainly_when_nothing_is_waiting
  - it_offers_a_decision_only_while_one_is_open
  - it_shows_a_resubmission_as_new_work_while_the_earlier_refusal_stays_readable
  - it_shows_the_queue_in_numbered_pages
  - it_takes_the_page_size_from_the_url
- Feature/Controllers/App/Admin/NoVaultContentTest.php
  - the_count_of_what_a_vault_holds_is_all_an_operator_gets
- Feature/Controllers/App/Admin/UserActivationControllerTest.php
  - it_never_offers_to_deactivate_you_on_your_own_account
  - it_records_what_was_done
- Feature/Controllers/App/Admin/FlagActivationControllerTest.php
  - it_asks_for_a_credential_first
  - it_asks_for_a_credential_before_deactivating_a_flag_for_every_account
- Feature/Controllers/App/Auth/PasswordConfirmationStatusControllerTest.php
  - it_says_the_password_is_not_confirmed_when_nothing_was_confirmed
- Feature/Controllers/App/Auth/EmailVerificationControllerTest.php
  - it_refuses_a_link_that_is_not_signed
- Feature/Controllers/App/Auth/NewPasswordControllerTest.php
  - it_refuses_a_link_that_is_not_signed
  - it_refuses_a_link_that_has_expired
  - it_throws_the_token_away_once_it_is_used
  - it_refuses_a_password_that_is_not_confirmed
- Feature/Controllers/App/Auth/ConfirmPasswordControllerTest.php
  - it_offers_the_passkey_alone_to_a_passwordless_account
  - it_explains_when_the_account_holds_neither_credential
- Feature/Controllers/App/Auth/RegistrationPasskeyControllerTest.php
  - it_signs_the_new_person_in
  - it_remembers_the_browser_of_the_new_person
  - it_creates_the_account_and_its_first_vault
  - it_stores_an_email_address_typed_with_capitals_in_lowercase
  - it_refuses_an_email_address_somebody_already_uses
  - the_step_confirms_the_passkey_once_it_exists
  - the_step_says_what_reaches_an_account_that_has_neither
- Feature/Controllers/App/Auth/LoginLinkTest.php
  - it_refuses_to_sign_anybody_in_outside_the_allowed_environments
  - it_refuses_an_email_address_that_belongs_to_nobody
- Feature/Controllers/App/Auth/MagicLinkControllerTest.php
  - it_records_the_email_it_sends
  - it_remembers_the_browser_that_opened_the_link
  - it_carries_the_decision_to_remember_through_the_challenge
  - it_refuses_a_link_whose_signature_ran_out
  - it_does_not_spend_a_link_while_the_setting_is_off
  - it_answers_not_found_before_checking_the_signature_while_off
- Feature/Controllers/App/Auth/ScreenshotLinkControllerTest.php
  - it_lands_on_the_home_screen_when_nothing_was_asked_for
  - it_is_refused_once_the_signature_has_expired
- Feature/Controllers/App/Auth/TwoFactorChallengeControllerTest.php
  - it_sends_no_warning_after_a_password_and_a_code
  - it_sends_no_warning_when_the_code_is_wrong
  - it_remembers_somebody_for_the_duration_the_instance_sets_once_the_code_is_accepted
  - it_signs_somebody_in_with_a_recovery_code_and_spends_it
  - it_offers_a_key_on_the_challenge_to_somebody_who_holds_one
  - it_puts_the_reader_back_on_the_code_field_after_a_refused_code
  - it_offers_no_key_to_somebody_with_the_app_alone
- Feature/Controllers/App/Auth/RegistrationControllerTest.php
  - it_remembers_the_browser_of_the_new_account
  - it_refuses_an_email_address_already_taken_when_typed_with_capitals
  - it_refuses_a_password_that_is_too_short
  - it_refuses_a_password_that_is_not_confirmed
  - it_creates_the_account_when_cloudflare_accepts_the_human_check
  - it_refuses_a_time_zone_that_does_not_exist
  - it_refuses_the_registration_when_cloudflare_rejects_the_human_check
- Feature/Controllers/App/Auth/LoginControllerTest.php
  - it_offers_to_remember_the_browser_ticked_by_default
  - it_keeps_the_remember_box_unticked_when_a_failed_sign_in_left_it_so
  - it_does_not_show_the_human_check_when_it_is_turned_off
  - it_links_to_the_other_ways_in
  - it_hides_the_magic_link_offer_while_the_setting_is_off
- Feature/Controllers/App/Auth/SsoControllerTest.php
  - it_remembers_the_browser_of_the_user_the_provider_names
  - it_creates_an_account_for_somebody_it_has_never_seen
  - it_remembers_the_browser_of_the_account_it_creates
  - it_carries_the_decision_to_remember_through_the_challenge
  - it_keeps_nothing_the_provider_issued
- Feature/Controllers/App/Auth/PasswordResetLinkControllerTest.php
  - it_records_the_email_it_sends
- Feature/Controllers/App/Docs/ApiDocsControllerTest.php
  - it_shows_the_reference_to_a_visitor_who_is_not_signed_in
  - a_page_names_its_own_endpoint_in_the_title
  - the_sidebar_links_to_pages_rather_than_to_anchors
  - the_sidebar_marks_the_page_being_read
  - it_offers_a_sample_in_three_languages
  - it_sends_somebody_to_where_keys_are_issued
  - it_says_what_the_endpoint_costs_against_the_rate_limit
  - it_offers_the_openapi_document_from_every_page_of_the_reference
  - it_tells_a_newcomer_the_document_exists
- Feature/Controllers/App/Docs/ApiDocsMarkdownControllerTest.php
  - the_whole_reference_holds_every_documented_endpoint
  - the_page_offers_both_ways_of_taking_it_away
- Feature/Controllers/App/Docs/OpenApiDocumentControllerTest.php
  - it_serves_the_document_at_an_address_naming_the_api_version
- Feature/Controllers/App/Docs/ApiDocsReturnFieldsTest.php
  - it_leaves_exactly_one_field_unchecked_on_purpose
- Feature/Controllers/App/Marketplace/MarketplaceControllerTest.php
  - it_marks_what_the_account_has_already_installed
  - it_narrows_the_catalogue_from_the_search_box
- Feature/Auth/PasskeyManagementTest.php
  - it_refuses_a_name_longer_than_the_column_holds
  - it_removes_a_passkey
  - it_refuses_a_guest
- Feature/Auth/ProviderRegressionTest.php (delete the file)
  - all 12 methods
- Feature/Auth/PasskeyScreensTest.php
  - the_sign_in_screen_offers_a_passkey_over_a_secure_connection
  - the_sign_in_screen_offers_no_passkey_over_an_insecure_connection
  - the_security_screen_offers_to_add_a_passkey_over_a_secure_connection
  - the_security_screen_says_why_passkeys_cannot_be_used_here
  - the_security_screen_shows_no_passkey_from_another_account
  - neither_screen_embeds_an_uncompiled_blade_directive
  - a_passkey_created_at_registration_is_listed_like_any_other
- Feature/Auth/PasskeyAccountDeletionTest.php
  - deleting_an_account_leaves_another_accounts_passkeys_alone
- Feature/Auth/PasskeyAccessibilityTest.php
  - the_sign_in_screen_carries_a_live_region_that_is_always_present
  - every_passkey_control_is_a_real_button
- Feature/Auth/UnconfirmedEmailAccessTest.php
  - it_opens_once_the_address_is_confirmed (provider, 2 cases)
- Feature/Auth/PasskeyChallengeTest.php
  - it_names_no_credential_so_nothing_reveals_who_has_an_account
  - it_requires_the_device_to_verify_its_owner
  - it_bounds_how_long_the_challenge_stands
  - it_encodes_the_challenge_the_way_the_browser_library_reads_it
  - it_asks_for_a_discoverable_credential_that_verifies_its_owner
  - the_challenge_is_kept_in_the_session_for_the_request_that_answers_it
  - a_fresh_account_makes_its_first_passkey_without_being_asked
- Feature/Auth/RegistrationScreenTest.php
  - it_leads_with_a_passkey_over_a_secure_connection
  - it_offers_the_password_as_an_alternative_rather_than_an_equal
  - it_embeds_no_uncompiled_blade_directive_in_an_attribute
  - it_says_why_and_offers_only_the_password_over_an_insecure_connection
  - an_account_with_neither_credential_is_told_what_reaches_it
- Feature/Auth/PasskeyAvailabilityTest.php (delete the file)
  - the_sign_in_screen_says_why_and_offers_no_control
  - the_security_screen_says_why_and_offers_no_control
  - the_sign_in_screen_still_offers_a_password_and_a_link
  - the_sign_in_screen_still_offers_a_password_and_a_link_when_passkeys_work
- Feature/Auth/PasskeyRefusalTest.php
  - it_refuses_a_passkey_belonging_to_a_deactivated_user
  - one_credential_being_hammered_does_not_lock_out_another
  - every_refusal_reads_exactly_the_same

### Patterns

- **The same screen-independent assertion repeated per controller.** Pagination is asserted by two tests in each of four admin controllers (8 tests) while PaginationTest, PageSizeTest and the view models' it_hands_back_one_page_at_a_time already own it. One test in AccountControllerTest proves the pager is wired; the other seven go. Examples: AdministratorLogControllerTest::it_takes_the_page_size_from_the_url, ModerationControllerTest::it_shows_the_queue_in_numbered_pages.
- **Password confirmation gate tested per route.** Seven admin routes sit in one `password.confirm` group in routes/administrator.php. Seven tests each submit one route and expect a redirect to auth.confirmPassword.new (InstanceSetting 3, FlagActivation 2, TelegramRegistration 1, TelegramWebhookSecret 1). Keep InstanceSettingControllerTest::it_asks_for_a_credential_before_a_change and remove six. Better, add one reflection test next to RouteProtectionTest::every_route_of_the_admin_space_is_closed_to_whoever_does_not_run_the_instance that asserts the middleware on those seven routes.
- **Signed URL variants.** NewPassword (3), EmailVerification (2), MagicLink (2) and ScreenshotLink (3) each test unsigned, expired and tampered links, which is the framework `signed` middleware. One test per route proves it is wired. 5 removed.
- **"Remembers the browser" as its own test.** Register, RegistrationPasskey, MagicLink and Sso (two tests) each make a full request to assert one cookie. 5 tests. The assertion belongs on the sign in test of the same route (`->assertCookie(...)` on the first response).
- **Blade copy tests for something a view model owns.** TwoFactorChallenge (offers a key, offers none), Login (magic link hidden), ConfirmPassword (passkey alone, neither), RegistrationScreen and PasskeyScreens (insecure connection, passkey first), Admin Overview (email counts), AccountController (flags). About 20 tests. Each has a named view model test.
- **A refactor guard that outlived its refactor.** Feature/Auth/ProviderRegressionTest.php is 12 "still" tests written when the user provider changed. Nine repeat LoginControllerTest, AttemptLoginTest and MagicLinkControllerTest. Three test Laravel's EloquentUserProvider.
- **A test per assertion of one response.** PasskeyChallengeTest requests passkey.login-options five times to assert five keys of one JSON document.
- **Controller tests that repeat action rules.** Admin: attribution, order refund, own account grant, last administrator, closing the area (about 9 tests). Auth: token spent, recovery code, first vault, account creation (about 6). The controller's job is one refusal and the redirect shape, which the kept tests already show.
- **Validation variants.** RegistrationControllerTest has seven validation tests where terms and email taken prove the inline rules are wired; NewPassword and Registration also repeat "too short" and "not confirmed".
- **Two controllers for one route family with the same tests.** RegistrationPasskeyControllerTest repeats lowercasing, email taken and 404 when registration is disabled from RegistrationControllerTest. Cloudflare accepted and rejected are tested in both Login and Registration, and no unit test owns the Turnstile rule.
- **Layer misplacement (not removals).** Marketplace filtering (query, type, category, locale, sort, paging) has no view model test, so MarketplaceControllerTest is the only owner of that logic. Those tests are Keep but belong in Unit/ViewModels/Marketplace/MarketplaceViewModelTest. ConfirmPasswordControllerTest::a_passkey_confirmation_lands_on_* test a response class and belong in Unit. PasskeyAccountDeletionTest belongs in Unit/Actions/DestroyAccountTest. PasskeyManagementTest owns UpdatePasskey and PasskeyDeletedResponse because no unit test exists for either.

### Cost hot spots

- **ApiDocsReturnFieldsTest.php.** Three of its tests (it_documents_every_field_every_endpoint_returns, every_documented_example_has_the_shape_the_endpoint_answers_with, every_field_comes_back_as_the_kind_of_thing_it_is_documented_as) each call answer() for every documented endpoint, about 166 each. Each call builds its own fixtures and user and sends a request. That is about 500 API requests with fixtures for one contract. Run the endpoints once (a single loop that collects the problems of all three checks, or a static cache of the answers) and the cost falls to a third. The 1,914 lines of fixtures stay. The test it_names_every_language writes a file into storage and swaps the lang path; it works, but it could read AvailableLocales without touching the disk.
- **ApiDocsControllerTest::every_section_of_the_reference_is_reachable.** 166 full page renders and a hard coded `assertCount(166)` that fails whenever an endpoint is added. Assert the count against `ApiDocsViewModel` or drop it, and render a sample (one per group) if the cost matters. ApiDocsReturnFieldsTest already walks every endpoint.
- **Admin pagination tests.** Each creates 20 or 21 rows: 20 Account factories, 21 AdministratorLog, 20 Administrator (each builds a user and an account), 21 MarketplaceListingVersion (each builds a listing and category). Removing seven of the eight cuts about 150 factory calls, several of which create further models.
- **AdminAccessTest.** Four provider tests times seven URLs is 28 requests and 21 users. Acceptable, and the pattern the rules ask for. The administrator provider could skip the render cost by not following redirects, but this is minor.
- **Registration and RegistrationPasskey.** Each test runs the full CreateAccount path (Queue is faked, so seeding jobs do not run). About 15 of these tests repeat the registration only to assert one extra thing, and merging them saves about 12 registrations.
- **TwoFactorChallenge security key tests and PasskeyRefusalTest.** SoftwareAuthenticator signing and 5 or 6 refused requests in a loop for the throttle. Fine for one of each, no more.
- **MagicLinkControllerTest::it_works_in_production_on_any_host** changes the app environment inside a test; harmless but worth a look if environment state leaks between processes.

### Proposed guidelines

- Do not test pagination, page size or sorting in a controller test. Prove the pager is wired once per area, and leave the rest to PaginationTest, PageSizeTest and the view model.
- Test a middleware group once. A group gate (`password.confirm`, `signed`, the admin check, a flag switch) gets one behaviour test or one reflection test over the routes, not one test per route.
- Test a signed link once per route: one tampered or unsigned case. Expiry and a missing signature are the framework's.
- Put a cookie, a session key or a log assertion on the test that causes it. Never write a separate test that repeats a full request to assert one extra thing about the same response.
- A controller test asserts one refusal from the action, and the controller's own validation. It does not repeat the refusals, the log writing or the notification of the action test, and it names the action test if you are tempted.
- Do not repeat a Blade copy test for a view model decision. If the view model has a test named after the condition (secure connection, credential held, flag declared), the controller test does not render that condition again.
- A file named "Regression" or "Still" is deleted when the refactor it guarded has shipped. A regression test must name the defect and sit in the layer that owns the rule.
- Do not assert a framework or vendor class (EloquentUserProvider, a package exception, a column that is absent). Assert the application's behaviour.
- When several tests request the same endpoint to read different keys of one response, write one test.
- Do not pin a list or a count of declared things in a test (the opaque field list, the 166 endpoints). Derive it from the declaration.
- A test whose subject is a unit (a response class, an action cascade) goes in tests/Unit, not under tests/Feature/Auth.
- A contract test that visits every endpoint (docs against API) runs the endpoints once and checks everything in that pass.

### Open questions

- Feature/Auth/PasskeyScreensTest::the_security_screen_says_why_passkeys_cannot_be_used_here. I cite LoginViewModelTest for the insecure connection condition, but I did not open the Settings security view model to confirm that it uses the same availability helper. If it does not, one security screen test for the insecure case should stay. Looked in: tests/Unit/ViewModels/Settings/Account/Security/PasskeyListViewModelTest (no insecure case listed).
- TelegramRegistrationCheckControllerTest::it_shows_the_answer_on_the_configuration_screen and ModerationControllerTest::it_counts_what_is_waiting_without_pinning_a_wording_to_one assert pluralised or conditional copy that may be built in Blade or in a view model. I kept them. Looked in: Feature/Views/App/Admin/ConfigurationTest, which does not cover these sentences.
- AdministratorLogControllerTest::it_shows_everything_support_did_to_an_account is the only place the log wording of five admin actions is read through the screen. I marked it Low value because the actions test the log write, but I did not confirm that AdministratorLogsViewModelTest covers all five action sentences. Checking that first is safe.
- Feature/Controllers/App/Auth/ConfirmPasswordControllerTest::one_confirmation_covers_a_run_of_sensitive_actions and ::it_asks_again_once_the_window_has_passed overlap with Settings security controller tests that I did not read (out of scope). Kept.
- Whether the ScreenshotLink and LoginLink controllers need an action test is a design question. They are controller owned today, so their tests are the owner.
- RouteProtectionTest does not check the `verified` middleware, so UnconfirmedEmailAccessTest is the only owner of that gate. Kept two of three tests there.

> Checked by the coordinator: `ProviderRegressionTest` holds 12 tests; `ApiDocsControllerTest` line 135 hard codes `assertCount(166, $ids)`, which fails on every new endpoint without saying anything about the docs being right; `ApiDocsReturnFieldsTest` loops over every endpoint in four separate tests (lines 107, 124, 153, 172), so the endpoint fixtures are built four times, not three. The Settings security view model was not opened, so the passkey availability removals stay conditional.

## Feature/Controllers/Api

I read all 76 files under tests/Feature/Controllers/Api (15,275 lines, 696 `#[Test]` methods, counted with grep). Late in the pass I read the longer files in a compacted form (set up lines dropped, assertion lines kept), so judgments about set up cost rest on the earlier, uncompacted reads only. API controllers here take a `string` and run their own query (`find()` with `->where('uuid', ...)->firstOrFail()`), so under testing.md they own their account scoping, and one cross account or wrong vault 404 per route is Keep. The layer should own, per route: that it answers with the right shape, that inline validation is wired, one refusal from the action reaching the caller as a 422, the token ability mapping (`sensitive-fields`), flag gates, and scoping. It should not re-prove what the action tests already prove. I opened the owning action tests (tests/Unit/Actions) for every Duplicate cited below. `RouteProtectionTest` only proves that every route is behind `Authenticate`, so no per route "401 without token" test exists in this folder, and none was flagged.

### Totals
| Bucket | Tests |
| --- | --- |
| Keep | 508 |
| Duplicate | 93 |
| Cosmetic | 0 |
| Declaration | 2 |
| Low value | 93 |
| Total | 696 |

### Findings by file
Paths are relative to tests/Feature/Controllers/Api/. Tests that stay are not listed. Files not listed (AccountExport, AccountExportDownload, AccountImportContact, AccountImportReport, Billing, ChannelSetup, CheckoutSession, ContactDuplicate, ContactListColumn, ContactReminderOccurrence, ContactVCard, FieldPosition, LifeEventRestoration, McpCall, MeContactListView, MeWelcome, Notification, NotificationPreference, PortalSession, ReminderOccurrenceAnswer, Search, StructureVersionPage, StructureVersionSectionPage, Subscription, SubscriptionCancellation) have nothing to remove.

| File | Tests | Remove | Bucket(s) | Why, citing the owning test when Duplicate |
| --- | --- | --- | --- | --- |
| AccountImportControllerTest | 16 | 3 | Duplicate | Refusals by the action: CreateAccountImportTest::it_refuses_a_second_import_while_the_first_runs, CancelAccountImportTest::it_refuses_an_import_that_has_ended, StartAccountImportTest::it_leaves_each_vault_of_a_file_of_several_to_be_created_from_the_file. The limits refusal stays as the one refusal. |
| ApiKeyControllerTest | 17 | 2 | Low value | Page size clamp and page number are inline code repeated in about ten controllers. One pair of tests (VaultControllerTest) is enough. |
| Auth/LoginControllerTest | 14 | 4 | Duplicate | Wrong password: AttemptLoginTest::it_refuses_a_wrong_password_without_a_session_and_warns_the_owner (and the "same thing" test here also asserts 401). Wrong second factor code: VerifyTwoFactorCodeTest::it_refuses_a_code_that_is_not_right. Recovery code: VerifyTwoFactorCodeTest::it_accepts_a_recovery_code_and_spends_it. Revoked token stops working: ApiKeyControllerTest::it_lets_a_caller_revoke_the_key_they_are_calling_with asserts the same 401 afterwards. |
| Auth/RegistrationControllerTest | 13 | 7 | Duplicate, Low value | Token shape restates the first test. Account build: CreateAccountTest::it_gives_the_account_a_vault_to_put_contacts_in. Short password, unconfirmed password, missing terms, throwaway address and "never echoes the password" are one per field validation variants or defects nobody introduces. The required fields test and the existing address test stay. |
| ContactAvatarControllerTest | 10 | 4 | Duplicate, Low value | Size over five megabytes: UpdateContactAvatarTest::it_refuses_an_image_over_five_megabytes. Storage ceiling: CreateFileTest::it_refuses_a_file_the_account_has_no_room_for. Previous picture: UpdateContactAvatarTest::it_lets_the_earlier_picture_go_without_deleting_it. Deleting when there was no picture is a variant. |
| ContactControllerTest | 35 | 9 | Duplicate, Low value | Structure of another account: CreateContactTest::it_refuses_a_structure_version_in_another_account and PublishedContactStructureVersionTest. Delete the "me" contact: DestroyContactTest::it_refuses_to_delete_the_contact_representing_the_user (the ceiling refusal stays as the one). Wrong vault through slugs repeats the uuid test in the same file. Initials only, archived filter 422, empty list, page size, deleted contact 404, readable address are variants of tests that stay. |
| ContactFavouriteControllerTest | 7 | 2 | Low value | Idempotent second request takes the same branch as the first. "Marked contact appears in favourites" is the list filter already proved in ContactControllerTest::it_lists_only_the_favourites_when_asked_for_them. |
| ContactMeControllerTest | 4 | 1 | Low value | "Vault named and no other" is the first test with one more contact. |
| ContactMergeControllerTest | 10 | 2 | Duplicate, Low value | Absorbing the "me" contact: MergeContactsTest::it_refuses_absorbing_the_contact_representing_the_user (the 409 stale state refusal stays). "Reports what moved" restates the preview. |
| ContactNameControllerTest | 7 | 1 | Low value | Unknown `name_order` is a second field validation test. |
| ContactNameRepresentationControllerTest | 7 | 1 | Low value | The contact show structure; list test already asserts the same keys. |
| ContactRelationshipControllerTest | 21 | 9 | Duplicate, Low value | Action rules reproved over HTTP, all in CreateRelationshipTest or UpdateRelationshipTest: first role to other person (it_moves_the_viewed_contact_to_the_other_side_when_the_roles_are_swapped), type of another account (it_refuses_a_type_in_another_account), person with own record (it_writes_the_other_person_down_as_a_regular_contact_when_asked), unknown name (it_writes_two_separate_people_down_when_neither_name_is_known), clearing a date (it_writes_and_clears_the_dates_a_relationship_ran_between), end before start (it_refuses_an_end_that_falls_before_the_start), naming a partial contact (it_renames_a_partial_contact_on_the_other_side), naming a contact with a record (it_refuses_to_rename_a_contact_with_a_record). Empty list is a variant. |
| ContactReminderControllerTest | 9 | 1 | Duplicate | UpdateReminderTest::it_refuses_a_first_date_for_a_reminder_reading_a_date_on_the_contact. Past start refusal stays. |
| ContactStructureVersionControllerTest | 7 | 1 | Duplicate | MoveRecordToStructureVersionTest::it_refuses_a_structure_version_in_another_account. |
| EmailSentControllerTest | 16 | 9 | Duplicate, Low value, Declaration | Delivery status precedence is the model's: EmailSentTest::it_reads_as_bounced_when_the_provider_reported_both, ::it_reads_as_complained_even_when_it_was_delivered_first, plus the three plain states. Clamp, page, empty list, complained_at timestamp, unknown id are variants. The 405 test asserts routes that are not declared. |
| EntityControllerTest | 9 | 1 | Duplicate | CreateEntityTest::it_refuses_a_turned_off_type. |
| EntityTypeActivationControllerTest | 5 | 1 | Low value | Idempotent second request. |
| EntityTypeControllerTest | 20 | 6 | Duplicate, Low value | Slug and marks given: CreateEntityTypeTest::it_takes_the_slug_it_is_given_rather_than_one_made_from_the_name. Empty list, paging, deleted 404, slug as address 404, deleted type slug are variants. |
| EntityTypePositionControllerTest | 5 | 2 | Duplicate | UpdateEntityTypePositionTest::it_puts_a_type_last_when_it_is_asked_for_a_position_past_the_end and ::it_numbers_an_inactive_type_along_with_the_rest. |
| FieldControllerTest | 15 | 8 | Duplicate, Declaration | CreateFieldTest: it_puts_a_new_field_at_the_end_of_the_section, it_marks_a_repeater_as_repeatable_without_being_asked, it_refuses_a_choice_field_with_no_list_to_offer, it_reads_choices_from_a_list, it_keeps_the_list_a_phone_takes_its_labels_from, it_takes_an_email_that_names_no_list_at_all. UpdateFieldTest::it_refuses_a_kind_of_file_that_is_not_a_mime_type. "Every type the product offers" loops the enum (conformance). |
| LifeEventCategoryActivationControllerTest | 7 | 3 | Duplicate, Low value | ActivateLifeEventCategoryTest::it_activates_a_supplied_category_like_any_other, DeactivateLifeEventCategoryTest::it_leaves_the_category_where_it_was_in_the_list. Idempotent variant. |
| LifeEventCategoryControllerTest | 19 | 5 | Duplicate, Low value | UpdateLifeEventCategoryTest::it_renames_a_supplied_category_because_being_supplied_governs_deletion_only. Page size, deleted 404, non uuid 404, reading a renamed supplied group are variants. |
| LifeEventCategoryPositionControllerTest | 6 | 3 | Duplicate, Low value | UpdateLifeEventCategoryPositionTest::it_places_a_category_last_when_the_position_is_past_the_end and ::it_numbers_every_category_from_one_again_counting_the_retired_ones. "Supplied like any other" is a variant. |
| LifeEventCategoryTypeControllerTest | 4 | 1 | Low value | Empty list. |
| LifeEventControllerTest | 17 | 2 | Duplicate, Low value | CreateLifeEventTest::it_refuses_a_participant_of_another_vault. Fields sent as null repeats "keeps its values when none are sent". |
| LifeEventTypeActivationControllerTest | 6 | 2 | Duplicate, Low value | DeactivateLifeEventTypeTest::it_leaves_the_position_and_the_name_alone. Idempotent variant. |
| LifeEventTypeControllerTest | 19 | 4 | Duplicate, Low value | CreateLifeEventTypeTest::it_puts_a_new_type_at_the_end_of_its_category, UpdateLifeEventTypeTest::it_puts_a_type_last_in_the_category_it_moves_to. Non uuid 404 and "update a supplied type" are variants. |
| LifeEventTypeFieldControllerTest | 8 | 1 | Low value | Group without a name is a second validation variant next to the unknown kind test. |
| LifeEventTypePositionControllerTest | 6 | 3 | Duplicate | UpdateLifeEventTypePositionTest::it_places_a_type_last_when_the_position_is_past_the_end, ::it_numbers_the_category_from_one_again_counting_the_retired_ones, ::it_leaves_the_types_of_another_category_alone. |
| LogControllerTest | 10 | 2 | Low value | Clamp and page repeated (see ApiKey). |
| MeControllerTest | 10 | 3 | Duplicate, Low value | UpdateUserProfileTest::it_unconfirms_the_address_when_it_changes. "Shows nobody else" and "email held by somebody else" are variants. |
| MeTimezoneControllerTest | 3 | 1 | Low value | Missing zone is the same rule as the unknown zone. |
| NotificationChannelControllerTest | 14 | 3 | Duplicate, Low value | ConfirmChannelProofCodeTest::a_wrong_code_is_counted_and_refused, SendChannelProofCodeTest::no_code_is_sent_inside_the_wait. Test on an unfinished channel is a second refusal. |
| PaymentControllerTest | 6 | 3 | Low value | Empty list, paging "like the logs", unknown payment 404 next to the other account 404. |
| RelationshipCategoryActivationControllerTest | 8 | 3 | Low value | Idempotent, supplied, and "types left where they were". |
| RelationshipCategoryControllerTest | 15 | 3 | Low value | Page size, deleted 404, non uuid 404. |
| RelationshipCategoryPositionControllerTest | 6 | 3 | Duplicate, Low value | UpdateRelationshipCategoryPositionTest::a_position_beyond_the_list_lands_at_its_end. Retired numbering and supplied are variants. |
| RelationshipCategoryTypeControllerTest | 4 | 1 | Low value | Empty list. |
| RelationshipTypeActivationControllerTest | 8 | 4 | Duplicate, Low value | DeactivateRelationshipTypeTest::it_leaves_the_type_where_it_was_in_the_list. Idempotent, supplied, relationships stay readable are variants. |
| RelationshipTypeControllerTest | 24 | 11 | Duplicate, Low value | CreateRelationshipTypeTest: it_goes_at_the_end_of_its_category, a_symmetric_type_carries_the_same_word_on_both_sides, it_refuses_a_name_the_account_already_uses, it_refuses_a_category_in_another_account. UpdateRelationshipTypeTest: a_supplied_type_can_be_reworded_and_moved_and_keeps_what_it_means, a_type_nobody_uses_may_be_turned_round. Deleted 404, non uuid 404, spaces only, "not retired when reworded", "keeps the direction" are variants. |
| RelationshipTypePositionControllerTest | 6 | 3 | Duplicate, Low value | UpdateRelationshipTypePositionTest::a_position_beyond_the_category_lands_at_its_end and ::it_leaves_the_other_categories_alone. Retired numbering is a variant. |
| StatusControllerTest | 2 | 1 | Low value | "Answers without a token" is the same request as the shape test. |
| StructureVersionControllerTest | 16 | 2 | Duplicate, Low value | DestroyStructureVersionTest::it_refuses_to_throw_away_a_published_version (the publish refusal stays). Unknown version number 404 next to the other account 404. |
| StructureVersionPagePositionControllerTest | 5 | 2 | Duplicate, Low value | UpdateStructureVersionPagePositionTest::it_puts_a_page_last_when_the_position_is_past_the_end. Single page stays where it is. |
| StructureVersionSectionControllerTest | 13 | 5 | Duplicate, Low value | AddSectionToStructureVersionTest::it_refuses_the_same_section_twice_on_one_structure_version and ::it_refuses_a_section_from_another_account. UpdateStructureVersionSectionTest::it_refuses_to_change_anything_on_a_published_structure_version and ::it_puts_the_sections_own_name_back_when_the_override_is_taken_away. "Leaves the section untouched" is a variant. |
| StructureVersionSectionPositionControllerTest | 4 | 1 | Duplicate | UpdateStructureVersionSectionPositionTest::it_refuses_to_reorder_a_published_structure_version. |
| TemplateActivationControllerTest | 5 | 1 | Low value | Idempotent variant. |
| TemplateControllerTest | 20 | 6 | Duplicate, Low value | CreateTemplateTest: it_starts_the_template_with_an_empty_draft, it_names_the_kind_of_record_it_describes, it_refuses_to_describe_an_entity_without_saying_which_kind, it_refuses_a_kind_of_record_from_another_account. Unknown uuid 404 and "supplied template the way the browser does" are variants. |
| TemplateDuplicateControllerTest | 6 | 2 | Duplicate, Low value | DuplicateTemplateTest::it_leaves_the_copy_as_an_unpublished_draft. "Leaves the original untouched" is a variant. |
| TemplatePositionControllerTest | 4 | 1 | Duplicate | UpdateTemplatePositionTest::it_puts_a_template_last_when_the_position_is_past_the_end. |
| VaultControllerTest | 50 | 29 | Duplicate, Low value | See list below. |

Method names to remove, by file.

- AccountImportControllerTest: it_refuses_a_second_file_while_an_import_runs; it_refuses_to_cancel_an_import_that_ended; it_starts_a_file_of_several_vaults_without_a_vault_name
- ApiKeyControllerTest: it_brings_an_absurd_page_size_back_into_range; it_answers_the_page_that_was_asked_for
- Auth/LoginControllerTest: it_refuses_a_wrong_password; it_issues_no_token_when_the_second_factor_is_wrong; it_accepts_a_recovery_code_as_the_second_factor; it_stops_working_with_a_revoked_token
- Auth/RegistrationControllerTest: it_hands_the_token_back_as_a_string_and_nothing_else; it_builds_the_account_the_way_the_sign_up_screen_does; it_refuses_a_throwaway_address; it_refuses_a_password_that_is_too_short; it_refuses_a_password_that_was_not_repeated; it_refuses_to_open_an_account_without_the_terms; it_never_echoes_the_password_back
- ContactAvatarControllerTest: it_refuses_a_picture_over_the_size_it_accepts; it_refuses_a_picture_that_would_take_the_account_past_its_storage; it_lets_the_previous_picture_go_when_a_new_one_arrives; it_answers_the_same_way_when_the_contact_had_no_picture
- ContactControllerTest: it_draws_a_contact_without_a_picture_as_their_initials; it_refuses_the_archived_filter_nothing_can_fill; it_answers_an_empty_list_for_a_vault_holding_nobody; it_pages_the_list_and_brings_a_page_size_back_into_range; it_returns_not_found_for_a_contact_that_was_deleted; it_refuses_a_structure_belonging_to_another_account; it_refuses_to_delete_the_contact_that_represents_you; it_reports_the_readable_address_beside_the_identifier; it_does_not_reach_a_contact_through_the_wrong_vault
- ContactFavouriteControllerTest: it_confirms_the_state_rather_than_inverting_it_when_the_same_request_arrives_twice; it_puts_a_marked_contact_in_the_favourites_grouping
- ContactMeControllerTest: it_resolves_the_me_contact_of_the_vault_named_and_no_other
- ContactMergeControllerTest: it_reports_what_moved; it_refuses_absorbing_the_contact_representing_the_user
- ContactNameControllerTest: it_refuses_a_name_order_it_does_not_know
- ContactNameRepresentationControllerTest: the_contact_object_carries_every_way_its_name_is_written
- ContactRelationshipControllerTest: it_answers_an_empty_list_for_a_contact_linked_to_nobody; it_gives_the_first_role_to_the_other_person_when_asked; it_refuses_a_relationship_type_of_another_account; it_gives_the_person_named_a_record_of_their_own_when_asked; it_records_somebody_whose_name_is_unknown; it_clears_a_date_sent_as_null; it_refuses_an_end_before_the_start; it_names_somebody_who_exists_only_through_the_relationship; it_refuses_to_name_somebody_with_a_record_of_their_own
- ContactReminderControllerTest: it_refuses_a_first_date_on_a_reminder_reading_a_date_on_the_contact
- ContactStructureVersionControllerTest: it_returns_not_found_for_a_version_of_another_account
- EmailSentControllerTest: it_lists_nothing_when_no_email_has_left; it_brings_an_absurd_page_size_back_into_range; it_answers_the_page_that_was_asked_for; it_names_the_three_delivery_states; it_reads_a_bounce_over_a_delivery_reported_out_of_order; it_reads_a_complaint_over_a_bounce_and_a_delivery; it_carries_the_moment_the_recipient_complained; it_returns_not_found_for_an_email_that_does_not_exist; it_offers_no_way_to_write_a_sent_email
- EntityControllerTest: it_refuses_an_entity_of_a_turned_off_type
- EntityTypeActivationControllerTest: it_confirms_the_state_an_entity_type_is_already_in_rather_than_inverting_it
- EntityTypeControllerTest: it_lists_nothing_when_the_account_invented_nothing; it_pages_the_entity_types_and_brings_a_page_size_back_into_range; it_returns_not_found_for_an_entity_type_that_was_deleted; it_returns_not_found_when_the_address_is_a_slug_rather_than_an_identifier; it_creates_an_entity_type_with_the_slug_and_the_marks_it_was_given; it_refuses_a_slug_a_deleted_type_of_the_account_still_holds
- EntityTypePositionControllerTest: it_puts_an_entity_type_last_when_the_position_is_past_the_end; it_numbers_the_types_nobody_may_file_records_under_along_with_the_rest
- FieldControllerTest: it_places_a_new_field_last_in_its_section; it_settles_repeatability_from_the_type; it_refuses_a_field_offering_choices_with_no_list_to_take_them_from; it_takes_the_list_a_field_offers_its_choices_from; it_takes_the_list_a_phone_takes_its_labels_from; it_takes_an_email_that_names_no_list_at_all; it_adds_a_field_of_every_type_the_product_offers; it_refuses_a_kind_of_file_that_is_not_a_mime_type
- LifeEventCategoryActivationControllerTest: it_confirms_the_state_rather_than_inverting_it_when_sent_twice; it_retires_a_supplied_group_like_any_other; it_leaves_the_name_and_the_place_of_a_retired_group_alone
- LifeEventCategoryControllerTest: it_brings_a_page_size_outside_the_range_back_into_it; it_returns_not_found_for_a_deleted_group; it_returns_not_found_when_the_identifier_is_not_a_uuid; it_renames_a_supplied_group_and_keeps_it_marked_as_supplied; it_reads_a_renamed_supplied_group_in_the_words_that_were_written
- LifeEventCategoryPositionControllerTest: it_places_a_group_last_when_the_position_is_past_the_end; it_numbers_the_groups_from_one_again_counting_the_retired_ones; it_moves_a_supplied_group_like_any_other
- LifeEventCategoryTypeControllerTest: it_answers_an_empty_list_for_a_category_holding_nothing
- LifeEventControllerTest: it_refuses_to_record_a_life_event_for_somebody_else; it_keeps_the_values_when_the_fields_are_sent_as_null
- LifeEventTypeActivationControllerTest: it_confirms_the_state_rather_than_inverting_it_when_sent_twice; it_keeps_the_place_a_retired_type_had
- LifeEventTypeControllerTest: it_returns_not_found_for_a_path_that_is_not_a_uuid; it_places_a_created_type_last_in_its_category; it_places_a_type_last_when_it_moves_to_another_category; it_updates_a_type_the_product_supplied
- LifeEventTypeFieldControllerTest: it_refuses_a_group_without_a_name
- LifeEventTypePositionControllerTest: it_places_a_type_last_when_the_position_is_past_the_end; it_numbers_the_retired_types_among_the_rest; it_leaves_the_types_of_another_category_where_they_are
- LogControllerTest: it_brings_an_absurd_page_size_back_into_range; it_answers_the_page_that_was_asked_for
- MeControllerTest: it_shows_nobody_else; it_unconfirms_the_address_when_it_changes; it_refuses_an_address_somebody_else_holds
- MeTimezoneControllerTest: it_refuses_a_request_naming_no_zone
- NotificationChannelControllerTest: a_wrong_code_is_refused_and_says_so; it_refuses_a_test_on_a_channel_that_is_not_finished; asking_for_another_code_inside_the_wait_is_refused
- PaymentControllerTest: it_answers_an_empty_list_rather_than_an_error; it_pages_the_list_like_the_logs; it_answers_not_found_for_a_payment_nobody_made
- RelationshipCategoryActivationControllerTest: it_confirms_the_state_rather_than_inverting_it_when_sent_twice; it_retires_a_supplied_category_like_any_other; it_leaves_the_types_of_a_retired_category_where_they_were
- RelationshipCategoryControllerTest: it_brings_a_page_size_outside_the_range_back_into_it; it_returns_not_found_for_a_deleted_category; it_returns_not_found_when_the_identifier_is_not_a_uuid
- RelationshipCategoryPositionControllerTest: it_places_a_category_last_when_the_position_is_past_the_end; it_numbers_the_categories_from_one_again_counting_the_retired_ones; it_moves_a_supplied_category_like_any_other
- RelationshipCategoryTypeControllerTest: it_answers_an_empty_list_for_a_category_holding_nothing
- RelationshipTypeActivationControllerTest: it_confirms_the_state_rather_than_inverting_it_when_sent_twice; it_retires_a_supplied_type_like_any_other; it_leaves_the_wording_and_the_place_of_a_retired_type_alone; it_leaves_the_relationships_written_with_a_retired_type_readable
- RelationshipTypeControllerTest: it_returns_not_found_for_a_deleted_type; it_returns_not_found_when_the_identifier_is_not_a_uuid; it_places_a_new_type_last_in_its_category; it_writes_the_single_role_of_a_symmetric_type_on_both_sides; it_refuses_a_name_another_type_of_the_account_carries; it_refuses_a_name_or_a_role_made_only_of_spaces; it_refuses_a_category_of_another_account_when_adding_a_type; it_rewords_a_supplied_type_without_touching_what_the_product_does_with_it; it_does_not_retire_a_type_when_it_is_reworded; it_keeps_the_direction_when_none_is_sent; it_changes_the_direction_of_a_type_nobody_uses
- RelationshipTypePositionControllerTest: it_places_a_type_last_when_the_position_is_past_the_end; it_numbers_the_types_of_the_category_from_one_again_counting_the_retired_ones; it_leaves_the_types_of_another_category_where_they_were
- StatusControllerTest: it_answers_without_a_token
- StructureVersionControllerTest: it_returns_not_found_for_a_version_number_the_template_does_not_have; it_refuses_to_delete_a_version_that_is_published
- StructureVersionPagePositionControllerTest: it_puts_a_page_asked_for_a_place_past_the_end_last; it_leaves_the_only_page_of_a_version_where_it_is
- StructureVersionSectionControllerTest: it_refuses_a_section_already_on_the_version; it_refuses_a_section_from_another_account; it_refuses_to_change_a_placement_on_a_published_version; it_falls_back_to_the_section_name_when_the_override_is_cleared; it_leaves_the_section_itself_untouched_when_it_is_placed
- StructureVersionSectionPositionControllerTest: it_refuses_to_move_a_section_on_a_published_version
- TemplateActivationControllerTest: it_confirms_the_state_rather_than_inverting_it_when_sent_twice
- TemplateControllerTest: it_returns_not_found_for_a_template_that_does_not_exist; it_opens_an_empty_draft_when_a_template_is_created; it_creates_a_template_describing_an_entity_type; it_requires_an_entity_type_when_the_template_describes_an_entity; it_refuses_an_entity_type_from_another_account_when_creating_a_template; it_changes_a_template_the_product_supplied_the_way_the_browser_does
- TemplateDuplicateControllerTest: it_copies_the_structure_across_as_a_draft; it_leaves_the_template_it_copied_untouched
- TemplatePositionControllerTest: it_puts_a_template_asked_for_a_place_past_the_end_last
- VaultControllerTest: it_lists_nothing_when_the_account_holds_no_vault; it_does_not_list_a_deleted_vault; it_returns_not_found_for_a_vault_that_does_not_exist; it_returns_not_found_for_a_deleted_vault; it_creates_a_vault_without_a_description_or_a_colour; it_puts_a_new_vault_at_the_end_of_the_list; it_makes_the_first_vault_of_an_account_the_default_one; it_refuses_a_colour_the_product_does_not_offer; it_refuses_a_name_longer_than_the_limit; it_writes_the_creation_to_the_journal; it_writes_the_change_to_the_journal; it_writes_the_deletion_to_the_journal; it_writes_each_decision_to_the_journal_as_its_own_entry; it_clears_the_description_when_it_is_sent_as_null; it_leaves_what_the_vault_holds_alone; it_refuses_a_change_that_omits_the_colour; it_refuses_a_change_that_omits_the_description; it_takes_the_contacts_with_it; it_revokes_the_private_links_into_the_vault; it_hands_the_default_title_to_the_first_vault_left; it_deletes_the_last_vault_of_an_account; it_returns_not_found_when_deleting_the_same_vault_twice; it_takes_the_default_title_off_the_vault_that_held_it; it_leaves_the_default_alone_when_the_request_says_false; it_leaves_the_default_alone_when_the_request_says_nothing; it_refuses_a_structure_version_that_is_still_a_draft; it_refuses_a_vault_when_the_account_has_no_contact_template; it_still_answers_and_edits_what_an_account_over_the_ceiling_holds; it_reports_the_readable_address_beside_the_identifier

VaultControllerTest owners: CreateVaultTest (it_gives_a_vault_a_colour_when_nobody_picks_one, it_leaves_the_description_empty_when_there_is_none, it_puts_a_new_vault_at_the_end_of_the_list, it_makes_the_first_vault_of_an_account_the_one_records_land_in, it_creates_nothing_when_the_account_has_no_contact_template, it_keeps_what_an_account_over_the_ceiling_already_has), UpdateVaultTest::it_leaves_the_place_in_the_list_and_the_default_alone, DestroyVaultTest (it_takes_the_contacts_of_the_vault_with_it, it_revokes_every_link_into_the_vault, it_hands_the_default_over_to_the_first_vault_left, it_deletes_the_last_vault_of_an_account_and_leaves_it_holding_nothing), and PublishedContactStructureVersionTest for the draft version. The journal entries are asserted with `Queue::assertPushedOn` inside CreateVaultTest (5 references), UpdateVaultTest (5) and DestroyVaultTest (3); testing.md says to assert them there and not in a test of their own.

### Patterns
- Action rules re proved over HTTP (93 Duplicates). A controller test per refusal of the action: ContactRelationship (8), Vault (15), RelationshipType (6), StructureVersionSection (4), Field (7), Template (4). Testing.md allows one refusal per controller. Examples: VaultControllerTest::it_refuses_a_vault_when_the_account_has_no_contact_template, ContactRelationshipControllerTest::it_refuses_an_end_before_the_start, FieldControllerTest::it_refuses_a_kind_of_file_that_is_not_a_mime_type.
- Position controllers prove the action's cases again. Nine position controllers carry "past the end", "numbers the retired ones" and "another category" tests that the nine Update*PositionTest files already hold (13 tests, for instance TemplatePositionControllerTest::it_puts_a_template_asked_for_a_place_past_the_end_last against UpdateTemplatePositionTest::it_puts_a_template_last_when_the_position_is_past_the_end). Keep per controller: it moves, validation, other account 404.
- Activation controllers across seven resources repeat three tests each: idempotent second request (7), "supplied like any other" (5), "leaves the place and wording alone" (6). The controller only picks Activate or Deactivate from `is_active`, and both branches are already proved by the retire and offer tests.
- Pagination clamp (`max(1, min(per_page, cap))`) is pasted inline in each list controller and tested in about 13 tests: ApiKey (2), EmailSent (3 assertions in one test plus one), Log (2), Vault (2), Contact, EntityType, LifeEventCategory, RelationshipCategory, Payment. Keep VaultControllerTest only (11 removed). A shared helper would turn this into one unit test.
- Per field validation. One test per rejected field on the same endpoint: Registration (4), Vault (4), MeTimezone (1), LifeEventTypeField (1), Contact (1). One test with all fields proves the validation is wired.
- Variants of 404. Soft deleted record 404, unknown id 404, non uuid address 404 sit next to the other account 404 in about 15 tests (for instance RelationshipTypeControllerTest::it_returns_not_found_when_the_identifier_is_not_a_uuid, EntityTypeControllerTest::it_returns_not_found_for_an_entity_type_that_was_deleted). They share the `find()` with `where('uuid')->firstOrFail()` that the other account test already proves.
- Empty list tests (6): ContactRelationship, EntityType, LifeEventCategoryType, RelationshipCategoryType, Payment, Vault. The empty list is the same code path as a list.
- `$jsonStructure` plus `assertJsonPath` on the same keys. Almost every show and list test asserts the key set with `assertJsonStructure` and then `assertJsonPath('data.type', ...)`, `data.id` and attributes already named in the structure. This is harmless in a test that stays, but ApiKeyControllerTest, EntityTypeControllerTest and EmailSentControllerTest each have a second test that only re reads the same resource. I did not count the structure and path duplication as removable by itself.
- Model rules asserted through a controller: EmailSent delivery status precedence (3 tests, owner EmailSentTest), contact sort name (ContactNameControllerTest::it_rebuilds_what_the_lists_sort_and_search_on, kept, see Open questions).
- No 401 per route test exists. The one `it_answers_a_visitor_of_the_api_with_unauthorized` lives in RouteProtectionTest and nothing in this folder repeats it.

### Cost hot spots
- SearchControllerTest uses the real Meilisearch engine in 3 of 6 tests (`useSearchEngine()` and `waitForSearchEngine()`, a 50 ms polling loop with a 10 second deadline). That is the 4 seconds noted in the audit. The engine behaviour (ranking, typo tolerance, name representations, deleted records, sensitive values) is owned by SearchRecordsTest, which also contains `it_searches_the_database_and_leaves_a_sensitive_value_out_of_reach_there_too` on the sqlite database driver. The controller owns only the mapping from the token ability to `withSensitive`, the 422 on a one letter query, the 404 on another account's vault, the 503 and the JSON shape. I would keep all 6 tests but run the three engine ones through the database driver (as SearchRecordsTest does on sqlite) or bind a fake for the search action, so the Meilisearch round trip stays only in tests/Unit/Actions/SearchRecordsTest.php. No test would be lost. On MySQL and PostgreSQL that file already skips its database driver test, so the controller tests would need the same guard or a fake.
- VaultControllerTest runs 50 tests with `Queue::fake()` and one or two vaults each. 29 of them are removable, which is the biggest single saving in this folder. Every `User::factory()->create()` creates an account and its seeded records; on MySQL each test pays that.
- BillingControllerTest calls `StripeCatalogue::forget()` and `fakeStripe()` in `setUp()` for all 13 tests, though only the free account, subscriber, offers not read and self hosted tests read the catalogue. Moving the fake into those tests would save the work in the other nine.
- ContactControllerTest, ContactRelationshipControllerTest and ContactMergeControllerTest each rebuild a published template, structure version and vault default per test (`vaultReadyForContacts`, `publishedContactVersion`). Where a test only needs a vault and a contact (about 20 of the 35 contact tests), the helper is not used. Fine as it is, but the heavy helper should not creep into the read only tests.
- StructureVersionControllerTest::it_reads_a_version_and_everything_it_carries_in_a_bounded_number_of_queries counts queries with `assertLessThan(12, ...)`. It builds a version, pages, sections and fields. It guards N+1, so I kept it, but it is the most fixture heavy single test in the folder.
- ContactVCardControllerTest builds default structures with `Structure::asTheDefaultOf`, value lists and values in 4 of 9 tests. They cross the web screen and the importer. Kept, see Open questions.

### Proposed guidelines
- A controller test asserts one action refusal per controller. The other refusals belong to the action test, with the action's test name cited when a controller test is removed (93 Duplicates here).
- Do not test position, activation or pagination clamps in each controller. Test "it moves", validation and the other account 404 in the controller; "past the end", renumbering, retired items and idempotency in the action test; the clamp once.
- Do not assert the `LogUserAction` in a controller test. testing.md already says to assert it inside the test of what the action does (VaultControllerTest had four journal tests).
- Do not write a controller test whose only difference from another is a soft deleted record, an unknown id or a non uuid address. One other account 404 per route proves the scoped lookup.
- Validation: one test per endpoint that sends an invalid body and asserts every wrongly filled field, not one test per field.
- Do not loop an enum inside a test to prove every case is accepted (FieldControllerTest::it_adds_a_field_of_every_type_the_product_offers). The action test owns the types.
- Do not assert routes that are absent (405 for POST, PUT and DELETE on a read only resource).
- A test that needs the real search engine belongs in tests/Unit/Actions/SearchRecordsTest.php. A controller test that exercises the token ability uses the database driver or a fake.

### Open questions
- ContactNameControllerTest::it_rebuilds_what_the_lists_sort_and_search_on asserts `sort_name` and a search through the contact list. I did not find a test for `sort_name` in UpdateContactNameTest (I read its method names only). Kept.
- RelationshipCategoryActivationControllerTest::it_takes_a_retired_category_and_its_types_out_of_the_relationship_picker asserts through `RelationshipTypeChoices`. RelationshipTypeChoicesTest has `it_does_not_offer_a_withdrawn_type` but I did not confirm it covers a withdrawn category. Kept until confirmed. If it does, this is a Duplicate.
- BillingResource is self contained (no BillingViewModel), and BillingViewModelTest in tests/Unit/ViewModels/Settings/Account/Billing covers the same facts for the web screen (self hosted, lifetime, order that stopped holding, cancelled subscription, usage). The facts are computed twice. I kept all 13 Billing tests because the resource does not call the view model, but one shared computation would let one set of tests cover both.
- Contact list sort, filter and search are implemented inline in the API ContactController. I found no view model test for the same query in tests/Unit/ViewModels/Vaults (only ContactListColumnsViewModelTest), so those tests are kept. If the web list runs its own query, there are two implementations.
- ContactVCardControllerTest::a_label_leaves_on_the_card_and_comes_back_as_the_label_it_left_with and ::an_address_a_device_wrote_survives_an_edit_on_the_screen_and_leaves_as_it_arrived call `ImportVCard`, the web section update and the API export in one test. ExportContactVCardTest and ImportVCardTest hold the label and address cases separately, but I did not find the cross screen edit case, so I kept both as integration tests.
- ContactControllerTest::it_reaches_a_contact_named_in_another_alphabet_written_either_way tests slug resolution; I did not check whether a model or helper test already owns slug handling for non Latin names. Kept.
- Durations per test were not measured (the suite was not run). The audit gives 26 s for the 699 tests in this folder, 37 ms each, so the 188 removals save about 7 s on sqlite and proportionally more on MySQL and PostgreSQL.
- Where I cite an action test I opened the file and read its method names, not each body. I did not check that every cited test asserts the same HTTP visible wording (for example the "free plan holds 1 contact" sentence in ContactControllerTest::it_refuses_a_contact_past_the_ceiling_with_the_cap_named, which I kept).

> Checked by the coordinator: `CreateVaultTest::it_creates_a_vault` asserts the `LogUserAction` with `VaultCreation`, so the journal tests in `Api/VaultControllerTest` are duplicates. One correction: keep `it_leaves_the_default_alone_when_the_request_says_false`. How the request turns `"default": false` into a boolean is the controller's own reading of its input, and a truthy read of the string `"false"` is exactly the defect nobody would notice. The agent read the cited action tests by method name, not body, for the later files, so treat the remaining duplicates as likely rather than confirmed until the deletion pass opens each one.

## Feature/Views and the other Feature tests

I read every file in tests/Feature/Views (79 files, 458 `#[Test]` methods; the 483 in the audit counts data provider rows), tests/Feature/Dav (5 files, 69 methods), tests/Feature/Localization (3 files, 28), tests/Feature/Translation (1 file, 5), tests/Feature/Webhooks (2 files, 15) and the six top level files (RouteProtectionTest, TrustedProxiesTest, TrustedHostsTest, OAuthTokenPurgeTest, NightwatchSamplingTest, EnsureFlagIsOnTest, 25 methods). Total 600 methods (grep -c '#\[Test\]'). I opened the owning tests to confirm every Duplicate: ConfigurationViewModelTest, TelegramRegistrationPanelTest, LifeEventTypeFormViewModelTest, FamilyTreeBlockTest, ImmediateFamilyBlockTest, RelationshipsBlockTest, RelationshipEditViewModelTest, ContactReminderBlockTest, LocaleResolutionTest (Unit/Helpers), NewLoginDetectedTest, ApiKeyControllerTest and the sibling component tests. In this area the layer owns: the accessibility and Alpine contract of a component (aria, role, for/id, x-data hooks, field names, which element it resolves to), one render proof per screen, and protocol behaviour for DAV, webhooks and trusted proxies. It does not own what a screen lists or says (the view model), the locale precedence rules (LocaleResolutionTest), or how anything is drawn. Verdict on the yardstick: about 12 component files test only a look and should be deleted; the vault and settings screen tests mostly repeat the view model tests.

### Totals
| Bucket | Tests |
| --- | --- |
| Keep | 389 |
| Duplicate | 74 |
| Cosmetic | 40 |
| Declaration | 15 |
| Low value | 82 |
| Total | 600 |

Removable: 211 methods (35 percent). Of those, 33 go by deleting whole files (listed first below).

### Findings by file

Component files that carry only a look. Delete the file. Contract check: none of them has an aria, role, hook or field name worth a test.

| File (path relative to tests/) | Tests | Remove | Bucket(s) | Why, citing the owning test when Duplicate |
| --- | --- | --- | --- | --- |
| Feature/Views/Components/ApiDocs/MethodBadgeTest.php | 1 | 1 | Cosmetic | Asserts the verb text is printed. A coloured label, no contract. |
| Feature/Views/Components/AuthFootnoteTest.php | 2 | 2 | Cosmetic | A sentence and a link. Look only. |
| Feature/Views/Components/BackLinkTest.php | 2 | 2 | Cosmetic | A link and `data-turbo`. The turbo rule is tested in LinkTest and ButtonTest. |
| Feature/Views/Components/BadgeTest.php | 1 | 1 | Cosmetic | Echoes the slot. |
| Feature/Views/Components/DetailListTest.php | 1 | 1 | Cosmetic | Echoes label and value. |
| Feature/Views/Components/EmptyStateTest.php | 2 | 2 | Cosmetic | Echoes title and slot. |
| Feature/Views/Components/NoticeTest.php | 2 | 2 | Cosmetic | Echoes title and slot. |
| Feature/Views/Components/PageHeaderTest.php | 3 | 3 | Cosmetic | Echoes title and description. |
| Feature/Views/Components/PageSectionTest.php | 2 | 2 | Cosmetic | Echoes title, description and slot. |
| Feature/Views/Components/PanelTest.php | 2 | 2 | Cosmetic | Echoes title and slot. |
| Feature/Views/Components/RowTest.php | 2 | 2 | Cosmetic | `<a` against no `<a`. A bare tag on a row that is a look. |
| Feature/Views/Components/WorldMapTest.php | 6 | 6 | Cosmetic | viewBox, preserveAspectRatio, SVG path regex, 25 `<line>` count, projection coordinates. Every assertion is SVG drawing. Also renders a 50 KB SVG six times. |
| Feature/Views/Components/EntityIconTest.php | 1 | 1 | Declaration | One provider over `EntityType::ICONS`: every name draws something other than the default. A catalogue declaration. IconTest::it_draws_the_default_glyph_when_the_name_is_not_one_it_knows covers the fallback. |
| Feature/Views/Components/SectionMarkTest.php | 3 | 3 | Duplicate, Declaration, Low value | Fallback glyph is IconTest::it_draws_the_default_glyph_when_the_name_is_not_one_it_knows. The `Section::ICONS` loop is a catalogue declaration. `aria-hidden` is IconTest::it_hides_itself_from_screen_readers_unless_it_is_given_a_name. |
| Feature/NightwatchSamplingTest.php | 3 | 3 | Declaration | Each test asserts a route holds a `Sample` middleware. A route declaration, nothing runs. |

Files that stay but lose methods.

| File (path relative to tests/) | Tests | Remove | Bucket(s) | Why, citing the owning test when Duplicate |
| --- | --- | --- | --- | --- |
| Feature/Views/App/Admin/ConfigurationTest.php | 26 | 18 | Duplicate, Low value, Declaration | 26 full admin page requests. Six repeat ConfigurationViewModelTest (self hosted first, deployment value, orphan row, way back) and TelegramRegistrationPanelTest::it_keeps_the_control_unavailable_while_something_is_missing. Nine are variants of the same `confirm-password` dispatch string. Three are the same string again per form (put back, clear secret, remove orphan). |
| Feature/Views/Components/FieldInputTest.php | 25 | 9 | Duplicate, Cosmetic, Low value | Six duplicate ComboBoxTest (open wording box, shows back picked and typed value, hover name). Two assert bare `<fieldset>` and `<legend>`. One checks a removed message is gone. |
| Feature/Views/Components/FieldReminderTest.php | 13 | 7 | Duplicate, Low value | Five duplicate ContactReminderBlockTest::it_offers_once_and_four_yearly_rhythms_and_keeps_an_older_one_on_offer, ::it_says_when_a_saved_rule_next_falls_and_nothing_for_a_new_one and ReminderViewModelTest::it_explains_every_reason_a_reminder_stops. Two assert an `x-show` flag and the absence of a warning. |
| Feature/Views/Components/FieldValueTest.php | 14 | 5 | Low value | Text, yes or no, paragraph, many of a list and unmarked date echo the `formatted` string the test passes in. The component adds nothing to prove. |
| Feature/Views/Components/SidebarTest.php | 14 | 7 | Cosmetic, Duplicate, Low value | Group order is element order. Aria current is asserted three other times. Drawer id, avatar id and menu `x-show` are layout. |
| Feature/Views/Layouts/TopBarTest.php | 12 | 6 | Duplicate, Low value | Docs and Marketplace links and the account settings link are BarLinksTest. The email is checked as absent in four tests. |
| Feature/Views/Components/BreadcrumbVaultTest.php | 12 | 3 | Duplicate, Low value, Cosmetic | `>D</span>` markup. Order of three labels. Vault list repeated. |
| Feature/Views/Components/Modal/WarningTest.php | 7 | 2 | Declaration, Cosmetic | Dialog focus trap conformance and the `x-teleport` placement. |
| Feature/Views/Components/Modal/FormTest.php | 4 | 2 | Declaration, Cosmetic | Same two. |
| Feature/Views/Vaults/RelationshipFormTest.php | 12 | 8 | Duplicate, Cosmetic, Low value | Twelve renders, each building a user, vault, contact, category and type. Roles per type are RelationshipFormViewModelTest::it_names_a_directed_type_by_both_of_its_roles. A `substr` 400 characters before "Cancel" is markup offset. |
| Feature/Views/Vaults/RelationshipsTest.php | 11 | 7 | Duplicate, Low value | RelationshipsBlockTest covers categories, empty state, partial contact, ended link. RelationshipEditViewModelTest::it_tells_which_of_three_states_the_other_side_is_in covers both partial contact form tests. |
| Feature/Views/Vaults/FamilyTreeTest.php | 8 | 5 | Duplicate, Low value | FamilyTreeBlockTest covers heading text, name only, friends left out, empty line. Large family test shows seven names. |
| Feature/Views/Vaults/ImmediateFamilyTest.php | 10 | 7 | Duplicate, Low value | ImmediateFamilyBlockTest covers children, partner, name only and age. Two "no partner" and "no children" variants and a negative test. |
| Feature/Views/Settings/LifeEventTypeFormTest.php | 11 | 6 | Duplicate, Low value | LifeEventTypeFormViewModelTest::it_always_ends_with_the_block_for_the_fields_in_no_group, ::it_reads_the_groups_and_the_loose_fields_back, ::it_allows_deleting_a_type_the_user_made. The field name tests are what LifeEventTypeControllerTest posts. |
| Feature/Views/Components/NotificationBarTest.php | 6 | 2 | Duplicate | NotificationAccessibilityTest::the_bar_says_what_it_opens_and_how_many_are_waiting, ::the_panel_names_itself_to_a_screen_reader and ::every_control_in_the_bar_is_a_real_button assert the same attributes and labels. |
| Feature/Localization/ApiLocaleResolutionTest.php | 14 | 10 | Duplicate | LocaleResolutionTest::it_matches_what_a_device_announces_to_a_language_the_product_ships (13 row provider including fr-CH, pt, pt-PT, kl-GL) and the `on_the_api_*` tests own every precedence rule. |
| Feature/Localization/WebLocaleResolutionTest.php | 11 | 7 | Duplicate | The five `on_a_screen_*` tests and the `it_refuses_*` tests in LocaleResolutionTest own precedence and unshipped locales. |
| Feature/Localization/LocaleRenderingTest.php | 3 | 2 | Duplicate, Declaration | The Polish sign in render repeats WebLocaleResolutionTest::it_shows_a_guest_screen_translated_and_not_merely_resolved. Looping every locale for a non English validation string is a translation completeness check that `monica:localize --check` already runs. |
| Feature/Translation/FrenchTypographyRenderingTest.php | 5 | 3 | Duplicate, Low value | The two `never lets an ordinary space` tests are the negation of the two positive ones. The English test asserts the absence of two characters. |
| Feature/Views/Components/ApiDocs/EndpointTest.php | 7 | 1 | Duplicate | CodePanelTest::it_offers_every_language_and_a_way_to_copy. |
| Feature/Views/Components/ApiDocs/CodePanelTest.php | 4 | 1 | Duplicate | what_renders_is_exactly_the_sample_that_was_built already compares every line including indentation. |
| Feature/Views/Components/ApiDocs/ReturnsTest.php | 6 | 1 | Duplicate | it_shows_the_path_the_type_and_the_description_of_every_field. |
| Feature/Views/Components/ApiDocs/SidebarTest.php | 5 | 1 | Low value | Verb and "Log out" text printed. |
| Feature/Views/Components/ApiDocs/SubheaderTest.php | 3 | 2 | Low value | The text "v1" and the text "Search endpoints". |
| Feature/Views/Components/AccountMenuTest.php | 3 | 1 | Low value | A link and a label. The form and menu roles are proven in the other two. |
| Feature/Views/Components/ActionsMenuItemTest.php | 4 | 2 | Cosmetic, Low value | Counts occurrences of `class=` and the `text-error` string. Glyph presence. |
| Feature/Views/Components/ActionsMenuTest.php | 4 | 2 | Low value | Slot echo and glyph slot. |
| Feature/Views/Components/AvatarInitialsTest.php | 4 | 1 | Low value | Compares two renders of the same input. |
| Feature/Views/Components/BarLinksTest.php | 9 | 2 | Duplicate, Low value | it_offers_the_docs_the_marketplace_and_the_account_settings is it_leads_to_the_docs_the_catalogue_and_the_settings plus every_link_says_its_name_to_a_screen_reader. Signed out and regular user admin tests are one rule. |
| Feature/Views/Components/ButtonSplitTest.php | 4 | 1 | Low value | `role="menuitem"` on a link. |
| Feature/Views/Components/ChipTest.php | 4 | 1 | Low value | Prints a count. |
| Feature/Views/Components/ComboBoxTest.php | 9 | 1 | Low value | `title` and `aria-label` of a hidden label. The aria label is asserted in the next test. |
| Feature/Views/Components/CopyButtonTest.php | 4 | 1 | Low value | Asserts the string `2500` and `this.state = 'idle'`, an implementation constant. |
| Feature/Views/Components/DropdownTest.php | 4 | 1 | Cosmetic | `x-anchor.bottom-end.offset.6`: positioning. |
| Feature/Views/Components/IconButtonTest.php | 6 | 2 | Duplicate, Low value | the_link_carries_its_name_the_same_way_the_button_does repeats it_says_its_name_to_a_screen_reader_and_shows_it_to_everybody_else. Attribute pass through is framework behaviour. |
| Feature/Views/Components/IconTest.php | 3 | 1 | Declaration | The 12 row provider is a catalogue list. The default glyph and aria tests stay. |
| Feature/Views/Components/InputTest.php | 7 | 2 | Low value | Textarea and file input pass attributes through. |
| Feature/Views/Components/ListPanelTest.php | 9 | 1 | Cosmetic | `assertDontSee('<footer')`. |
| Feature/Views/Components/Modal/ConfirmPasswordTest.php | 8 | 1 | Low value | Default wording of the description. |
| Feature/Views/Components/OfferListTest.php | 7 | 1 | Low value | `assertDontSee('<script')`. |
| Feature/Views/Components/OptionRowTest.php | 3 | 2 | Low value | `type="checkbox"` and a hint echo. The label `for` test stays. |
| Feature/Views/Components/PaywallPromptTest.php | 2 | 1 | Low value | Echoes a message. |
| Feature/Views/Components/PositionFormTest.php | 3 | 2 | Low value | Three assertions on one small form. One test is enough. |
| Feature/Views/Components/SelectTest.php | 3 | 2 | Declaration, Low value | The wiring of label, hint and error repeats InputTest::it_wires_the_label_the_hint_and_the_error_to_the_control. A hint slot echo. |
| Feature/Views/Components/SortableRowTest.php | 6 | 4 | Cosmetic, Duplicate, Low value | The drop indicator expression is look. The form is PositionFormTest::it_carries_the_place_a_dropped_row_landed_in. |
| Feature/Views/Components/TabsTest.php | 5 | 1 | Low value | `title` of a count, and a second `blade()` call with no assertion. |
| Feature/Views/Components/TooltipTest.php | 3 | 1 | Low value | Echo of the sentence, which the aria label test asserts. |
| Feature/Views/Layouts/AdminLayoutTest.php | 5 | 2 | Duplicate, Cosmetic | The sign out is AccountMenuTest::it_signs_out_from_a_real_button_in_the_menu. `x-dock` absence is layout. |
| Feature/Views/Layouts/AuthPageTest.php | 7 | 4 | Cosmetic, Low value | Copyright year and name in the footer, theme toggle expression, optional description, home link. |
| Feature/Views/Layouts/MailLayoutTest.php | 9 | 2 | Duplicate, Cosmetic | NewLoginDetectedTest asserts "If this was not you" (line 79). `color-scheme` meta tags are look. |
| Feature/Views/Layouts/SettingsPageTest.php | 5 | 1 | Duplicate | SidebarTest::the_settings_column_offers_the_settings_and_the_way_out_of_them asserts aria current. |
| Feature/Views/App/Admin/FlagsTest.php | 3 | 1 | Low value | Deactivate variant of the activate test. |
| Feature/Views/App/Settings/Security/ApiKeyClientChoiceTest.php | 3 | 1 | Low value | A `for` attribute on a screen SecurityControllerTest already renders. |
| Feature/Views/Settings/LifeEventTypesIndexTest.php | 8 | 1 | Low value | Second move controls test. |
| Feature/Dav/SecurityScreenAccessibilityTest.php | 4 | 1 | Low value | The address printed in full. |
| Feature/Dav/AddressBookTest.php | 38 | 2 | Low value | a_card_naming_nobody_in_the_vault_still_makes_a_contact repeats it_creates_a_contact_from_a_card_a_device_saves. it_refuses_a_card_whose_name_is_empty repeats it_refuses_a_card_with_no_name_at_all. |
| Feature/Webhooks/ResendWebhookTest.php | 8 | 2 | Declaration, Low value | The route middleware check is proven by the two 403 tests. "No signature" is a second 403 case. |
| Feature/Webhooks/StripeWebhookTest.php | 7 | 1 | Declaration | it_asks_stripe_for_more_than_the_package_would_on_its_own lists config values. |
| Feature/RouteProtectionTest.php | 6 | 2 | Low value | Redirect of a visitor and 401 on the API are the framework acting on a middleware the reflection test already proves. |
| Feature/EnsureFlagIsOnTest.php | 6 | 2 | Duplicate, Low value | it_answers_not_found_while_the_flag_is_off is the Dwight half of it_answers_the_account_the_flag_is_on_for_and_nobody_else. it_answers_when_a_test_forces_the_flag_on tests Pennant `define`. |

Methods to remove, by file.

- Views/Components/ApiDocs/MethodBadgeTest.php: whole file.
- Views/Components/AuthFootnoteTest.php, BackLinkTest.php, BadgeTest.php, DetailListTest.php, EmptyStateTest.php, NoticeTest.php, PageHeaderTest.php, PageSectionTest.php, PanelTest.php, RowTest.php, WorldMapTest.php, EntityIconTest.php, SectionMarkTest.php: whole files.
- NightwatchSamplingTest.php: whole file.
- App/Admin/ConfigurationTest.php: it_shows_whether_the_instance_is_self_hosted_before_anything_else; it_says_when_the_deployment_value_is_no_longer_the_one_obeyed; it_keeps_a_setting_the_product_no_longer_carries_on_the_screen; it_offers_a_way_back_only_for_a_setting_somebody_changed; it_offers_to_remove_a_value_the_product_no_longer_reads; it_keeps_the_registration_control_and_says_what_is_missing; it_names_the_environment_variable_of_every_setting; it_submits_the_value_under_one_field_name; it_asks_nothing_before_a_change_that_takes_nothing_away; it_shows_the_warning_before_it_asks_for_a_credential; it_asks_before_registration_is_switched_on; it_offers_to_register_the_telegram_bot; it_asks_for_a_credential_before_generating_a_telegram_secret; it_warns_before_it_asks_for_a_credential_when_a_new_telegram_secret_breaks_the_registration; it_asks_no_credential_from_a_telegram_control_that_is_unavailable; it_asks_for_a_credential_before_a_setting_is_put_back; it_asks_for_a_credential_before_a_secret_is_cleared; it_asks_for_a_credential_before_a_value_nothing_reads_is_removed. Kept: names every setting and its route, aria describedby reason, never prints a secret, asks before change that takes something away, credential on the screen, no credential from a setting that cannot be changed, refused control stays reachable, credential before registering the bot but not before checking it. Replace the dispatch string tests by one test over the forms.
- Components/FieldInputTest.php: it_groups_the_number_and_its_label_under_the_name_of_the_field; it_groups_the_parts_of_an_address_under_the_name_of_the_field; the_label_names_itself_on_hover_rather_than_taking_a_line_of_its_own; the_open_label_offers_a_wording_of_its_own; it_shows_back_the_wording_somebody_typed; it_shows_back_the_label_somebody_already_picked; the_open_label_of_an_address_offers_a_wording_of_its_own; an_address_shows_back_the_wording_somebody_typed_for_its_label; an_address_no_longer_says_it_cannot_be_filled_in_here.
- Components/FieldReminderTest.php: it_offers_once_and_every_so_many_years; it_keeps_offering_a_rhythm_the_list_no_longer_suggests_to_the_reminder_that_has_it; it_says_when_the_next_reminder_falls_and_at_what_hour; it_says_in_words_why_a_rule_has_gone_quiet; it_says_a_quiet_rule_lost_the_date_it_read; the_panel_is_closed_until_the_box_is_ticked; it_has_nothing_to_warn_about_on_a_date_that_carries_no_reminder.
- Components/FieldValueTest.php: it_reads_a_line_of_text_as_the_line_of_text; a_yes_or_no_takes_a_mark_you_can_see_at_a_glance; a_paragraph_keeps_the_line_breaks_it_was_written_with; a_many_of_a_list_reads_as_the_set_it_is; a_date_nobody_asked_to_be_told_about_carries_no_mark.
- Components/SidebarTest.php: the_vault_column_says_where_you_are_with_more_than_a_colour; the_settings_column_puts_every_destination_in_its_group_and_its_order; the_vault_column_does_not_offer_tasks_or_files; the_name_of_the_application_at_the_top_of_either_column_leads_to_the_vaults; both_columns_carry_who_is_signed_in_and_the_way_out; the_way_into_the_settings_and_the_way_out_are_kept_behind_the_name; both_columns_are_the_drawer_the_bar_opens.
- Layouts/TopBarTest.php: it_leaves_who_is_signed_in_to_the_foot_of_the_navigation_column; every_bar_reaches_the_docs_and_the_marketplace; the_bar_of_a_screen_with_a_navigation_column_leaves_the_account_settings_to_it; it_carries_what_the_screen_can_do; a_footer_reaches_the_screen; it_offers_no_search_outside_a_vault.
- Components/BreadcrumbVaultTest.php: it_needs_nothing_but_the_vault_to_find_the_vaults_of_that_account; it_keeps_the_order_it_was_given; the_character_standing_for_the_vault_is_not_read_out_beside_its_name.
- Components/Modal/WarningTest.php and Modal/FormTest.php: it_is_a_dialog_that_traps_the_focus; it_draws_the_card_on_the_body_so_it_shows_from_inside_a_menu_that_closes (each file). Put one provider test over the four modal components in its place.
- Vaults/RelationshipFormTest.php: it_writes_no_script_of_its_own_into_the_markup; it_tells_the_form_both_roles_of_every_type_it_offers; it_names_the_contact_the_sentence_will_be_built_around; it_starts_with_nothing_chosen; it_offers_the_three_ways_of_naming_the_other_side_as_one_group; it_keeps_the_navigation_and_the_way_back_around_it; it_leaves_the_form_without_reloading_the_whole_page; it_offers_to_write_the_other_person_down_as_a_contact.
- Vaults/RelationshipsTest.php: it_offers_each_category_a_first_relationship_can_go_under; it_points_to_the_relationship_types_when_none_is_offered; it_leads_nowhere_for_somebody_who_is_only_a_name; it_offers_the_way_to_correct_and_to_remove_each_link; it_says_when_a_link_has_ended; the_form_renames_a_partial_contact_and_says_why; the_form_offers_to_name_a_nameless_contact.
- Vaults/FamilyTreeTest.php: it_heads_the_panel_and_says_what_it_leaves_out; it_leads_nowhere_for_somebody_who_is_only_a_name; it_draws_no_friend_though_the_groups_beside_it_do; it_says_there_is_no_line_yet_and_offers_the_first_relationship; it_draws_every_child_of_a_large_family.
- Vaults/ImmediateFamilyTest.php: it_lists_the_children_under_their_own_heading_and_links_to_each_one; it_lists_the_current_partner_under_their_own_heading; it_names_somebody_who_is_only_a_name_without_offering_a_way_through_to_them; it_reads_out_an_age_rather_than_leaving_a_bare_number_beside_a_name; it_says_that_no_partner_is_recorded_while_naming_the_children; it_says_that_no_children_are_recorded_while_naming_the_partner; it_offers_no_destination_that_does_not_exist_yet.
- Settings/LifeEventTypeFormTest.php: it_names_everything_the_form_sends; it_picks_the_icon_and_the_colour_from_two_pickers; it_shows_the_block_for_the_fields_in_no_group_even_when_there_are_none; it_reads_a_saved_field_back_into_its_row; it_reads_the_choices_of_a_field_back_into_its_row; it_offers_a_real_delete_for_a_type_the_user_made.
- Components/NotificationBarTest.php: it_is_reachable_and_announced_without_a_mouse; clearing_everything_asks_first.
- Localization/ApiLocaleResolutionTest.php: it_answers_a_caller_who_announced_nothing_in_the_preference_on_their_account; a_caller_naming_a_language_and_no_region_reaches_the_one_locale_for_it; a_caller_naming_a_region_the_product_does_not_ship_reaches_its_language; a_caller_naming_nothing_the_product_ships_falls_through_to_the_account; the_first_language_the_product_ships_wins_over_the_ones_ahead_of_it_that_it_does_not; a_caller_with_no_token_is_answered_in_the_language_they_announce; a_caller_with_neither_a_token_nor_a_language_is_answered_in_the_fallback; a_caller_naming_portuguese_without_a_region_reaches_the_one_that_answers_for_it; a_caller_naming_european_portuguese_reaches_that_one; a_choice_kept_on_a_device_takes_no_part_in_answering_a_call. Kept: what_the_caller_announces_beats_the_preference_on_the_account (wiring), it_returns_a_translated_message_in_the_language_the_caller_asked_for, a_request_carrying_no_session_resolves_without_raising, answering_a_call_keeps_nothing_on_the_callers_device.
- Localization/WebLocaleResolutionTest.php: a_choice_made_in_this_session_beats_the_account; the_account_beats_a_choice_made_on_the_device_before; a_choice_made_on_the_device_beats_what_the_browser_announces; a_first_time_visitor_lands_in_the_language_their_browser_announces; a_first_time_visitor_announcing_nothing_the_product_ships_lands_in_the_fallback; a_session_holding_a_language_the_product_does_not_ship_is_skipped; a_device_holding_a_language_the_product_does_not_ship_is_skipped.
- Localization/LocaleRenderingTest.php: the_sign_in_screen_reads_in_the_language_of_the_session; every_shipped_language_says_why_a_form_was_rejected_in_its_own_words.
- Translation/FrenchTypographyRenderingTest.php: it_never_lets_an_ordinary_space_stand_before_a_french_question_mark; it_never_lets_an_ordinary_space_stand_before_a_french_colon; it_writes_english_with_ordinary_spaces_because_english_declares_no_typography.
- Components/ApiDocs: EndpointTest::it_offers_the_request_in_three_languages; CodePanelTest::it_keeps_the_indentation_the_sample_was_written_with; ReturnsTest::it_names_every_type_in_words; SidebarTest::a_resource_entry_carries_its_verb; SubheaderTest::it_says_which_version_of_the_api_this_is and ::the_search_box_carries_a_real_label.
- Components (single methods): AccountMenuTest::it_leads_to_the_account_settings; ActionsMenuItemTest::it_takes_what_the_caller_hangs_on_it_without_losing_its_own_dressing and ::it_carries_a_glyph_only_where_one_was_given; ActionsMenuTest::it_holds_whatever_may_be_done_there and ::it_takes_a_glyph_of_its_own_and_falls_back_to_the_sandwich; AvatarInitialsTest::it_puts_the_same_name_on_the_same_tint_every_time; BarLinksTest::it_offers_the_docs_the_marketplace_and_the_account_settings and ::it_says_nothing_about_the_admin_space_to_somebody_signed_out; ButtonSplitTest::an_item_of_the_menu_is_a_link_with_a_glyph_beside_it; ChipTest::it_may_carry_a_count; ComboBoxTest::it_names_itself_on_hover_rather_than_taking_a_line; CopyButtonTest::it_lets_go_of_the_word_it_showed_so_the_button_may_be_pressed_again; DropdownTest::it_is_pinned_to_its_button_so_a_panel_cannot_clip_it; IconButtonTest::the_link_carries_its_name_the_same_way_the_button_does and ::it_passes_the_attributes_it_is_given_through; IconTest::it_draws_a_glyph_of_its_own_for_every_mark_the_catalogue_asks_for; InputTest::a_textarea_reads_the_same_props_and_is_the_same_control and ::a_file_field_takes_the_kinds_of_file_it_is_given; ListPanelTest::it_draws_no_footer_for_an_empty_paginated_list; Modal/ConfirmPasswordTest::it_says_why_it_asks_in_the_words_of_the_sign_in_screens_by_default; OfferListTest::it_needs_no_script_of_its_own; OptionRowTest::the_input_is_a_real_control and ::it_carries_a_second_line_when_the_option_needs_explaining; PaywallPromptTest::it_names_the_ceiling_that_was_hit; PositionFormTest::it_is_a_put_rather_than_a_post and ::it_replaces_only_the_list_it_belongs_to; SelectTest::it_wires_the_label_the_hint_and_the_error_to_the_control and ::its_hint_may_be_a_slot_when_it_changes_as_the_field_changes; SortableRowTest::it_shows_where_the_row_would_land, ::it_carries_the_form_that_records_where_it_landed, ::every_row_offers_the_same_handle and ::it_holds_what_it_is_given; TabsTest::the_count_says_in_words_what_it_counts; TooltipTest::it_says_the_sentence_the_mark_stands_for.
- Layouts: AdminLayoutTest::it_offers_a_way_out and ::it_carries_no_dock_since_the_dock_points_into_a_vault; AuthPageTest::it_signs_itself_off_at_the_foot_of_the_column, ::the_line_under_the_title_is_left_out_when_there_is_none, ::every_screen_a_visitor_sees_offers_the_same_way_to_change_the_theme and ::the_name_of_the_application_leads_home; MailLayoutTest::it_shows_the_panel_of_a_sign_in_alert and ::it_declares_it_supports_light_and_dark; SettingsPageTest::it_marks_which_settings_screen_you_are_on.
- Screens: FlagsTest::it_asks_for_a_credential_after_the_warning_before_deactivating_a_flag_for_every_account; ApiKeyClientChoiceTest::the_choice_is_labelled_for_a_screen_reader; LifeEventTypesIndexTest::it_offers_the_move_controls_of_a_type_without_a_pointer; Dav/SecurityScreenAccessibilityTest::the_address_a_device_is_given_is_shown_in_full; Dav/AddressBookTest::a_card_naming_nobody_in_the_vault_still_makes_a_contact and ::it_refuses_a_card_whose_name_is_empty.
- Webhooks and top level: ResendWebhookTest::it_keeps_the_signature_check_on_the_endpoint_the_package_registers and ::it_records_nothing_when_a_report_arrives_with_no_signature_at_all; StripeWebhookTest::it_asks_stripe_for_more_than_the_package_would_on_its_own; RouteProtectionTest::it_sends_a_visitor_to_sign_in and ::it_answers_a_visitor_of_the_api_with_unauthorized; EnsureFlagIsOnTest::it_answers_not_found_while_the_flag_is_off and ::it_answers_when_a_test_forces_the_flag_on.

Component files that carry a contract and stay (keep the file, behaviour only): AccountMenu (menu roles, sign out form), ActionsMenu and ActionsMenuItem (aria, escape, turbo rule), ApiDocs Sidebar, StatusCodes, CodePanel, Endpoint, Returns (table scope, copy, aria current), AvatarInitials (initials logic), BarLinks (flag and administrator gating), BreadcrumbVault (switcher, other account left out), Button, Link, Chip, IconButton, Tabs, Pagination (element resolution, aria, turbo or alpine target), ButtonSplit, Dropdown, ComboBox, ContactNameEditor, CopyButton, DeleteAction, ConfirmPassword, Modal Form and Warning, Docs PageActions, FieldInput, FieldReminder, FieldValue, Icon (default glyph, aria), Input, Select (id to name), LanguagePicker, ListPanel, NotificationBar and NotificationAccessibility, OfferList, OptionRow (label for), PaywallPrompt (link), PositionForm, RecordPicker, RecordPickerCandidates, SortableRow (drag handlers), Timestamp (machine readable datetime), Tooltip, and the layouts.

### Patterns

1. A component or screen test that renders a slot, a title or a description and asserts the same text back. 12 whole files, 33 tests, plus about 20 single methods. Examples: BadgeTest, PanelTest, PageHeaderTest, FieldValueTest::it_reads_a_line_of_text_as_the_line_of_text.
2. A screen test repeating what its view model test proves. 51 methods across the Vaults, Settings and Admin screens. Examples: FamilyTreeTest::it_draws_no_friend_though_the_groups_beside_it_do against FamilyTreeBlockTest::it_draws_no_friend_or_colleague; ConfigurationTest::it_keeps_a_setting_the_product_no_longer_carries_on_the_screen against ConfigurationViewModelTest::it_keeps_a_row_the_product_no_longer_carries; RelationshipsTest::it_says_when_a_link_has_ended against RelationshipsBlockTest::it_says_when_a_link_has_ended_and_what_dates_it_holds. testing.md says a screen needs one render test with its copy.
3. The same string asserted N times. The confirm password dispatch string `$dispatch('confirm-password', { form: $el.form })` is asserted in 12 tests (ConfigurationTest 10, ModalWarning, DeleteAction, FlagsTest). `data-turbo="true"` appears in 6 component files (Link, Button, BackLink, ActionsMenuItem, IconButton, Pagination). `role="dialog"`, `aria-modal` and `x-trap` are asserted in 4 modal files. One provider test over the components replaces these.
4. Locale precedence tested three times. LocaleResolutionTest (Unit) has a 13 row provider and the precedence tests. ApiLocaleResolutionTest and WebLocaleResolutionTest repeat 17 of them over HTTP.
5. Markup structure asserted: bare `<fieldset>`, `<legend>`, `<footer`, `<script`, `<template x-teleport="body">`, `x-anchor...`, `>D</span>`, `id="main-nav"`, SVG paths. About 40 methods.
6. Variant tests of one branch: one test per form or per Telegram control for the same dispatch (ConfigurationTest 9), ImmediateFamilyTest two "no partner, no children" variants, TopBarTest four "email not printed" assertions.
7. Several test files request the same screen in separate tests: settings.security.index in SecurityScreenAccessibilityTest (4) and ApiKeyClientChoiceTest (3); settings.index in NotificationBarTest and NotificationAccessibilityTest (13 requests for one page). SecurityControllerTest already renders it.

### Cost hot spots

- ConfigurationTest: 26 full admin page requests, each building the whole catalogue of settings. After the cuts, 8 remain. Merge the credential assertions in one request.
- RelationshipFormTest: `form()` creates a user, vault, contact, category and type for each of 12 tests. After the cuts, 4 remain; they could share one request.
- FamilyTreeTest, ImmediateFamilyTest, RelationshipsTest, ContactRemindersPanelTest: each test builds a user, vault, contact and relationships and renders the whole contact page (`contacts.show`). The page query count test in FamilyTreeTest is the only one that needs a larger fixture and it should stay.
- NotificationAccessibilityTest and NotificationBarTest: 15 requests of `settings.index` or `notifications.index` with a user, account and notification each. Assert the accessibility contract once per page.
- WorldMapTest: renders a 50 KB SVG six times. Deleting it removes about 300 KB of output.
- IconTest and EntityIconTest providers: 13 renders of a SVG each, with the comparison render of the default glyph repeated each time.
- FieldInputTest (25) and FieldValueTest (14): each creates value lists and items in the database; the nine cut tests from FieldInput account for most of the value list writes.
- ApiLocaleResolutionTest: 14 API requests with a Sanctum user. After the cuts, 4.
- StripeWebhookTest::it_opens_access_on_a_completed_session_stripe_signed still calls `$response->dump()`, a debug leftover that prints to the console on every run.
- AddressBookTest (38 tests, 781 lines) is the heaviest file in the area but each test is a protocol case. No change recommended beyond the two variants.

### Proposed guidelines

- A Blade component whose test would only echo its props or slot gets no test file. Write the test only when the component has aria, a role, a `for` and `id`, an Alpine hook or a conditional element.
- A screen test asserts the copy of one render and the accessibility contract of the screen. Anything the view model decides (grouping, order, empty line, refusal reason, age) is tested in the view model test only.
- Never assert a bare tag (`<fieldset`, `<legend`, `<footer`, `<script`, `<template`), an id used for layout (`main-nav`, `shell-avatar`), a positioning directive (`x-anchor`, `x-teleport`) or an `assertSeeInOrder` of navigation labels.
- When N components must carry the same contract (dialog with focus trap, turbo link, label wired to a control), write one data provider test over them, not one test per file.
- Test locale resolution in LocaleResolutionTest. A Feature test asserts only that the middleware is wired on the web group and on the api group, and one translated page.
- A route middleware or a config list is not tested by asserting it exists. Test behaviour or leave it.
- Do not request the same screen in several tests to assert one attribute each. Request once and assert the contract together, as the page is the unit.
- Remove `$response->dump()`, `dd()` and unused private helpers (LanguagePickerTest::manyLocales) before committing.

### Open questions

- FieldReminderTest::it_says_in_words_why_a_rule_has_gone_quiet and ::it_says_a_quiet_rule_lost_the_date_it_read: I marked them Duplicate of ReminderViewModelTest::it_explains_every_reason_a_reminder_stops from method names and the code in the Blade component; I did not open the Blade file to see whether the wording is chosen in the view model or the template. If it is in the template, they are Keep.
- FieldInputTest::it_never_offers_a_label_nobody_offers_any_more: kept. I did not check whether the filtering of disabled value list items happens in `x-field-input` or in the view model that builds `$field`. If a view model owns it, it is a Duplicate.
- LifeEventTypesIndexTest category refusals (cannot delete a category holding types, a supplied category, how many types leave): kept because the wording lives in Blade and no view model or policy test asserts it (grep for the strings in tests/Unit finds nothing). If the copy moves into the view model, they move with it.
- FrenchTypographyRenderingTest: kept the two positive tests. They overlap with `monica:localize --check` run in CI, which refuses a French value with an ordinary space before `?` or `:`. I did not open the command to confirm it scans the same two strings. If it does, the whole file is Duplicate.
- SidebarTest: I marked 5 methods Low value, including both_columns_carry_who_is_signed_in_and_the_way_out. I did not open `sidebar-foot.blade.php` to see if it reuses `x-account-menu`. If it does, the test is Duplicate of AccountMenuTest.
- ConfirmPasswordTest::its_alpine_expression_is_balanced is kept: it checks parentheses in a JSON blob. It looks like a regression test for one past bug; I could not find its history.
- ErrorPageTest runs four provider rows for the same three assertions (status, heading, `data-turbo-track`). I kept it because it is cheap and renders no database record.

> Checked by the coordinator: `StripeWebhookTest.php:46` holds a leftover `$response->dump()`, which prints to the CI log on every run. Two disagreements with this section. `BackLinkTest` asserts an `href`, a copy and `data-turbo="true"`; the first two echo props, but `data-turbo` changes how the link navigates, so keep that one method and drop the rest. `NightwatchSamplingTest` is a declaration, but its three tests read the route table without touching the database and protect the monitoring bill from a dropped middleware; deleting it saves nothing measurable, so it stays.

## Unit/Actions (A to E)

Scope: the 115 files of tests/Unit/Actions from AccountTransferRoundTripTest.php to ExportTemplateTest.php (18,142 lines, 775 methods by `grep -c '#\[Test\]'`). I read the assertions and structure of every method and the bodies of the ones I judged. I opened AttemptLogin, CreateEntityType, CreateAccount, DestroyVault and the round trip test to confirm ownership, and tests/Unit/Helpers/SlugBuilderTest.php for the slug duplicates. At this layer the tests should own business rules, refusals, the account check, counters and journal writes. Most of that is here and is Keep. The removals are mirror image tests (position left alone, state confirmed twice), separate "writes it to the journal" tests, five stateless login copies, slug tests that repeat SlugBuilderTest, and CreateAccount tests that repeat the Create Default* action tests. I did not run anything.

### Totals
| Bucket | Tests |
| --- | --- |
| Keep | 701 |
| Duplicate | 16 |
| Cosmetic | 0 |
| Declaration | 4 |
| Low value | 54 |
| Total | 775 |

### Findings by file
| File (path relative to tests/) | Tests | Remove | Bucket(s) | Why, citing the owning test when Duplicate |
| --- | --- | --- | --- | --- |
| Unit/Actions/ActivateEntityTypeTest.php | 3 | 1 | Low value | The action sets one flag. A position left alone is a defect nobody introduces. |
| Unit/Actions/ActivateLifeEventCategoryTest.php | 4 | 2 | Low value | "Supplied like any other" and "position left alone" assert fields the action never touches. |
| Unit/Actions/ActivateLifeEventTypeTest.php | 4 | 2 | Low value | Second activation and "position and name alone" restate that the action only sets is_active. |
| Unit/Actions/ActivateRelationshipCategoryTest.php | 3 | 1 | Low value | Position left alone. |
| Unit/Actions/ActivateRelationshipTypeTest.php | 3 | 1 | Low value | Position left alone. |
| Unit/Actions/ActivateTemplateTest.php | 3 | 1 | Low value | An already active template stays active. Same line of code as the main test. |
| Unit/Actions/ActivateUserTest.php | 4 | 2 | Low value | The vault is untouched (nothing deletes it). The journal assertion should sit in the main test. |
| Unit/Actions/DeactivateEntityTypeTest.php | 3 | 1 | Low value | Records, count and position untouched. Mirror of the Activate case. |
| Unit/Actions/DeactivateLifeEventCategoryTest.php | 4 | 2 | Low value | Same two mirror tests as Activate. |
| Unit/Actions/DeactivateLifeEventTypeTest.php | 4 | 2 | Low value | Same two mirror tests as Activate. |
| Unit/Actions/DeactivateRelationshipCategoryTest.php | 3 | 1 | Low value | Position left alone. |
| Unit/Actions/DeactivateRelationshipTypeTest.php | 3 | 1 | Low value | Position left alone. |
| Unit/Actions/DeactivateTemplateTest.php | 3 | 1 | Low value | Record count on a version is untouched. Nothing in the action reads it. |
| Unit/Actions/DeactivateUserTest.php | 5 | 3 | Low value | "Deletes nothing" is a defect nobody introduces. The journal write and the "still active after refusal" check belong inside the main test and the refusal test. |
| Unit/Actions/DeactivateFlagTest.php | 3 | 1 | Low value | A contact survives a flag switch. The action only writes to the features table. |
| Unit/Actions/DeactivateFlagForEveryAccountTest.php | 4 | 1 | Low value | Same as above. |
| Unit/Actions/ApproveMarketplaceListingVersionTest.php | 11 | 1 | Low value | Separate LogAdministratorAction test with no other assertion. Fold into it_lets_a_version_into_the_catalogue. |
| Unit/Actions/AttemptLoginTest.php | 20 | 6 | Duplicate (5), Low value (1) | Five "without a session" refusals run the same code before the branch (validate() and fail()). Owners: it_refuses_a_suspended_user, it_refuses_somebody_who_signs_in_through_single_sign_on, it_says_nothing_about_an_address_that_has_no_account, it_stops_trying_after_five_failures, it_refuses_an_account_that_gave_its_password_up, all in the same file. The remember token "not asked" case is the framework's default. |
| Unit/Actions/BuildOpenApiDocumentTest.php | 35 | 4 | Declaration (2), Low value (1), Duplicate (1) | The format version and the presence of info keys restate declarations. The server URL asserts a helper's output. it_admits_that_a_returned_value_may_hold_nothing asserts the same integer or null type that it_reads_a_moment_as_the_count_of_seconds_the_api_answers_with asserts. |
| Unit/Actions/CompleteChannelConnectionTest.php | 6 | 1 | Low value | Separate assertNotPushed test. Fold into a_token_nobody_is_waiting_on_changes_nothing. |
| Unit/Actions/ConfirmChannelProofCodeTest.php | 8 | 1 | Low value | Separate assertNotPushed test. Fold into a_wrong_code_is_counted_and_refused. |
| Unit/Actions/CreateAccountTest.php | 14 | 4 | Duplicate (3), Low value (1) | Value lists, relationship vocabulary and life event categories are owned by CreateDefaultValueListsTest::it_gives_the_account_every_list_the_product_supplies, CreateDefaultRelationshipTypesTest::it_gives_the_account_the_ten_supplied_types and CreateDefaultLifeEventCategoriesTest::it_gives_the_account_the_eight_supplied_categories. CreateAccount runs the jobs inline, so the wiring is already proven by the vault, contact, template and life event type tests. "Same account whichever credential" repeats CreateAccountFromProviderTest::it_gives_the_account_everything_registering_gives. |
| Unit/Actions/CreateApiKeyTest.php | 8 | 2 | Low value | "Returns the row it wrote" is a return shape. "Unused until used" asserts a null default. |
| Unit/Actions/CreateContactTest.php | 25 | 3 | Low value | Avatar colour is a membership check on a constant. The favourite flag is a passthrough. The nameless case with no representations repeats it_writes_down_somebody_whose_name_is_not_known. |
| Unit/Actions/CreateDefaultLifeEventCategoriesTest.php | 5 | 1 | Low value | "Each account its own set" repeats the count test run for two users. |
| Unit/Actions/CreateDefaultRelationshipTypesTest.php | 6 | 1 | Declaration | other_is_supplied_empty restates the seed list. |
| Unit/Actions/CreateDefaultValueListsTest.php | 10 | 1 | Low value | Other accounts untouched. The query is scoped by account_id and the idempotence test already creates a second account row set. |
| Unit/Actions/CreateEntityTypeTest.php | 12 | 5 | Duplicate | Slug behaviour is owned by Unit/Helpers/SlugBuilderTest.php: it_cleans_a_slug_it_is_given, it_counts_up_until_nothing_answers_to_it, it_falls_back_to_a_word_when_the_name_leaves_nothing, it_keeps_the_slug_within_the_room_the_column_has. Two tests here count up with a given and with a derived slug. Keep one for wiring, the other-account and deleted-type tests (the isTaken closure is the action's own). |
| Unit/Actions/CreateFieldTest.php | 31 | 5 | Low value (4), Declaration (1) | Default not identifying, no end for a repeater when none chosen (repeats it_drops_the_end_on_a_field_that_is_not_a_repeater), "email names no list", "address names no list" (variants of the phone and address keep tests). The canDriveAReminder test asserts an enum method. |
| Unit/Actions/CreateNotificationPreferenceTest.php | 5 | 1 | Duplicate | Row count per channel is covered by only_the_channels_asking_for_nothing_are_switched_on_to_start_with, which asserts each of the four rows. |
| Unit/Actions/CreateSectionTest.php | 9 | 1 | Low value | Null mark. One passthrough test is enough. |
| Unit/Actions/CreateStructureVersionDraftTest.php | 10 | 2 | Low value | The "no second drafting" journal assertion belongs in it_answers_with_the_draft_that_is_already_open. "Leaves the published version as it was" asserts `$id ?? $id`, which cannot fail. |
| Unit/Actions/CreateValueListTest.php | 4 | 1 | Low value | Boolean passthrough. |
| Unit/Actions/CreateVaultTest.php | 17 | 2 | Low value | Default colour constant and empty description. |
| Unit/Actions/DeliverNotificationTest.php | 24 | 1 | Low value | a_notification_that_failed_keeps_its_row_and_its_reason asserts what the abandon tests already assert (failed_at and failure_reason). |
| Unit/Actions/DestroyAccountTest.php | 10 | 1 | Duplicate | it_destroys_the_account is subsumed by it_takes_everything_under_the_account_with_it, which asserts the account model is missing first. |
| Unit/Actions/DestroyRelationshipTest.php | 6 | 1 | Low value | "Off both sides at once" re-asserts the single row soft delete in it_removes_a_relationship. |
| Unit/Actions/DestroyStructureVersionTest.php | 7 | 1 | Low value | Same action on a life event type structure. Only the factory differs. |
| Unit/Actions/DestroyVaultTest.php | 13 | 2 | Low value | Two "contacts / records of another vault alone" tests. The queries filter on the vault id. |
| Unit/Actions/DisableValueListItemTest.php | 3 | 1 | Low value | Position and usage count untouched. |
| Unit/Actions/DisconnectProviderFromUserTest.php | 5 | 1 | Low value | "Keeps the provider when it refuses" belongs in the refusal test. |
| Unit/Actions/EnableValueListItemTest.php | 3 | 1 | Low value | Position untouched. |

Methods to remove, by file:
- ActivateEntityTypeTest: it_puts_the_type_back_where_it_was_in_the_navigation
- ActivateLifeEventCategoryTest: it_activates_a_supplied_category_like_any_other, it_leaves_the_category_where_it_was_in_the_list
- ActivateLifeEventTypeTest: it_confirms_the_state_rather_than_inverting_it, it_leaves_the_position_and_the_name_alone
- ActivateRelationshipCategoryTest: it_leaves_the_category_where_it_was_in_the_list
- ActivateRelationshipTypeTest: it_leaves_the_type_where_it_was_in_the_list
- ActivateTemplateTest: it_leaves_an_already_offered_template_alone
- ActivateUserTest: it_leaves_everything_they_owned_where_it_was, it_writes_the_reactivation_down (move its Queue assertion into it_lets_somebody_sign_in_again)
- DeactivateEntityTypeTest: it_leaves_the_records_the_type_holds_alone
- DeactivateLifeEventCategoryTest: it_deactivates_a_supplied_category_like_any_other, it_leaves_the_category_where_it_was_in_the_list
- DeactivateLifeEventTypeTest: it_confirms_the_state_rather_than_inverting_it, it_leaves_the_position_and_the_name_alone
- DeactivateRelationshipCategoryTest: it_leaves_the_category_where_it_was_in_the_list
- DeactivateRelationshipTypeTest: it_leaves_the_type_where_it_was_in_the_list
- DeactivateTemplateTest: it_leaves_the_records_already_on_it_alone
- DeactivateUserTest: it_deletes_nothing_the_person_owns, it_writes_the_deactivation_down, it_leaves_an_administrator_who_tried_to_deactivate_themselves_active
- DeactivateFlagTest: it_keeps_what_was_created_while_the_flag_was_on
- DeactivateFlagForEveryAccountTest: it_keeps_what_was_created_while_the_flag_was_on
- ApproveMarketplaceListingVersionTest: it_writes_the_decision_down_against_the_administrator
- AttemptLoginTest: it_refuses_a_suspended_user_without_a_session, it_refuses_single_sign_on_without_a_session, it_says_nothing_about_an_unknown_address_without_a_session, it_stops_trying_after_five_failures_without_a_session, it_refuses_an_account_that_gave_its_password_up_without_a_session, it_leaves_no_remember_token_when_not_asked_to
- BuildOpenApiDocumentTest: it_declares_the_version_of_the_format_it_follows, it_names_everything_a_reader_of_the_document_alone_would_have_to_ask_for, it_points_at_the_instance_that_built_it, it_admits_that_a_returned_value_may_hold_nothing
- CompleteChannelConnectionTest: it_logs_nothing_for_a_token_nobody_is_waiting_on
- ConfirmChannelProofCodeTest: it_logs_nothing_when_the_code_was_wrong
- CreateAccountTest: it_gives_the_account_the_value_lists_it_starts_with, it_gives_the_account_the_vocabulary_its_relationships_are_written_in, it_gives_the_account_the_groups_its_life_events_are_filed_under, it_seeds_the_same_account_whichever_credential_it_was_created_with
- CreateApiKeyTest: it_returns_the_row_it_wrote, it_leaves_a_key_unused_until_something_uses_it
- CreateContactTest: it_gives_the_contact_a_colour_to_draw_their_initials_on, it_writes_a_contact_down_already_pinned_to_the_top_of_the_list, it_writes_down_somebody_whose_name_is_not_known_yet_with_no_way_of_writing_it
- CreateDefaultLifeEventCategoriesTest: it_gives_each_account_its_own_set
- CreateDefaultRelationshipTypesTest: other_is_supplied_empty
- CreateDefaultValueListsTest: it_leaves_the_lists_of_other_accounts_alone
- CreateEntityTypeTest: it_cleans_a_slug_it_is_given, it_counts_up_when_the_slug_it_wants_is_taken, it_falls_back_to_a_word_when_the_name_leaves_no_slug, it_keeps_the_slug_within_the_length_of_the_column, it_counts_up_when_the_slug_it_is_given_is_taken (keep it_takes_the_slug_it_is_given, it_leaves_the_slug_of_another_account_alone, it_does_not_hand_out_the_slug_of_a_deleted_type)
- CreateFieldTest: it_marks_no_field_as_identifying_unless_it_is_told_to, it_says_nothing_about_the_end_when_nobody_chose_one, it_takes_an_email_that_names_no_list_at_all, it_takes_an_address_that_names_no_list_at_all, it_lets_the_type_alone_say_whether_a_field_drives_a_reminder
- CreateNotificationPreferenceTest: it_gives_the_account_one_row_per_channel
- CreateSectionTest: it_leaves_the_mark_empty_when_it_is_given_none
- CreateStructureVersionDraftTest: it_does_not_record_a_second_drafting_when_a_draft_is_already_open, it_leaves_the_published_version_exactly_as_it_was
- CreateValueListTest: it_can_be_closed_to_choices_of_the_users_own
- CreateVaultTest: it_gives_a_vault_a_colour_when_nobody_picks_one, it_leaves_the_description_empty_when_there_is_none
- DeliverNotificationTest: a_notification_that_failed_keeps_its_row_and_its_reason
- DestroyAccountTest: it_destroys_the_account
- DestroyRelationshipTest: it_takes_the_link_off_both_sides_at_once
- DestroyStructureVersionTest: it_throws_away_a_draft_of_a_life_event_type_structure
- DestroyVaultTest: it_leaves_the_contacts_of_another_vault_alone, it_leaves_the_records_of_another_vault_alone
- DisableValueListItemTest: it_leaves_the_choice_where_it_is_and_touches_nothing_that_uses_it
- DisconnectProviderFromUserTest: it_keeps_the_provider_when_it_refuses
- EnableValueListItemTest: it_puts_the_choice_back_in_the_place_it_had

### Patterns
- Mirror image "leaves it where it was" tests (roughly two dozen removals across Activate, Deactivate, Enable, Disable and Destroy files). Each asserts that an action which sets one column did not change another. Examples: ActivateRelationshipTypeTest::it_leaves_the_type_where_it_was_in_the_list, DisableValueListItemTest::it_leaves_the_choice_where_it_is_and_touches_nothing_that_uses_it, DeactivateUserTest::it_deletes_nothing_the_person_owns. The Activate and Deactivate pairs were copied wholesale, so a pair of tests exists in 8 files.
- A separate test for the journal write (6 removals: ActivateUser, DeactivateUser, ApproveMarketplaceListingVersion, CompleteChannelConnection, ConfirmChannelProofCode, CreateStructureVersionDraft). testing.md says to assert the LogUserAction inside the main test. The "nothing logged" variants fold into the refusal test.
- Sessionless copies of refusal tests (AttemptLoginTest, 5 removals). Both modes share validate() and fail(), so only the final credential check differs, and the stateless success and wrong password tests cover it.
- Slug behaviour retested per action (CreateEntityTypeTest, 5 removals). SlugBuilderTest already owns cleaning, counting up, fallback and length. An action needs one wiring test and the tests for what its own isTaken closure scopes (account, soft deleted rows). CreateSectionTest and CreateTemplateTest are already close to that shape and stay.
- Seed counts asserted in two places (CreateAccountTest, 3 removals). The Create Default* tests own what an account is seeded with. CreateAccount keeps the tests that prove it calls them in the right order.
- Sanitising wiring repeated 9 times: it_strips_html_out_of_what_it_is_given or similar in CreateContact, CreateEntityType, CreateField, CreateLifeEventCategory, CreateSection, CreateTemplate, CreateValueList, CreateValueListItem and CreateVault. TextSanitizer has no test of its own, so I left them as Keep (they prove each action calls it). A TextSanitizerTest plus one architecture test would replace them. Open question below.
- Count never goes below zero, written four times (DestroyContact, DestroyFile, DestroyStructureVersion, DetachFile). I kept them as the clamp is a real rule, but a shared helper would need one test.
- Near identical variants that a #[DataProvider] would shrink without removing a case: DeliverNotificationTest (four "sends nothing about X archived while it waited" tests and five Telegram refusal classifications), CreateFieldTest (phone and address list keep), BuildOpenApiDocumentTest (several one assertion tests that each rebuild the whole document).

### Cost hot spots
- BuildOpenApiDocumentTest builds the full document 33 times (`new BuildOpenApiDocument()->execute()` once per test), and each build walks every API route and reference. Building it once in a static or `setUpBeforeClass` property, or with one test per concern reading the same array, would cut this file to a single build. I could not time it. It also uses RefreshDatabase and needs none of its own data.
- AccountTransferRoundTripTest: setUp imports tests/Fixtures/Imports/monica-4-small.json into Michael's account for all three tests, then each test exports the vault and imports it again for Pam. Three full import pipelines plus three exports. The second and third tests guard specific defects (a field added to a built in type, values on an older version), so they stay, but they could add their fixtures to one shared setUp and be asserted in one round trip. Savings come from running the import once, not from removing assertions.
- CreateAccountTest runs the real seed jobs for every test (value lists, 40 life event types, templates). 14 tests pay for the full seed. Removing the 4 duplicates saves 4 seeds. The other tests could assert from one created account if kept in one method, but that hides which rule failed, so I recommend only removing duplicates.
- CreateAccountExportTest and CreateAccountImportTest are cheap: Queue and Storage are faked and no job runs. They were not a cost problem.
- DeliverNotificationTest (24 tests) and CreateFieldTest (31) are large files but each test is small.

### Proposed guidelines
- Do not write a test that an action left a column alone. Assert the column it changes. A test of untouched state needs a named reason (a cascade or a count that could otherwise move).
- Assert the journal write (LogUserAction or LogAdministratorAction) in the test of what the action does. Give it a separate test only when the point is that nothing is logged, and put that assertion in the refusal test.
- When one method has two modes sharing a code path, test the shared refusals once. Test each mode only for the branch that differs.
- Do not retest a helper through its callers. If SlugBuilder, TextSanitizer or a seeder owns the rule, the action test proves wiring in one test.
- Count a seed in the seeder's test. An action that calls seeders proves it called them, not what they wrote.
- Keep a mutation of the assertion in mind: a test whose assertion cannot fail (for example CreateStructureVersionDraftTest::it_leaves_the_published_version_exactly_as_it_was comparing `$x ?? $x`) is removed or rewritten.
- Use a #[DataProvider] when two or more tests differ only in the input and the expected classification.

### Open questions
- I did not open every body of 775 methods. For the ones I classed as Keep I read the assertions and the names but not the setup. If a Keep test has setup that does the work of its owner, I would have missed it.
- BuildOpenApiDocumentTest cost is a read of the code, not a measurement. I did not run the suite, as instructed.
- CreateAccountTest::it_asks_for_the_life_event_types_only_once_the_categories_they_are_filed_under_exist asserts 40 life event types. The owner is likely a test of the CreateDefaultLifeEventTypes job under tests/Unit/Jobs, which is outside this scope, so I kept it. Whoever audits Unit/Jobs should confirm.
- The nine sanitising tests are Keep only because TextSanitizer has no direct test (searched tests/ for the class name and found none). If a TextSanitizerTest exists under another name, eight of them become Duplicate.
- CreateLifeEventTest::it_records_where_the_life_event_was_written and ::it_records_where_it_was_written_from look like two views of the same column. I could not tell without opening the bodies, so I left both.
- The count clamp tests (DestroyContact, DestroyFile, DestroyStructureVersion, DetachFile) are Keep on the grounds that the clamp is a rule. If the team does not consider a negative count a realistic fault, they are four more Low value.
- DestroyVaultTest::it_takes_the_contact_standing_for_the_owner_with_the_vault may be subsumed by it_takes_the_contacts_of_the_vault_with_it. I did not open the bodies, so I left both.

> Checked by the coordinator: `SlugBuilderTest` owns cleaning, counting up, the fallback and the length, so the slug duplicates are real. The removal list above drops both counting up tests from `CreateEntityTypeTest` while the table says to keep one for wiring. Keep `it_counts_up_when_the_slug_it_wants_is_taken`: it is the only proof the action passes its own `isTaken` closure to the builder. That makes four removals in that file, not five. `BuildOpenApiDocumentTest` calls the builder 35 times.

## Unit/Actions (F to Z)

Scope: the 116 files of tests/Unit/Actions from FavouriteContactTest.php to WriteDefaultValuesTest.php (25,241 lines, 1,045 methods by `grep -c '#\[Test\]'`). I listed every method name in all 116 files and read in full, or in the relevant method bodies, about 45 of them: SearchRecords, the Grant/Withdraw/Revert/RemoveOrphaned/GenerateTelegram family, the Update*Position and simple Update* files, PublishStructureVersion, PublishingLeavesRecordsWhereTheyAreTest, the SetFieldValue and SetFieldValues overlap (checked against the actions), ImportVCard naming and label tests, and the sampled one or two test files per family. The large importer, billing and reminder files were judged from names, setUp and the sampled bodies, and everything not opened is in Keep (see Open questions). Actions own business rules, refusals, the account check and the journal write, so most of this area is Keep. The file by file check for "several another account tests per action" found none: where an action has two, they are different checks (record versus vault, section versus vault, scoping of the renumbering query versus refusal).

### Totals
| Bucket | Tests |
| --- | --- |
| Keep | 1,007 |
| Duplicate | 7 |
| Cosmetic | 0 |
| Declaration | 1 |
| Low value | 30 |
| Total | 1,045 |

Of the 30 Low value, 10 are "fold into the main test": the assertion is worth keeping, the separate test method is not (testing.md: assert the journal write inside the test of what the action does).

### Findings by file
| File | Tests | Remove | Bucket(s) | Why |
| --- | --- | --- | --- | --- |
| Unit/Actions/UpdateInstanceSettingRetroactivityTest.php | 2 | 1 | Duplicate | The second test builds the same Michael account, the same setting change, and asserts the same `storage_limit_in_bytes` of 104857600 on Michael, then adds Dwight. it_governs_only_the_accounts_created_after_it proves the first. |
| Unit/Actions/WithdrawLifetimeAccessTest.php | 6 | 2 | Duplicate, Low value | it_leaves_the_order_that_was_placed_on_the_record: it_marks_the_order_refunded_when_the_purchase_is_taken_back already asserts the order row still exists with the refunded status. it_writes_the_withdrawal_down: fold the LogAdministratorAction assertion into it_takes_the_full_product_back. |
| Unit/Actions/WithdrawAdministratorAccessTest.php | 8 | 3 | Duplicate, Low value | it_keeps_the_last_administrator_when_two_withdrawals_arrive_together is sequential, not concurrent, and repeats it_refuses_to_leave_the_instance_with_nobody (add the `assertDatabaseCount` there). it_leaves_an_administrator_who_tried_to_withdraw_their_own_capacity_holding_it repeats it_refuses_to_let_an_administrator_take_it_off_themselves (add one assertion). it_writes_the_withdrawal_down: fold. |
| Unit/Actions/GrantAdministratorAccessTest.php | 5 | 1 | Low value | it_writes_the_grant_down: fold into it_lets_somebody_run_the_instance. |
| Unit/Actions/GrantLifetimeAccessTest.php | 4 | 2 | Low value | it_writes_the_grant_down: fold. it_lets_an_administrator_grant_it_to_their_own_account: the action never compares the administrator with the account, nobody introduces that defect. |
| Unit/Actions/UpdateAccountStorageLimitTest.php | 5 | 2 | Low value | it_writes_the_change_down_with_what_it_was_before: fold into it_sets_a_limit. it_lets_an_administrator_change_the_limit_on_their_own_account: same argument as above. |
| Unit/Actions/RevertInstanceSettingTest.php | 4 | 1 | Low value | it_records_the_reversion: fold into it_drops_what_was_stored. (it_clears_a_secret_that_cannot_be_read_back_to_be_edited is a third variant of the same delete, left in Keep, see Open questions.) |
| Unit/Actions/RemoveOrphanedInstanceSettingTest.php | 4 | 1 | Low value | it_records_the_removal: fold into it_drops_a_value_the_product_no_longer_reads. |
| Unit/Actions/PublishingLeavesRecordsWhereTheyAreTest.php | 5 | 3 | Duplicate, Low value | a_life_event_type_in_another_account_is_not_found is CreateStructureVersionDraft refusing another account, proven by CreateStructureVersionDraftTest.php::it_refuses_a_version_from_another_account (opened, line 166). an_entity_stays_on_the_version_it_was_written_on and a_value_stays_attached_to_the_field_of_the_version_it_was_entered_under repeat a_contact_stays_on_the_version_it_was_written_on for the same property, that PublishStructureVersion never writes to records. Keep the contact one and a_life_event_type_keeps_offering_the_newest_version. |
| Unit/Actions/PublishStructureVersionTest.php | 15 | 1 | Low value | it_publishes_a_structure_version_whose_sections_are_all_published is a variant of it_publishes_the_draft_sections_it_carries and asserts less. |
| Unit/Actions/UpdateVaultTest.php | 6 | 2 | Low value | it_logs_what_changed_and_what_it_changed_from: fold into it_updates_the_vault. it_leaves_the_place_in_the_list_and_the_default_alone: the action assigns only name, description, colour. |
| Unit/Actions/UpdateValueListTest.php | 4 | 1 | Low value | it_logs_what_changed_and_what_it_changed_from: fold into it_updates_the_value_list. |
| Unit/Actions/UpdateValueListItemTest.php | 6 | 2 | Low value | it_logs_what_changed_and_what_it_changed_from: fold. it_leaves_how_used_the_choice_is_and_whether_it_is_offered_alone: assignments are explicit. |
| Unit/Actions/UpdateEntityTypeTest.php | 4 | 1 | Low value | it_leaves_how_many_records_the_type_holds_and_whether_it_is_active_alone: the action assigns five named columns. (it_leaves_the_slug_alone stays, the slug is the URL route key.) |
| Unit/Actions/UpdateSectionTest.php | 7 | 2 | Low value | it_gives_the_section_no_new_version_when_the_mark_changes and it_leaves_every_value_written_against_the_section_alone_when_the_mark_changes: the action touches only the section row, so neither can fail without a rewrite of the action. |
| Unit/Actions/UpdateStructureVersionSectionTest.php | 5 | 1 | Low value | it_leaves_the_name_of_the_section_itself_alone: the same "the action writes only what it names" check; the next test, it_puts_the_sections_own_name_back_when_the_override_is_taken_away, already reads the section name. |
| Unit/Actions/UpdateLifeEventCategoryTest.php | 5 | 1 | Low value | it_leaves_the_place_and_the_availability_alone. |
| Unit/Actions/UpdateTimezoneTest.php | 2 | 1 | Low value | it_leaves_the_language_and_the_formats_alone. The second test costs a five column fixture and nobody writes those columns here. |
| Unit/Actions/UpdateContactListViewTest.php | 4 | 2 | Low value | it_writes_when_one_of_the_three_choices_differs repeats the first test with one changed value. it_remembers_nothing_for_anybody_else: the action holds one user. |
| Unit/Actions/SendEmailVerificationTest.php | 2 | 1 | Low value | it_puts_the_email_on_the_queue_the_impatient_ones_go_on: same arrangement as the first test, fold the `queue === 'high'` callback into it. |
| Unit/Actions/UpdateUserProfileTest.php | 6 | 1 | Low value | it_asks_to_confirm_the_new_address_when_it_changes: same arrangement as it_unconfirms_the_address_when_it_changes, one test with `Notification::fake()` proves both. |
| Unit/Actions/ResizeImageTest.php | 4 | 1 | Low value | One of it_refuses_a_width_that_is_not_positive and it_refuses_a_height_that_is_not_positive. Better: one test with a `#[DataProvider]` of width 0 and height -10. |
| Unit/Actions/SetFieldValueTest.php | 48 | 1 | Duplicate | it_refuses_an_address_label_that_is_not_on_the_list_the_field_names reaches the same guard (`The item ... is not on the list this field offers`, SetFieldValue line 203) as it_refuses_a_label_that_is_not_on_the_list_the_field_names in the same file (phone field). |
| Unit/Actions/SetFieldValuesTest.php | 71 | 2 | Low value | it_refuses_a_country_typed_in_rather_than_picked is the same refusal as it_refuses_a_country_that_is_not_on_the_list_of_countries. it_keeps_a_field_unfilled_when_a_label_arrives_with_no_value is the phone variant of it_keeps_an_address_unfilled_when_a_label_arrives_with_seven_empty_boxes, which also asserts the usage count. |
| Unit/Actions/UpdateContactNameRepresentationsTest.php | 11 | 1 | Declaration | it_stores_the_kind_as_its_enum restates the enum cast. Every other test in the file that reads `kind` already goes through it. |
| Unit/Actions/WriteDefaultValuesTest.php | 6 | 1 | Duplicate | it_is_what_gives_a_new_contact_its_defaults has the same fixture, the same call and the same `1` and 'Scranton' assertions as it_gives_the_record_the_answer_its_structure_starts_with in the same file (opened). |

Methods to remove, by file (the "fold" ones keep their assertion in the named main test):

- UpdateInstanceSettingRetroactivityTest.php: it_leaves_a_ceiling_an_existing_account_already_holds
- WithdrawLifetimeAccessTest.php: it_leaves_the_order_that_was_placed_on_the_record, it_writes_the_withdrawal_down (fold)
- WithdrawAdministratorAccessTest.php: it_keeps_the_last_administrator_when_two_withdrawals_arrive_together, it_leaves_an_administrator_who_tried_to_withdraw_their_own_capacity_holding_it, it_writes_the_withdrawal_down (fold)
- GrantAdministratorAccessTest.php: it_writes_the_grant_down (fold)
- GrantLifetimeAccessTest.php: it_writes_the_grant_down (fold), it_lets_an_administrator_grant_it_to_their_own_account
- UpdateAccountStorageLimitTest.php: it_writes_the_change_down_with_what_it_was_before (fold), it_lets_an_administrator_change_the_limit_on_their_own_account
- RevertInstanceSettingTest.php: it_records_the_reversion (fold)
- RemoveOrphanedInstanceSettingTest.php: it_records_the_removal (fold)
- PublishingLeavesRecordsWhereTheyAreTest.php: a_life_event_type_in_another_account_is_not_found, an_entity_stays_on_the_version_it_was_written_on, a_value_stays_attached_to_the_field_of_the_version_it_was_entered_under
- PublishStructureVersionTest.php: it_publishes_a_structure_version_whose_sections_are_all_published
- UpdateVaultTest.php: it_logs_what_changed_and_what_it_changed_from (fold), it_leaves_the_place_in_the_list_and_the_default_alone
- UpdateValueListTest.php: it_logs_what_changed_and_what_it_changed_from (fold)
- UpdateValueListItemTest.php: it_logs_what_changed_and_what_it_changed_from (fold), it_leaves_how_used_the_choice_is_and_whether_it_is_offered_alone
- UpdateEntityTypeTest.php: it_leaves_how_many_records_the_type_holds_and_whether_it_is_active_alone
- UpdateSectionTest.php: it_gives_the_section_no_new_version_when_the_mark_changes, it_leaves_every_value_written_against_the_section_alone_when_the_mark_changes
- UpdateStructureVersionSectionTest.php: it_leaves_the_name_of_the_section_itself_alone
- UpdateLifeEventCategoryTest.php: it_leaves_the_place_and_the_availability_alone
- UpdateTimezoneTest.php: it_leaves_the_language_and_the_formats_alone
- UpdateContactListViewTest.php: it_writes_when_one_of_the_three_choices_differs, it_remembers_nothing_for_anybody_else
- SendEmailVerificationTest.php: it_puts_the_email_on_the_queue_the_impatient_ones_go_on (fold)
- UpdateUserProfileTest.php: it_asks_to_confirm_the_new_address_when_it_changes (fold)
- ResizeImageTest.php: it_refuses_a_height_that_is_not_positive (or merge both into one data provider)
- SetFieldValueTest.php: it_refuses_an_address_label_that_is_not_on_the_list_the_field_names
- SetFieldValuesTest.php: it_refuses_a_country_typed_in_rather_than_picked, it_keeps_a_field_unfilled_when_a_label_arrives_with_no_value
- UpdateContactNameRepresentationsTest.php: it_stores_the_kind_as_its_enum
- WriteDefaultValuesTest.php: it_is_what_gives_a_new_contact_its_defaults

### Patterns
- **A separate test for the journal write** (10 of the 30 Low value). The administrator log actions (Grant and Withdraw administrator access, Grant and Withdraw lifetime access, Revert and RemoveOrphaned instance setting, UpdateAccountStorageLimit) each have an `it_writes_X_down` test whose only content is `Queue::assertPushedOn('low', LogAdministratorAction::class)`. UpdateVault, UpdateValueList and UpdateValueListItem have `it_logs_what_changed_and_what_it_changed_from`. testing.md says to assert it inside the test of what the action does. Fixing it removes ten fixtures with no loss.
- **"Leaves X alone" where the action never assigns X** (8). Slug, position, language, counts, usage. These pass unless the action is rewritten to assign the column. The slug tests (UpdateEntityType, UpdateTemplate, UpdateSection) are a judgement call and stay, because the slug is the URL route key and a rename could plausibly regenerate it.
- **Administrator "on their own account"** (2): no code path compares the two.
- **Test families that repeat per action but test a query each action owns**: 19 Update*Position files carry about 4 or 5 tests each (move, past the end, retired ones counted, other account's rows untouched, refuse other account). Each action has its own renumbering query, so they are Keep. A shared dataset or trait would shorten them, not remove assurance.
- **Variants of one branch inside very large files**: SetFieldValuesTest (71 tests, 2,133 lines) and SetFieldValueTest (48 tests, 1,172 lines). SetFieldValues delegates the actual write to SetFieldValue, but it has its own item, country and relation lookups scoped by account and vault (lines 623, 670, 684 of SetFieldValues), so its refusal tests are Keep. The few real overlaps are listed above.
- **ImportVCardTest naming fallback** (4 tests: organisation, nickname, email before phone, phone only) are the same chain with different inputs. They could be one `#[DataProvider]`. They would still cost four database writes, so this is readability, not time. Not counted as a removal.
- **Query-count style test**: PreviewContactMergeTest::it_reads_each_contact_from_the_database_once asserts that no raw query repeats. It guards performance, not a rule. Kept, flagged in Open questions.

### Cost hot spots
- **SearchRecordsTest.php (15 tests, about 10 s).** Nine tests call `useSearchEngine()`: any word order, typing mistake or accent, ranking, vault scope, another account, sensitive value, groups by entity type, deleted then restored, and life events with participants. Each one runs `scout:sync-index-settings`, waits on Meilisearch tasks (50 ms polling), indexes its own records, waits again, and then deletes the indexes, which is about 1 s per test. Which tests truly need the engine:
  - Needs it: it_finds_a_contact_despite_a_typing_mistake_or_a_missing_accent (tolerance), it_ranks_a_name_match_above_a_value_match (ranking), it_finds_a_contact_holding_every_word_in_any_order (matching semantics). Those three are the engine's own behaviour and only the real engine proves them.
  - The rest are rules of SearchRecords, not of the engine: the vault versus account filter, another account never returned, sensitive values hidden, grouping by entity type, and life events found with participants. They could run on the database driver on sqlite, as it_searches_the_database_and_leaves_a_sensitive_value_out_of_reach_there_too already does, or be folded into one or two engine tests that share a single index (one `useSearchEngine()` setup instead of five). The deleted-and-restored test is Scout behaviour: `useRecordingSearchEngine()` in TestCase proves what a delete and a restore send, with no HTTP.
  - Already on the null engine or without one: it_does_not_search_a_term_under_two_letters, it_says_search_is_unavailable_without_an_engine_it_supports, it_says_search_is_unavailable_when_the_engine_cannot_be_reached, it_refuses_a_vault_of_another_account. Note that the last "unavailable" test configures the meilisearch driver to `127.0.0.1:1`, which refuses at once and costs nothing.
  - The two database driver tests skip themselves when the driver is not sqlite ("RefreshDatabase never commits"). CI runs only the mysql job at the moment, so these two do not execute there today. That is a coverage gap worth deciding on: the "sensitive values stay out" rule for the database driver and the "name written several ways" search are never checked in CI.
- **Large importer and fixture-heavy files**: ImportVCardTest (40 tests, 863 lines), SetFieldValuesTest, SetFieldValueTest, WriteAccountImportItemTest (24 tests, 567 lines), MergeContactsTest and UndoContactMergeTest (26 and 19 tests, building snapshots and values per test). I did not measure these. They are the first candidates for timing by file with `--log-junit` before any restructuring.
- **UpdateInstanceSettingRetroactivityTest**: two tests that each create two accounts through CreateAccount (which creates vaults, templates and default lists). Removing the first saves one of them.

### Proposed guidelines
- Never write a test whose only assertion is that a journal job was dispatched. Add the assertion to the test of what the action does. (10 found.)
- Do not write "leaves X alone" for a column the action does not assign. Write it only when a model hook, an observer or a shared helper could write that column. (8 found.)
- Do not write an "administrator acts on their own account" test unless the action branches on it. (2 found.)
- When two tests differ only in the field type or the input value of the same refusal branch, write one test, or a `#[DataProvider]`. (ResizeImage, country refusals, phone and address label refusals.)
- A test of a rule that belongs to another action (a refusal of CreateStructureVersionDraft, proven in its own test) does not belong in a test of this one.
- When several tests in one file build the same fixture and call the action once, check whether one arrangement can carry the assertions of the others. (WithdrawLifetimeAccess, UpdateUserProfile, SendEmailVerification.)
- A test that needs the real search engine says in its name which engine behaviour it proves (tolerance, ranking, tokenising). Rules of SearchRecords itself (filters, scope, grouping) run on an engine that needs no server.
- A skip that depends on the database driver must be checked against the CI matrix. A test that never runs on the job that runs is a Declaration of intent, not assurance.

### Open questions
- Coverage of reading: I read all 116 method name lists, and opened bodies in about 45 files. I did not open the bodies of ImportLifeEventType, ImportSection, ImportTemplate, most of ImportVCard, MergeContacts, UndoContactMerge, MoveRecordToStructureVersion, Reminder* and Notify*, the billing tests (StartCheckout, SettleOrder, RecordPayment, RestoreAccess, Upgrade and Resume subscription), ReadMonicaFile and ReadMonicaFourFile, RollBackAccountImport, WriteAccountExport and WriteAccountImportItem, UpdateRelationship, UpdateLifeEvent and ReplaceLifeEventTypeFields. They sit in Keep because the names describe single rules, not because I verified them. A second pass on these could find more Low value variants (the ImportVCard phone and address label tests, for example, may share one resolver).
- RefusePasskeyLoginTest::it_reports_every_refusal_with_the_same_message and VerifyPasskeyTest's two "same words" tests protect against user enumeration, so I kept them. They overlap with the individual refusal tests; an owner decision.
- RevertInstanceSettingTest::it_clears_a_secret_that_cannot_be_read_back_to_be_edited is a third variant of the delete, unless the secret case takes a different code path in RevertInstanceSetting (not checked).
- UpdateInstanceSettingTest has three validation tests (wrong kind, count below one, not a URL). I did not read the validator to know whether each kind is a separate branch, so I did not count them.
- PreviewContactMergeTest::it_reads_each_contact_from_the_database_once is a performance guard, not a business rule. Kept, but the repository may prefer to retire query-count tests.
- The three slug tests are Keep on the argument that the slug is a route key. If the models never regenerate it, they become Low value (3 more).
- The 1,007 Keep figure is the 1,045 total minus the 38 removals above. It is not an independent count of tests I verified.

> Checked by the coordinator: confirmed. `SearchRecordsTest` lines 111 and 126 and `MaterializedDatabaseEngineTest` line 137 skip themselves on any driver other than sqlite, and the CI matrix runs MySQL only, so these database search rules are not checked anywhere in CI today. `MaterializedDatabaseEngineTest` line 25 does the reverse and runs on MySQL only. Restoring a sqlite job (see the workflow section) closes this gap as a side effect. This agent read bodies in about 45 of its 116 files; the Keep count for the rest rests on method names.

## Unit/ViewModels

I read all 100 files under tests/Unit/ViewModels (about 19,800 lines, 1,079 methods counted with grep -c '#[Test]'), plus the classes under test where ownership was in doubt (ConfirmPasswordViewModel, ContactDeadlinesBlock, RelationshipFormViewModel, ContactViewModel, PageSize, InvoicesViewModel) and the controller tests named in the Duplicate rows. This layer owns what a screen shows: grouping, ordering, labels, empty states, what is left out, and the account or vault scoping of its own queries. Most of the suite does that well: the Reminder, Contact, Template and Billing view models carry real rules (filters, version history, state to wording). The weight to remove is concentrated in five places: paging tests that the PageSize helper test already proves, tests that restate route() or a config value, tests that assert icon, colour, tint or tone strings, tests that snapshot a catalogue (a hardcoded list of 25 API resources, 70 documentation pages, 92 tools, 37 glyphs), and the same behaviour tested again in a sibling class that delegates to it.

### Totals
| Bucket | Tests |
| --- | --- |
| Keep | 927 |
| Duplicate | 33 |
| Cosmetic | 17 |
| Declaration | 22 |
| Low value | 80 |
| Total | 1079 |

Another 30 or so kept tests carry one or two cosmetic assertions inside a larger behaviour test (tone strings, icon names, a spaceBelow pixel count). Those are listed under Patterns as "trim", not counted as removals.

### Findings by file
| File (path relative to tests/) | Tests | Remove | Bucket(s) | Why, citing the owning test when Duplicate |
| --- | --- | --- | --- | --- |
| Unit/ViewModels/Admin/AccountViewModelTest.php | 34 | 4 | Low | Empty or unit variants of tests that already prove the rule; one only asserts a non null value; one passes a person through |
| Unit/ViewModels/Admin/AccountsViewModelTest.php | 13 | 3 | Duplicate, Low | Paging and "all" are proven by Unit/Helpers/PageSizeTest.php::it_pages_a_query_at_the_size_asked and ::it_holds_everything_on_one_page_for_all. Whole address search is a variant of the part of address test |
| Unit/ViewModels/Admin/AdministratorLogsViewModelTest.php | 9 | 2 | Duplicate | Same two PageSizeTest methods |
| Unit/ViewModels/Admin/AdministratorsViewModelTest.php | 7 | 4 | Duplicate, Declaration | Same two PageSizeTest methods. Two tests only read the grantedBy relation back |
| Unit/ViewModels/Admin/ConfigurationViewModelTest.php | 14 | 2 | Duplicate | credentials() returns ConfirmPasswordViewModel (app/ViewModels/Admin/ConfigurationViewModel.php:25). Owned by Unit/ViewModels/Auth/ConfirmPasswordViewModelTest.php::it_offers_the_password_alone and ::it_offers_nothing_when_the_account_holds_neither |
| Unit/ViewModels/Admin/FlagsViewModelTest.php | 4 | 2 | Duplicate | Same two ConfirmPasswordViewModelTest methods, through FlagsViewModel::credentials() |
| Unit/ViewModels/Admin/ModerationQueueViewModelTest.php | 6 | 2 | Duplicate | Same two PageSizeTest methods |
| Unit/ViewModels/Admin/ModerationSubmissionViewModelTest.php | 8 | 2 | Low, Declaration | One restates two route() calls, one hands back the listing the constructor was given |
| Unit/ViewModels/Admin/OverviewViewModelTest.php | 11 | 2 | Declaration, Low | Enum cases are already counted by it_counts_no_email_on_an_instance_that_has_sent_none. isEmpty false is the inverse of the empty test |
| Unit/ViewModels/Auth/LoginViewModelTest.php | 8 | 2 | Low | Config value passed through to a boolean |
| Unit/ViewModels/Auth/RegisterViewModelTest.php | 6 | 1 | Low | Config value passed through |
| Unit/ViewModels/Docs/ApiDocsFieldsTest.php | 10 | 2 | Low | One checks every row has three keys, one reads an attribute back unchanged |
| Unit/ViewModels/Docs/ApiDocsSectionTest.php | 5 | 2 | Low | Both read constructor input back unchanged |
| Unit/ViewModels/Docs/ApiDocsViewModelTest.php | 15 | 4 | Declaration, Low | Hardcoded list of 25 resource names that fails whenever a resource is added, a check that every entry has an HTTP verb, a test of ApiDocsSection defaults (belongs to the section test), a base URL suffix |
| Unit/ViewModels/Docs/DocsViewModelTest.php | 6 | 1 | Low | Config value passed through |
| Unit/ViewModels/Docs/McpDocsViewModelTest.php | 10 | 3 | Low | Hardcoded tool count (92), hardcoded names of 11 groups, constants contain the word OAuth. it_lists_exactly_the_tools_the_server_registers already catches a missing tool |
| Unit/ViewModels/Docs/PortalCatalogueTest.php | 14 | 1 | Low | A snapshot of 70 page ids in order. Any new documentation page breaks it. The ordering rule can be proven with three fixture files |
| Unit/ViewModels/Marketplace/MarketplaceStatisticsTest.php | 7 | 1 | Cosmetic | Asserts the 4 pixel height of an empty bar |
| Unit/ViewModels/Marketplace/MarketplaceViewModelTest.php | 15 | 1 | Low | Config value passed through |
| Unit/ViewModels/Settings/Account/Billing/BillingViewModelTest.php | 34 | 3 | Low, Declaration | currency() echoes its argument, isKnownToStripe is stripe_id not null, amount alone is a variant of the price test in the same file |
| Unit/ViewModels/Settings/Account/Billing/InvoicesViewModelTest.php | 7 | 1 | Duplicate | Page size 20 is PageSizeTest. The controller also repeats it: Feature/Controllers/App/Settings/Account/Billing/InvoiceControllerTest.php::it_pages_the_payments_twenty_at_a_time |
| Unit/ViewModels/Settings/Account/EntityTypes/EntityTypeFormViewModelTest.php | 6 | 1 | Low | Restates two route() calls |
| Unit/ViewModels/Settings/Account/EntityTypes/EntityTypesViewModelTest.php | 8 | 2 | Declaration, Low | Hardcoded example profiles, and user() passthrough |
| Unit/ViewModels/Settings/Account/Logs/EmailsSentViewModelTest.php | 4 | 2 | Low | A page size constant, and user() passthrough |
| Unit/ViewModels/Settings/Account/Logs/LogsViewModelTest.php | 8 | 2 | Low | A page size constant, and user() passthrough |
| Unit/ViewModels/Settings/Account/Preferences/PreferencesViewModelTest.php | 14 | 1 | Low | user() passthrough |
| Unit/ViewModels/Settings/Account/Preferences/TimezonePanelTest.php | 16 | 4 | Declaration, Low, Duplicate | Two tests pass constants through or test TimezoneCatalogue::position. Off the grid city is a variant of the London band test. The row search line is asserted in it_offers_every_zone_with_the_time_it_is_there (same 'tokyo japan asia/tokyo' and the same 419 count) |
| Unit/ViewModels/Settings/Account/Profile/ProfileViewModelTest.php | 7 | 2 | Duplicate, Low | email() is inside it_gives_the_details_of_the_user. hasPhoto is photo_path not null |
| Unit/ViewModels/Settings/Account/RelationshipTypes/RelationshipTypeFormViewModelTest.php | 10 | 2 | Low | Two "this plain type may be edited or deleted" tests that assert true |
| Unit/ViewModels/Settings/Account/RelationshipTypes/RelationshipTypesViewModelTest.php | 10 | 1 | Low | isSystem passthrough |
| Unit/ViewModels/Settings/Account/Security/SecurityViewModelTest.php | 29 | 5 | Low | user() passthrough, a trivial true, a route() restatement, and two more "N days ago" variants of the password changed test |
| Unit/ViewModels/Settings/Account/Templates/FieldCatalogueViewModelTest.php | 18 | 8 | Declaration, Cosmetic | The catalogue's own contents restated (non empty strings, list of types needing a value list, label list types, composite family) and three tests of the preview illustration kind and mark |
| Unit/ViewModels/Settings/Account/Templates/ShareTemplateViewModelTest.php | 5 | 1 | Declaration | Asserts the four labels of a static list |
| Unit/ViewModels/Settings/Account/Templates/StructureVersionEntryTest.php | 14 | 1 | Low | Third of three status label tests that fit one data provider |
| Unit/ViewModels/Settings/Account/Templates/TemplateBuilderViewModelTest.php | 32 | 4 | Declaration, Cosmetic | 37 glyphs and 9 colours counted, and the section mark read off the model |
| Unit/ViewModels/Settings/Account/Templates/TemplateFormViewModelTest.php | 9 | 4 | Low | isEdit, slugIsEditable, values() passthrough and a route() restatement |
| Unit/ViewModels/Settings/Account/ValueLists/ValueListsViewModelTest.php | 10 | 2 | Low | user() passthrough, isActive passthrough |
| Unit/ViewModels/Settings/SettingsViewModelTest.php | 9 | 3 | Low | Three route() restatements (notifications, sign out, delete) |
| Unit/ViewModels/Shared/SharedContactViewModelTest.php | 17 | 2 | Cosmetic | Icon name and tint of a section |
| Unit/ViewModels/Vaults/ContactDeadlinesBlockTest.php | 11 | 1 | Duplicate | emptyTitle() and emptyLine() delegate to ContactRemindersViewModel (app/ViewModels/Vaults/ContactDeadlinesBlock.php:51). Owned by Unit/ViewModels/Vaults/ContactRemindersViewModelTest.php::it_says_what_an_empty_filter_would_hold |
| Unit/ViewModels/Vaults/ContactDuplicatesViewModelTest.php | 9 | 2 | Duplicate | PageSizeTest. Each builds 25 pairs (about 75 rows) |
| Unit/ViewModels/Vaults/ContactEditViewModelTest.php | 1 | 1 | Low | The whole file is one account flag passthrough, the same check as Unit/ViewModels/Settings/Account/Profile/ProfileViewModelTest.php::it_offers_an_upload_only_while_file_upload_is_on_for_the_account |
| Unit/ViewModels/Vaults/ContactFormViewModelTest.php | 12 | 1 | Low | Restates two route() calls |
| Unit/ViewModels/Vaults/ContactMergeViewModelTest.php | 14 | 3 | Duplicate, Low | PageSizeTest (the first builds 45 contacts). The avatar URL is a route() restatement |
| Unit/ViewModels/Vaults/ContactRemindersViewModelTest.php | 11 | 1 | Duplicate | Asserts the same strings as it_says_what_an_empty_filter_would_hold in the same file |
| Unit/ViewModels/Vaults/ContactTimelineBlockTest.php | 17 | 2 | Cosmetic, Low | Icon and tint of an entry. A read only link that is a route() restatement |
| Unit/ViewModels/Vaults/ContactViewModelTest.php | 61 | 8 | Cosmetic, Low, Duplicate | Section marks and a pixel spacing, three route() restatements, ten notes a page (owned by Unit/ViewModels/Vaults/ContactFieldRowsViewModelTest.php::it_shows_ten_notes_at_a_time_newest_first, which ContactViewModel delegates to at ContactViewModel.php:647), a saved reminder read back (owned by Unit/ViewModels/Vaults/ContactReminderBlockTest.php::it_reads_a_saved_rhythm_back_as_one_choice_of_the_list, ::it_says_a_saved_rule_is_told_on_the_day_at_its_hour and ::it_says_when_a_saved_rule_next_falls_and_nothing_for_a_new_one) |
| Unit/ViewModels/Vaults/ContactsViewModelTest.php | 31 | 4 | Duplicate, Low | The French ordering test is a subset of it_orders_the_list_exactly_as_the_collator_would. Two tests restate URL paths, one restates the avatar route |
| Unit/ViewModels/Vaults/EntitiesViewModelTest.php | 9 | 3 | Duplicate | PageSizeTest (twice). it_marks_an_archived_record is covered by it_keeps_an_archived_record_in_its_place |
| Unit/ViewModels/Vaults/FamilyTreeBlockTest.php | 10 | 1 | Low | One substring of a static line |
| Unit/ViewModels/Vaults/ImmediateFamilyBlockTest.php | 11 | 1 | Duplicate | Same url assertion as it_lists_the_children_of_a_contact in the same file |
| Unit/ViewModels/Vaults/LifeEventFormBlockTest.php | 14 | 2 | Cosmetic, Declaration | Icon and colour of a type, and two copy strings handed to the panel |
| Unit/ViewModels/Vaults/RelationshipEditViewModelTest.php | 9 | 1 | Duplicate | sentenceGroups() maps RelationshipTypeChoices::groups(). Owned by Unit/ViewModels/Vaults/RelationshipTypeChoicesTest.php::it_groups_the_types_under_their_categories_in_the_order_the_account_set |
| Unit/ViewModels/Vaults/RelationshipFormViewModelTest.php | 15 | 3 | Duplicate, Low | typeGroups() delegates to RelationshipTypeChoices (RelationshipFormViewModel.php:95). Owned by RelationshipTypeChoicesTest::it_groups_the_types_under_their_categories_in_the_order_the_account_set and ::it_does_not_offer_a_withdrawn_type. The "more people than the list shows" test is an eight row variant of it_stops_short_of_loading_the_whole_vault |
| Unit/ViewModels/Vaults/RelationshipsBlockTest.php | 15 | 2 | Cosmetic, Low | Six icon names, a route() restatement |
| Unit/ViewModels/Vaults/ReminderHistoryTest.php | 12 | 1 | Low | Empty result, already asserted by it_leaves_out_a_notification_that_never_went_out |
| Unit/ViewModels/Vaults/ReminderRowTest.php | 17 | 1 | Cosmetic | Icon names gift and calendar |
| Unit/ViewModels/Vaults/ReminderViewModelTest.php | 14 | 1 | Low | title() returns the reminder name |
| Unit/ViewModels/Vaults/ShareLinkFormViewModelTest.php | 11 | 3 | Low | Three passthroughs of fields the link already holds (and their null case) |
| Unit/ViewModels/Vaults/VaultDashboardAttentionTest.php | 4 | 1 | Low | route() restatement |
| Unit/ViewModels/Vaults/VaultDashboardViewModelTest.php | 14 | 3 | Cosmetic, Low | Initial and colour of a tile, three route() restatements, title and description passthrough |
| Unit/ViewModels/Vaults/VaultFormViewModelTest.php | 12 | 1 | Declaration | Counts 8 colours and asserts the first is Blue |
| Unit/ViewModels/Vaults/VaultRemindersSentViewModelTest.php | 10 | 4 | Duplicate, Low | Outcome and tone are ReminderRow methods: owned by Unit/ViewModels/Vaults/ReminderRowTest.php::it_says_what_became_of_a_date. Channel labels are owned by ReminderRowTest::it_names_every_channel_that_carried_the_deadline. The other two are a negative substring check and a 1, 1, 1 count |
| Unit/ViewModels/Vaults/VaultRemindersViewModelTest.php | 11 | 1 | Low | Two URL suffixes |
| Unit/ViewModels/Vaults/VaultSettingsViewModelTest.php | 11 | 3 | Declaration, Low | The colour list is the one in VaultFormViewModelTest. Empty description and values() are passthroughs |
| Unit/ViewModels/Vaults/VaultsViewModelTest.php | 11 | 3 | Declaration, Cosmetic, Low | openTaskCount is hardcoded 0, the tint is a CSS color-mix string, the avatar route restated |
| Unit/ViewModels/Welcome/WelcomeViewModelTest.php | 3 | 2 | Low | A route() and two asset() calls restated |

Methods to remove, by file:

- Admin/AccountViewModelTest: it_counts_nothing_for_an_account_holding_no_vault, it_counts_bytes_as_bytes_while_they_still_are, it_hands_back_the_person_on_the_account, it_says_when_a_cancelled_subscription_stops
- Admin/AccountsViewModelTest: it_hands_back_one_page_at_a_time, it_hands_back_everybody_on_one_page_when_asked_for_all, it_finds_an_account_by_the_whole_address
- Admin/AdministratorLogsViewModelTest: it_hands_back_one_page_at_a_time, it_hands_back_everything_on_one_page_when_asked_for_all
- Admin/AdministratorsViewModelTest: it_hands_back_one_page_at_a_time, it_hands_back_everybody_on_one_page_when_asked_for_all, it_says_who_granted_it_and_when, it_leaves_the_console_unnamed_since_nobody_granted_it
- Admin/ConfigurationViewModelTest: it_offers_the_password_to_an_administrator_who_has_one, it_offers_nothing_to_an_administrator_with_neither_credential
- Admin/FlagsViewModelTest: it_offers_the_password_to_an_administrator_who_has_one, it_offers_nothing_to_an_administrator_with_neither_credential
- Admin/ModerationQueueViewModelTest: it_hands_back_one_page_at_a_time, it_hands_back_everything_on_one_page_when_asked_for_all
- Admin/ModerationSubmissionViewModelTest: it_points_the_two_decisions_at_the_routes_that_take_them, it_hands_back_the_listing_the_version_belongs_to
- Admin/OverviewViewModelTest: it_names_every_state_even_the_ones_nothing_landed_on, it_knows_an_instance_with_something_on_it_is_not_empty
- Auth/LoginViewModelTest: it_says_whether_the_magic_link_is_enabled, it_says_whether_registration_is_enabled
- Auth/RegisterViewModelTest: it_gives_the_urls_of_the_legal_pages
- Docs/ApiDocsFieldsTest: it_gives_every_row_a_name_a_type_and_a_description, it_keeps_the_type_and_the_description_of_an_attribute
- Docs/ApiDocsSectionTest: it_reads_the_return_fields_back_unchanged, it_keeps_the_summary_and_the_field_list_apart
- Docs/ApiDocsViewModelTest: it_splits_the_sidebar_between_guides_and_resources, it_gives_every_resource_entry_a_verb, it_falls_back_to_the_title_for_the_sidebar_label, it_points_the_base_url_at_the_versioned_api
- Docs/DocsViewModelTest: it_says_whether_registration_is_enabled
- Docs/McpDocsViewModelTest: it_counts_the_tools_it_lists, it_marks_only_the_groups_that_change_data, it_explains_how_to_connect_and_disconnect_an_assistant_through_oauth
- Docs/PortalCatalogueTest: it_orders_the_pages_by_the_numbers_in_front_of_their_folders_and_files
- Marketplace/MarketplaceStatisticsTest: it_draws_a_sliver_for_a_month_with_nothing_in_it
- Marketplace/MarketplaceViewModelTest: it_says_whether_registration_is_enabled
- Settings/Account/Billing/BillingViewModelTest: it_shows_the_currency_the_reader_picked, it_says_whether_stripe_knows_the_account, it_prices_the_amount_alone_beside_the_whole_price
- Settings/Account/Billing/InvoicesViewModelTest: it_shows_twenty_payments_a_page
- Settings/Account/EntityTypes/EntityTypeFormViewModelTest: it_points_the_form_at_the_right_route
- Settings/Account/EntityTypes/EntityTypesViewModelTest: it_gives_three_example_profiles_with_three_fields_each, it_gives_the_user_to_the_sidebar
- Settings/Account/Logs/EmailsSentViewModelTest: it_gives_ten_emails_a_page, it_gives_the_person_who_is_signed_in
- Settings/Account/Logs/LogsViewModelTest: it_gives_five_logs_a_page, it_gives_the_person_who_is_signed_in
- Settings/Account/Preferences/PreferencesViewModelTest: it_gives_the_person_who_is_signed_in
- Settings/Account/Preferences/TimezonePanelTest: it_hands_the_picker_the_latitudes_the_map_is_cut_off_at, the_latitudes_it_hands_over_are_the_ones_the_marker_is_drawn_with, it_snaps_a_city_off_the_grid_to_the_hour_it_belongs_to, it_hands_every_row_the_line_the_search_matches_on
- Settings/Account/Profile/ProfileViewModelTest: it_gives_the_email, it_knows_whether_there_is_a_photo
- Settings/Account/RelationshipTypes/RelationshipTypeFormViewModelTest: a_type_the_user_invented_may_be_turned_round, a_type_nobody_uses_can_be_deleted
- Settings/Account/RelationshipTypes/RelationshipTypesViewModelTest: it_says_which_types_the_product_supplied
- Settings/Account/Security/SecurityViewModelTest: it_gives_the_person_who_is_signed_in, it_lets_a_connected_user_remove_their_password, it_gives_the_address_a_device_is_given, it_says_when_a_key_last_answered, it_gives_how_long_ago_the_api_key_was_last_used
- Settings/Account/Templates/FieldCatalogueViewModelTest: it_describes_every_type_it_offers, it_says_which_types_need_a_value_list, it_says_which_types_point_at_a_record, it_offers_a_label_list_for_an_email_a_phone_and_an_address, it_shows_an_email_and_a_phone_among_the_composite_types, it_gives_every_type_a_mark_and_an_illustration, it_draws_an_email_and_a_phone_as_a_label_beside_a_value, it_draws_an_address_as_what_it_is_for_above_the_boxes_it_is_written_in
- Settings/Account/Templates/ShareTemplateViewModelTest: it_lists_what_is_in_the_file
- Settings/Account/Templates/StructureVersionEntryTest: it_reads_a_retired_version
- Settings/Account/Templates/TemplateBuilderViewModelTest: it_offers_every_glyph_a_section_may_be_marked_with, it_offers_nine_colours_with_neutral_first_and_each_one_named, it_reads_the_mark_off_the_section, it_reads_an_empty_mark_off_a_section_that_carries_none
- Settings/Account/Templates/TemplateFormViewModelTest: it_says_whether_it_is_naming_or_renaming, it_starts_the_fields_with_what_the_template_says, it_sends_cancel_back_to_where_the_form_was_opened_from, it_only_offers_the_slug_while_the_template_is_being_created
- Settings/Account/ValueLists/ValueListsViewModelTest: it_gives_the_user_to_the_sidebar, it_says_whether_a_value_is_still_active
- Settings/SettingsViewModelTest: it_links_notifications_to_the_notification_channels, it_marks_signing_out_as_something_that_is_sent_rather_than_followed, it_sends_deleting_the_account_to_the_confirmation_on_the_profile_screen
- Shared/SharedContactViewModelTest: it_carries_the_mark_of_each_section_it_draws, it_falls_back_to_the_neutral_mark_for_a_section_carrying_none
- Vaults/ContactDeadlinesBlockTest: it_says_what_an_empty_filter_would_hold_in_the_reminders_screen_s_words
- Vaults/ContactDuplicatesViewModelTest: it_pages_the_pairs_twenty_at_a_time, it_holds_every_pair_on_one_page_for_all
- Vaults/ContactEditViewModelTest: it_offers_an_upload_only_while_file_upload_is_on_for_the_account (delete the file)
- Vaults/ContactFormViewModelTest: it_says_where_the_form_leads_out_of
- Vaults/ContactMergeViewModelTest: it_pages_the_candidates_twenty_at_a_time, it_puts_every_candidate_on_one_page_when_asked_for_all, it_says_where_the_picture_of_each_record_is_read_from
- Vaults/ContactRemindersViewModelTest: it_says_what_another_filter_would_hold_when_asked
- Vaults/ContactTimelineBlockTest: it_marks_a_life_event_with_the_glyph_and_colour_of_its_type, it_links_every_entry_to_the_contact_page_when_read_only
- Vaults/ContactViewModelTest: a_block_wears_the_mark_of_the_section_it_draws, a_block_on_an_unmarked_section_falls_back_to_the_blank_page_and_the_neutral_pair, the_space_below_a_version_grows_with_how_long_the_one_under_it_was_in_use, it_points_the_star_at_the_route_that_changes_the_mark, it_says_where_the_pages_of_the_record_are_decided, it_says_where_the_picture_of_the_contact_is_read_from, it_shows_ten_notes_at_a_time_newest_first_with_the_way_to_the_rest, it_reads_a_saved_rule_back_in_words
- Vaults/ContactsViewModelTest: it_orders_the_list_by_the_alphabet_of_the_language_the_reader_reads_in, it_puts_the_chips_in_the_path_rather_than_the_query_string, it_aims_the_search_at_the_chip_in_force, it_says_where_the_picture_of_a_row_is_read_from
- Vaults/EntitiesViewModelTest: it_pages_the_records_twenty_at_a_time, it_holds_every_record_on_one_page_for_all, it_marks_an_archived_record
- Vaults/FamilyTreeBlockTest: it_says_what_the_drawing_leaves_out
- Vaults/ImmediateFamilyBlockTest: it_offers_the_way_through_to_somebody_who_has_a_record_of_their_own
- Vaults/LifeEventFormBlockTest: it_marks_each_type_with_its_glyph_and_colour_and_falls_back_to_the_blank_page, it_gives_the_panel_the_wording_of_the_note_shown_when_the_type_changes
- Vaults/RelationshipEditViewModelTest: it_groups_the_sentences_in_the_order_of_the_categories
- Vaults/RelationshipFormViewModelTest: it_groups_the_types_under_their_category, it_leaves_out_a_deactivated_type, it_offers_more_people_than_the_list_shows_at_once
- Vaults/RelationshipsBlockTest: it_marks_each_supplied_category_with_its_own_icon, it_links_to_the_relationship_types_settings
- Vaults/ReminderHistoryTest: it_has_nothing_to_say_about_a_reminder_nothing_has_happened_to
- Vaults/ReminderRowTest: it_marks_a_birthday_and_falls_back_to_a_calendar_for_anything_somebody_invented
- Vaults/ReminderViewModelTest: it_is_titled_by_the_reminder
- Vaults/ShareLinkFormViewModelTest: it_carries_what_the_link_already_says, a_form_with_no_link_behind_it_says_nothing_is_set, a_record_with_no_photograph_has_no_address_for_one
- Vaults/VaultDashboardAttentionTest: it_leads_to_every_reminder_of_the_vault
- Vaults/VaultDashboardViewModelTest: it_says_what_the_vault_is_called_and_what_it_is_for, it_marks_the_vault_with_its_initial_and_its_colour, it_says_where_every_box_leads
- Vaults/VaultFormViewModelTest: it_offers_the_colours_a_vault_may_be_told_apart_by
- Vaults/VaultRemindersSentViewModelTest: it_says_what_was_answered_for_each_one, it_says_how_the_reminder_reached_somebody, it_never_says_a_reminder_was_completed, it_counts_every_tab
- Vaults/VaultRemindersViewModelTest: it_gives_each_grouping_an_address_of_its_own
- Vaults/VaultSettingsViewModelTest: it_offers_every_colour_a_vault_may_be_told_apart_by, it_has_an_empty_description_when_the_vault_has_none, it_starts_the_naming_form_on_what_the_vault_holds
- Vaults/VaultsViewModelTest: no_task_is_ever_open_while_the_application_writes_none_down, the_mark_on_a_card_is_the_colour_somebody_chose_for_the_vault, a_face_carries_the_picture_of_whoever_has_one
- Welcome/WelcomeViewModelTest: it_sends_the_button_to_the_address_that_marks_somebody_as_welcomed, it_shows_the_portraits_of_the_two_people_who_wrote_the_letter

Trim, do not delete (cosmetic or route assertions inside a test that is otherwise worth keeping): the tone strings in ReminderRowTest::it_says_what_became_of_a_date, ContactShareLinksViewModelTest (three state tests), StructureVersionEntryTest (three status tests), AccountTransferViewModelTest (two tests), McpCallsViewModelTest::it_spells_out_what_a_row_unfolds_into and AccountViewModelTest::it_badges_a_person_who_cannot_sign_in; the cadenceIcon and icon assertions in ReminderRowTest::it_draws_the_date_the_cadence_and_the_person and ::it_says_a_rule_that_fires_once_fires_once; the icons list in VaultDashboardAttentionTest::it_bands_what_is_overdue_due_today_and_due_within_seven_days; tint, icon and colour in EntityTypesViewModelTest::it_gives_a_row_everything_it_is_drawn_from; route() lines in LifeEventTypeFormViewModelTest::it_adds_when_it_holds_no_type, StructureVersionHistoryViewModelTest::it_points_back_at_the_builder, ContactMergeViewModelTest::it_offers_the_way_into_both_records_with_the_one_that_stays_first, ContactDeadlinesBlockTest::it_leads_to_all_of_the_contact_s_reminders, ContactDuplicatesViewModelTest::it_says_in_words_why_a_pair_is_on_the_screen (asserts the substrings /merge/ and /dismissal).

### Patterns

1. Paging tests repeated per view model, 15 removable. Twelve view models call PageSize::paginate (Admin x4, Invoices, McpCalls, ContactDuplicates, ContactMerge, ContactShareLinks, Contacts, Entities, ShareLink). Unit/Helpers/PageSizeTest.php already proves a size is honoured, that "all" holds everything on one page, and that the last page is reached. Per view model tests of "20 a page" and "all on one page" therefore prove only that the view model calls the helper. Examples: the four identical it_hands_back_one_page_at_a_time and four it_hands_back_everything_on_one_page_when_asked_for_all in Admin, EntitiesViewModelTest and ContactDuplicatesViewModelTest. Two more tests assert a hardcoded page size of 10 and 5 (EmailsSentViewModelTest, LogsViewModelTest). Keep the tests that add a rule to paging: AccountsViewModelTest::it_keeps_the_search_in_the_page_links, ContactsViewModelTest::it_pages_at_the_size_asked_and_keeps_the_view_in_its_links, ContactShareLinksViewModelTest::it_shows_twenty_links_a_page_newest_first_with_the_revoked_ones_kept, the heading continuation tests.

2. Paging repeated again in the controller layer (cited here, outside this scope). The Feature controller tests below rebuild the same fixture and assert the same counts. The view model owns paging; the controller should keep one test that proves ?page= and ?size= reach the view model. Candidates: Feature/Controllers/App/Settings/Account/Billing/InvoiceControllerTest.php::it_pages_the_payments_twenty_at_a_time (duplicate of InvoicesViewModelTest::it_shows_twenty_payments_a_page), Vaults/ContactMergeControllerTest.php::it_pages_the_picker_and_offers_a_search_field (ContactMergeViewModelTest::it_pages_the_candidates_twenty_at_a_time), Vaults/ContactReminderControllerTest.php::the_history_loads_twenty_five_answers_at_a_time (ContactRemindersViewModelTest::the_history_shows_the_newest_twenty_five_answers_and_counts_them_all and ::the_last_page_of_the_history_holds_what_is_left_and_offers_no_more), Vaults/VaultReminderControllerTest.php::it_offers_the_next_page_and_answers_it (VaultRemindersViewModelTest::it_shows_one_page_of_rows_and_gives_a_group_its_heading_only_where_it_starts), Vaults/ContactFieldRowControllerTest.php::it_answers_one_page_of_notes_with_the_way_to_the_next (ContactFieldRowsViewModelTest::it_shows_ten_notes_at_a_time_newest_first), NotificationControllerTest.php::a_full_page_offers_to_load_more and ::the_last_page_holds_the_rest_and_says_it_is_the_end (NotificationBarViewModelTest::it_reads_the_newest_twenty_five_and_points_at_the_next_page and ::the_second_page_holds_the_rest_and_points_nowhere), Vaults/ContactControllerTest.php::it_offers_numbered_pages_and_a_choice_of_how_many_rows_a_page_holds (ContactsViewModelTest::it_pages_at_the_size_asked_and_keeps_the_view_in_its_links), Vaults/EntityControllerTest.php::it_reads_the_page_size_from_the_query (EntitiesViewModelTest, which this audit already reduces). I opened InvoiceControllerTest and ContactReminderControllerTest and confirmed the overlap; the others I matched by name and fixture only.

3. Empty state copy tested in the controller as well. Feature/Controllers/App/Vaults/ContactReminderControllerTest.php::every_filter_answers_at_its_own_address loops the five filters and asserts the same five strings that ContactRemindersViewModelTest::it_says_what_an_empty_filter_would_hold asserts. The ContactDeadlinesBlockTest copy is a third time. Three copies of one wording.

4. Tests that restate route(), about 24. The test builds a URL and asserts the method returned the same route() call. Examples: SettingsViewModelTest (three), WelcomeViewModelTest::it_sends_the_button_to_the_address_that_marks_somebody_as_welcomed, ContactViewModelTest::it_points_the_star_at_the_route_that_changes_the_mark, VaultDashboardViewModelTest::it_says_where_every_box_leads, TemplateFormViewModelTest::it_sends_cancel_back_to_where_the_form_was_opened_from. A wrong route name throws in any render test; a wrong route parameter is caught by the controller render. The same applies to the avatar thumbnail URL, which 12 view models build with an identical expression (ReminderViewModel, ReminderRow, EntitiesViewModel, RelationshipsBlock, ShareLinkFormViewModel, ContactMergeViewModel, VaultDashboardViewModel, ContactsViewModel, SearchViewModel, ImmediateFamilyBlock, FamilyTreeBlock, VaultsViewModel) and four test files assert (ContactMergeViewModelTest, ContactsViewModelTest, ContactViewModelTest, VaultsViewModelTest). One Contact::avatarThumbnailUrl() with one unit test would replace both.

5. Icon, tint, colour, tone and pixel strings, 17 tests removed and about 15 more assertions to trim. Examples: SharedContactViewModelTest::it_carries_the_mark_of_each_section_it_draws, RelationshipsBlockTest::it_marks_each_supplied_category_with_its_own_icon (asserts six icon names), VaultsViewModelTest::the_mark_on_a_card_is_the_colour_somebody_chose_for_the_vault (asserts 'color-mix(in srgb, #2f7350 12%, var(--canvas))'), MarketplaceStatisticsTest::it_draws_a_sliver_for_a_month_with_nothing_in_it (asserts height 4), ContactViewModelTest::the_space_below_a_version_grows_with_how_long_the_one_under_it_was_in_use. I kept ContactViewModelTest::a_block_falls_back_when_the_section_holds_a_glyph_it_may_not_use because it proves an allowlist, not a look. Five view models carry the same Section::ICONS fallback (ContactTimelineBlock, VaultDashboardViewModel, LifeEventFormBlock, ContactViewModel, SharedContactViewModel); testing it once through one of them is enough.

6. Snapshots of catalogues, 20 tests. A hardcoded list that restates a declaration and breaks every time the product grows: the 25 API resource names (ApiDocsViewModelTest), 70 page ids (PortalCatalogueTest), 92 tools and 11 group names (McpDocsViewModelTest), 37 glyphs and 9 colours (TemplateBuilderViewModelTest), 8 colours twice (VaultFormViewModelTest, VaultSettingsViewModelTest), the Field catalogue's own non empty descriptions and family contents (FieldCatalogueViewModelTest). Consistency tests that compare two sources are good and stay (it_lists_exactly_the_tools_the_server_registers, it_marks_as_available_exactly_what_the_enum_ships, it_documents_every_registered_route, the PortalCatalogue frontmatter, link and screenshot checks).

7. Passthrough of a model attribute, a config value or the user. About 18 tests: it_gives_the_person_who_is_signed_in (PreferencesViewModelTest, SecurityViewModelTest, LogsViewModelTest, EmailsSentViewModelTest), it_gives_the_user_to_the_sidebar (EntityTypesViewModelTest, ValueListsViewModelTest), it_says_whether_registration_is_enabled (LoginViewModelTest, DocsViewModelTest, MarketplaceViewModelTest), the legal URLs, three ShareLinkFormViewModelTest tests, the vault name and description.

8. Sibling classes retest the class they delegate to. ContactDeadlinesBlock delegates to ContactRemindersViewModel, ContactViewModel delegates to ContactFieldRowsViewModel and ContactReminderBlock, RelationshipFormViewModel and RelationshipEditViewModel delegate to RelationshipTypeChoices, three view models delegate to ConfirmPasswordViewModel, VaultRemindersSentViewModel uses ReminderRow. The owner's test already covers the output; the delegate needs one wiring test. One gap runs the other way: ContactReminderBlock::quietBecause() is tested only through ContactViewModelTest (it_says_why_a_rule_has_gone_quiet, it_finds_a_rule_whose_date_has_been_cleared), which are the right assertions in the wrong file. Move them to ContactReminderBlockTest rather than delete them.

9. One test per label that fits a data provider, no removals counted. ContactShareLinksViewModelTest (Live, Expired, Revoked), StructureVersionEntryTest (Published, Draft, Retired), ReminderWordsTest (four tests, one function), ContactsViewModelTest (five accent and locale search tests: French, German, accent kept, Spanish, Turkish), four "alphabet of the language the reader reads in" tests with the same Zoe, Élodie, Erin fixture (AdministratorLogsViewModelTest, MarketplaceViewModelTest, ContactsViewModelTest, TemplatesViewModelTest as the negative case).

10. Misplaced tests. InvoicesViewModelTest::it_writes_the_amount_for_the_reader asserts Payment::total() (a model accessor). TimezonePanelTest::the_latitudes_it_hands_over_are_the_ones_the_marker_is_drawn_with asserts TimezoneCatalogue::position. ApiDocsSectionTest and ApiDocsFieldsTest test classes that are not view models.

### Cost hot spots

- ReminderHistoryTest::it_pages_the_merge_fifty_at_a_time_newest_first builds 120 events across three sources (logs, notifications, occurrences), about 300 to 400 inserts. ReminderHistoryTest::it_offers_more_when_one_source_alone_fills_the_page and ReminderViewModelTest::it_pages_the_delivery_log_fifty_at_a_time_on_its_own_page_name each insert 51 logs. If PER_PAGE were a constructor argument (or a smaller class constant in tests), the same merge logic is proven with 8 events.
- ContactsViewModelTest: 120 contacts in it_pages_at_the_size_asked_and_keeps_the_view_in_its_links, 55 in it_shows_everybody_exactly_once_across_the_pages, 52 in it_repeats_no_heading_when_a_page_opens_inside_a_run, 22 in it_tells_a_favourite_run_apart_from_a_page_boundary_at_any_size, and it_reads_the_names_of_a_page_of_people_without_a_query_per_person builds 1, then 3, then 12 people each with two name representations, three times over. A size of 10 or 20 would shrink the first three to a tenth.
- ContactRemindersViewModelTest::sixtyAnswers() inserts 60 occurrences and is called by three tests. ContactTimelineBlockTest builds 32, 32 and 30 life events (each with a participant) in three tests. ShareLinkViewModelTest::it_shows_twenty_visits_a_page_newest_first_and_still_counts_them_all inserts 60 visits to assert three pages. ContactMergeViewModelTest::it_pages_the_candidates_twenty_at_a_time inserts 46 contacts. ContactDuplicatesViewModelTest builds 25 pairs (75 rows) in two tests that this audit removes.
- SearchViewModelTest: four tests call useSearchEngine() and three also call waitForSearchEngine() (up to a 10 second poll on a real Meilisearch index). Two tests (it_shows_each_result_with_the_words_marked_where_they_were_found and it_marks_the_words_found_in_a_name_and_says_which_vaults_were_searched) both index a contact and assert the same markup of a hit. Keep one search per rule, and assert the copy (countLine, nothingFoundHint) in a test that does not index anything.
- BillingViewModelTest and InvoicesViewModelTest call StripeCatalogue::forget() and fakeStripe() in setUp for every test. In InvoicesViewModelTest only it_falls_back_to_a_plain_word_when_nothing_names_the_offer reaches StripeCatalogue::kindOfPayment, so six tests build a fake Stripe client for nothing.
- TimezonePanelTest builds all 419 zones in three tests and hardcodes the count (419), which changes with the tz database.
- PortalCatalogueTest reads every Markdown file of every locale with Finder in five tests (frontmatter, ids and addresses, links, screenshots, unused screenshots). These are content lints with real value; they do not need RefreshDatabase and are cheap on a database, but cost file reads. Nothing to change, only noting that the cost is I/O not SQL.
- AccountImportViewModelTest::readImport() writes a fixture to a fake disk and runs ReadMonicaFourFile in seven tests. Reading once per class (setUpBeforeClass is not available with RefreshDatabase) is not possible, so keep, but the reading action is already tested in the action layer; a fixture built through AccountImportItem factories would drop it.
- Fixture duplication, not CI time: the same "contact with a template, a page and a section" builder is copied into ShareLinkFormViewModelTest, ShareLinkViewModelTest, ContactShareLinksViewModelTest and SharedContactViewModelTest; "publishedContactTemplate" is copied into ContactFormViewModelTest, VaultFormViewModelTest and VaultSettingsViewModelTest; rule() and deadline() are copied into four reminder tests; contactOn, categoryOf, typeOf into four relationship tests. Each copy builds a Template, StructureVersion, StructureVersionPage, Section, SectionVersion and StructureVersionSection, about 8 to 12 inserts per call.

### Proposed guidelines
- A view model test asserts a rule the view model owns. Do not assert that a method returns a model attribute, a config value, a constant, the user it was given or a route() call. If the screen's controller render test needs the value, it already proves it.
- Do not write a view model test for paging when the view model calls PageSize::paginate. One test per view model may prove that size and page reach the helper; the counts are PageSizeTest's.
- When a view model delegates to another class (a block, a choices object, ConfirmPasswordViewModel), write one wiring test in the delegate's view model and leave the rest to the owner's test. Name the owner in the PR when you add the delegating call.
- Never assert an icon name, a colour, a tint, a tone or a pixel size. If the value changes behaviour (an allowlist that refuses a stored value), assert the refusal once, not each fallback.
- Do not snapshot a catalogue. Assert that two sources agree (the enum against the catalogue, the registered routes against the documented routes) or build three fixture records and assert the rule.
- Do not write one test per label string. Use a #[DataProvider] when the only difference between tests is the input and the wording.
- Empty state and filter copy is tested once, in the view model that owns it. A controller render test names one string to recognise the screen.
- Build a large fixture only when the rule needs it. Paging rules need three rows and a smaller page size, not 120 rows. Ask the class under test for a page size argument rather than building to its constant.
- Keep a fixture builder used by three or more test files in one place (a trait in tests/Fixtures or a TestCase helper) so a schema change is made once.
- A test that needs the real search engine asserts one hit and its markup. Copy and empty states go in a test that does not index.

### Open questions
- Paging: I removed tests that duplicate PageSizeTest but did not prove that each of the 12 view models really passes its `size` argument through. A one line wiring test per view model is a reasonable alternative to deleting every copy. I kept one such test where it also asserts a second rule (see Pattern 1).
- Controller duplicates in Pattern 2 and 3: I read InvoiceControllerTest and ContactReminderControllerTest and confirmed the same fixture and counts. The other seven controller tests are matched by test name and by grep of the page parameter only. The controller auditor should confirm each before deleting.
- Docs/PortalMarkdownTest::it_escapes_html_written_in_a_page asserts that a script tag is escaped. The tests skill says not to test sanitization (TextSanitizer), and this is the Markdown renderer rather than TextSanitizer, so I kept it. Confirm whether the rule is meant to reach the renderer.
- TimezonePanelTest::it_places_the_marker_where_the_zone_is, ::it_snaps_the_hour_band_to_the_fifteen_degree_grid and ::it_clips_the_hour_band_that_straddles_the_antimeridian assert map coordinates in percent. They are geometry, which could count as layout, but they check latitude and longitude arithmetic with an invariant (Greenwich near 50, Tokyo east of it). I kept them. A reviewer who treats any percentage as drawing could cut three more.
- ContactViewModelTest::a_block_falls_back_when_the_section_holds_a_glyph_it_may_not_use is an icon test that I kept because it proves an allowlist. If the icon name is never user supplied, it is cosmetic too.
- StructureVersionHistoryViewModelTest::it_reads_the_whole_tree_without_a_query_per_version asserts at most 6 queries. A query count budget is brittle but it guards an N plus one that the rules ask about (the same pattern is in LifeEventTypesViewModelTest, ContactsViewModelTest, ImmediateFamilyBlockTest, ContactFormViewModelTest and VaultFormViewModelTest). I kept all of them. I did not run them to check how long they take.
- I did not run the suite, so I do not know the timings of individual view model tests. The cost list comes from reading fixtures only.

> Checked by the coordinator: `PageSizeTest::it_pages_a_query_at_the_size_asked` (line 87) and `::it_holds_everything_on_one_page_for_all` (line 99) exist, and 12 view models call `PageSize::paginate`, so the per view model paging tests are duplicates. The controller side paging duplicates are confirmed for two files only.

## Unit/Mcp and Feature/Mcp

Files read in full: the 92 tool tests under `tests/Unit/Mcp/Tools` (319 `#[Test]` methods, one file per tool in `app/Mcp/Tools`, plus the `MirrorsAnApiCapability` trait they share) and the 10 files under `tests/Feature/Mcp` (72 methods). Total 391 methods. I also opened `app/Mcp/Tools/MirrorsAnApiCapability.php`, a dozen tool classes, the matching action tests under `tests/Unit/Actions` (every tool wraps an action that has its own test file), `tests/Unit/Helpers/McpPayloadTest.php` and `tests/TestCase.php::useSearchEngine`. A tool should own its schema and validation, the mapping of arguments to the action, the lookup scoped to the account and the vault (done once in the shared trait), the relay of an action refusal as `Response::error`, and the response shape. It should not re-prove what the action refuses. Each tool uses one `catch` to relay refusals, so one refusal test per tool proves the relay and a second refusal from the same catch is a Duplicate of the action test. Feature/Mcp owns the transport: authentication, OAuth, rate limit, call recording, locale, sensitive field gating and the catalogue. There is no per tool name, description or schema conformance test, but there is one in the other direction: 91 of the 92 tool files carry the identical `it_names_the_api_capability_it_mirrors` test.

### Totals
| Bucket | Tests |
| --- | --- |
| Keep | 231 |
| Duplicate | 28 |
| Cosmetic | 0 |
| Declaration | 93 |
| Low value | 39 |
| Total | 391 |

### Findings by file
Every one of the 92 tool files has the mirrors test except `SearchRecordsTest`. Those 91 are listed once as a group, then the other findings per file.

| File (path relative to tests/) | Tests | Remove | Bucket(s) | Why, citing the owning test when Duplicate |
| --- | --- | --- | --- | --- |
| Unit/Mcp/Tools/*Test.php (91 files) | 91 | 91 | Declaration | `it_names_the_api_capability_it_mirrors` restates the `$mirrors` property. `Feature/Mcp/McpCatalogueTest::every_capability_names_an_api_capability_that_exists` already proves each tool names a route that exists. |
| Unit/Mcp/Tools/AddTemplateSectionTest.php | 5 | 1 | Duplicate | `it_refuses_a_section_already_in_the_version` shares one `catch` with the published refusal, which stays. Owner: `Unit/Actions/AddSectionToStructureVersionTest::it_refuses_the_same_section_twice_on_one_structure_version`. |
| Unit/Mcp/Tools/CreateContactRelationshipTest.php | 4 | 1 | Duplicate | Owner: `Unit/Actions/CreateRelationshipTest::it_refuses_a_type_that_can_no_longer_be_chosen`. The tool's own refusal for a type of another account stays. |
| Unit/Mcp/Tools/CreateLifeEventTest.php | 4 | 1 | Duplicate | Owner: `Unit/Actions/CreateLifeEventTest::it_refuses_a_type_that_can_no_longer_be_chosen` (same catch as the other action refusals). |
| Unit/Mcp/Tools/CreateTemplateDraftTest.php | 3 | 1 | Duplicate | Owner: `Unit/Actions/CreateStructureVersionDraftTest::it_answers_with_the_draft_that_is_already_open_rather_than_starting_a_second`. |
| Unit/Mcp/Tools/DeleteLifeEventCategoryTest.php | 4 | 1 | Duplicate | Two refusals, one `catch (AuthorizationException)`. Owner: `Unit/Actions/DestroyLifeEventCategoryTest::it_refuses_to_delete_a_supplied_category`. |
| Unit/Mcp/Tools/DeleteLifeEventTypeTest.php | 4 | 1 | Duplicate | Owner: `Unit/Actions/DestroyLifeEventTypeTest::it_refuses_a_type_the_product_supplied`. |
| Unit/Mcp/Tools/DeleteRelationshipCategoryTest.php | 4 | 1 | Duplicate | Owner: `Unit/Actions/DestroyRelationshipCategoryTest::it_refuses_to_delete_a_supplied_category`. |
| Unit/Mcp/Tools/DeleteRelationshipTypeTest.php | 4 | 1 | Duplicate | Owner: `Unit/Actions/DestroyRelationshipTypeTest::it_refuses_to_delete_a_supplied_type`. |
| Unit/Mcp/Tools/GetLifeEventTest.php | 4 | 1 | Duplicate | `it_never_returns_a_sensitive_value`. Owner: `Feature/Mcp/McpSensitiveFieldsTest::it_hides_a_sensitive_value_from_a_key_without_the_ability`, which asserts the same through the real endpoint. |
| Unit/Mcp/Tools/ListContactNameRepresentationsTest.php | 3 | 1 | Duplicate | Vault and account lookup. Owner: `Feature/Mcp/McpCompartmentTest::it_reports_a_contact_of_another_vault_of_the_same_account_as_not_existing` and `::it_reports_a_vault_of_another_account_as_not_existing`. |
| Unit/Mcp/Tools/UpdateContactNameRepresentationsTest.php | 4 | 1 | Duplicate | Same test and same owners as above. |
| Unit/Mcp/Tools/ListContactRelationshipsTest.php | 3 | 1 | Duplicate | Owner: `McpCompartmentTest::it_reports_a_contact_of_another_account_as_not_existing`. |
| Unit/Mcp/Tools/ListContactRemindersTest.php | 3 | 1 | Duplicate | Same owner. |
| Unit/Mcp/Tools/ListTemplateVersionsTest.php | 4 | 2 | Duplicate, Low value | `it_reports_a_template_of_another_account_as_not_existing` is called by name in `McpCompartmentTest::it_reports_a_template_of_another_account_as_not_existing` (same tool, same input). `it_refuses_without_a_template` is Low value. |
| Unit/Mcp/Tools/UpdateContactRelationshipTest.php | 4 | 1 | Duplicate | `it_refuses_an_end_before_the_start`. Owner: `Unit/Actions/UpdateRelationshipTest::it_refuses_an_end_that_falls_before_the_start`. |
| Unit/Mcp/Tools/UpdateLifeEventCategoryTest.php | 3 | 1 | Duplicate | Owner: `Unit/Actions/UpdateLifeEventCategoryTest::it_renames_a_supplied_category_because_being_supplied_governs_deletion_only`. |
| Unit/Mcp/Tools/CreateContactTest.php | 4 | 1 | Low value | `it_refuses_without_a_given_name`: required argument. |
| Unit/Mcp/Tools/CreateEntityTypeTest.php | 6 | 2 | Low value | Slug with capitals and icon outside the list are the third and fourth validation variants. The unique slug refusal stays. |
| Unit/Mcp/Tools/CreateLifeEventTypeTest.php | 4 | 1 | Low value | `it_refuses_an_icon_outside_the_allowed_ones`. |
| Unit/Mcp/Tools/GetContactTest.php | 5 | 2 | Low value | A contact that never existed (same lookup as the compartment tests) and `it_refuses_without_a_contact`. |
| Unit/Mcp/Tools/GetEmailSentTest.php | 4 | 1 | Low value | An identifier of `0` is the same lookup as another account. |
| Unit/Mcp/Tools/GetLogTest.php | 4 | 1 | Low value | An identifier of `abc`, same reason. |
| Unit/Mcp/Tools/ListContactsTest.php | 9 | 3 | Low value | Page size that is not a number, an empty vault, and no vault given. |
| Unit/Mcp/Tools/ListLogsTest.php | 7 | 3 | Low value | The pagination branches are a copy of `ListContactsTest::it_brings_an_absurd_page_size_back_into_range` and `::it_refuses_a_page_size_that_is_not_a_number`. `it_writes_nothing_to_the_activity` guards a read tool against writing, which nobody adds. |
| Unit/Mcp/Tools/ListEntityTypesTest.php, ListLifeEventCategoriesTest.php, ListRelationshipCategoriesTest.php, ListRelationshipTypesTest.php, ListTemplatesTest.php, ListVaultsTest.php | 4, 4, 4, 4, 4, 3 | 6 (one each) | Low value | `it_answers_an_account_holding_nothing_without_erroring`. An empty collection through `resolve()` is not a branch. |
| Unit/Mcp/Tools/SetContactFavouriteTest.php | 4 | 1 | Low value | `it_refuses_without_being_told_which_way`. |
| Unit/Mcp/Tools/SetLifeEventCategoryActivationTest.php, SetLifeEventTypeActivationTest.php, SetRelationshipCategoryActivationTest.php, SetRelationshipTypeActivationTest.php | 4 each | 1 each | Low value | `it_journals_nothing_when_the_..._is_already_in_that_state`. Same short circuit, copied. Keep the one in `SetEntityTypeActivationTest` (and ideally move the short circuit into one place). |
| Unit/Mcp/Tools/UpdateEntityTypeTest.php | 5 | 2 | Low value | Colour outside the list, empty name. |
| Unit/Mcp/Tools/UpdateLifeEventTypeTest.php | 5 | 1 | Low value | Colour outside the list. |
| Feature/Mcp/McpChangelogTest.php | 9 | 7 | Low value | Six variants of `it_records_a_created_contact_as_having_arrived_over_mcp` (name, favourite, template, entity type, relationship category, life event category). The source detail is set by the middleware, not by the tool. Each also builds a vault, template and published version. `a_contact_an_assistant_created_carries_no_marker_of_its_own` asserts `is_me` and `is_partial` defaults, which belong to `CreateContactTest`. |
| Feature/Mcp/McpCompartmentTest.php | 18 | 11 | Duplicate, Low value | Lookup of another account's record runs through one trait. Duplicates of `it_reports_a_vault_of_another_account_as_not_existing`: the contact of another account, the me contact, the rename, the write into another template. Six `it_does_not_delete_a_..._of_another_account` tests duplicate the Get tests that use the same helper: `GetEntityTypeTest`, `GetRelationshipTypeTest`, `GetRelationshipCategoryTest`, `GetLifeEventTypeTest`, `GetLifeEventCategoryTest` (all `..._of_another_account_as_not_existing`) and `McpCompartmentTest::it_reports_a_template_of_another_account_as_not_existing`. `it_never_says_forbidden_where_it_means_not_found` is implied by the exact message already asserted (Low value). |
| Feature/Mcp/McpCallRecordingTest.php | 11 | 2 | Duplicate, Low value | `it_cuts_parameters_that_are_too_long_and_says_so` repeats `Unit/Helpers/McpPayloadTest::it_cuts_a_payload_that_is_too_long_without_splitting_a_character`. `it_records_nothing_for_a_browser_session` can be one more assertion in `it_records_nothing_for_a_request_without_a_valid_key`, since `McpAuthenticationTest::it_refuses_a_browser_session` proves the refusal. |
| Feature/Mcp/McpRateLimitTest.php | 4 | 1 | Duplicate | `it_limits_per_minute_on_the_hosted_instance` asserts 60, which `it_answers_too_many_requests_once_the_limit_is_spent` proves by behaviour. |
| Feature/Mcp/McpRefusalLanguageTest.php | 3 | 1 | Low value | `it_resolves_a_locale_exactly_as_the_json_api_does` is a third locale variant that makes an extra API call. |
| Feature/Mcp/McpTransportTest.php | 4 | 2 | Declaration | The two tests that read the middleware list of the route restate a declaration. Behaviour is proven by `McpRefusalLanguageTest::it_refuses_in_the_language_the_caller_announces` and `McpRateLimitTest::it_counts_one_key_apart_from_another`. |

Method names to remove, per file:

- Unit/Mcp/Tools, every file except SearchRecordsTest: `it_names_the_api_capability_it_mirrors`
- AddTemplateSectionTest: `it_refuses_a_section_already_in_the_version`
- CreateContactRelationshipTest: `it_refuses_a_relationship_type_that_is_switched_off`
- CreateLifeEventTest: `it_refuses_a_type_that_can_no_longer_be_chosen`
- CreateTemplateDraftTest: `it_returns_the_open_draft_rather_than_a_second_one`
- DeleteLifeEventCategoryTest: `it_refuses_a_category_that_came_with_the_account`
- DeleteLifeEventTypeTest: `it_refuses_a_life_event_type_that_came_with_the_account`
- DeleteRelationshipCategoryTest: `it_refuses_a_category_that_came_with_the_account`
- DeleteRelationshipTypeTest: `it_refuses_a_relationship_type_that_came_with_the_account`
- GetLifeEventTest: `it_never_returns_a_sensitive_value`
- ListContactNameRepresentationsTest, UpdateContactNameRepresentationsTest: `it_does_not_find_a_contact_through_another_vault_or_another_account`
- ListContactRelationshipsTest, ListContactRemindersTest: `it_refuses_a_contact_of_another_account`
- ListTemplateVersionsTest: `it_reports_a_template_of_another_account_as_not_existing`, `it_refuses_without_a_template`
- UpdateContactRelationshipTest: `it_refuses_an_end_before_the_start`
- UpdateLifeEventCategoryTest: `it_renames_a_category_that_came_with_the_account`
- CreateContactTest: `it_refuses_without_a_given_name`
- CreateEntityTypeTest: `it_refuses_a_slug_with_capitals_or_spaces`, `it_refuses_an_icon_outside_the_allowed_ones`
- CreateLifeEventTypeTest: `it_refuses_an_icon_outside_the_allowed_ones`
- GetContactTest: `it_reports_a_contact_that_never_existed_as_not_existing`, `it_refuses_without_a_contact`
- GetEmailSentTest, GetLogTest: `it_reports_an_identifier_that_is_not_a_number_as_not_existing`
- ListContactsTest: `it_refuses_a_page_size_that_is_not_a_number`, `it_answers_an_empty_vault_without_erroring`, `it_refuses_without_a_vault`
- ListLogsTest: `it_brings_an_absurd_page_size_back_into_range`, `it_refuses_a_page_size_that_is_not_a_number`, `it_writes_nothing_to_the_activity`
- ListEntityTypesTest, ListLifeEventCategoriesTest, ListRelationshipCategoriesTest, ListRelationshipTypesTest, ListTemplatesTest, ListVaultsTest: `it_answers_an_account_holding_nothing_without_erroring`
- SetContactFavouriteTest: `it_refuses_without_being_told_which_way`
- SetLifeEventCategoryActivationTest, SetRelationshipCategoryActivationTest: `it_journals_nothing_when_the_category_is_already_in_that_state`
- SetLifeEventTypeActivationTest, SetRelationshipTypeActivationTest: `it_journals_nothing_when_the_type_is_already_in_that_state`
- UpdateEntityTypeTest: `it_refuses_a_colour_outside_the_allowed_ones`, `it_refuses_an_empty_name`
- UpdateLifeEventTypeTest: `it_refuses_a_colour_outside_the_allowed_ones`
- Feature/Mcp/McpChangelogTest: `it_records_a_corrected_name_as_having_arrived_over_mcp`, `it_records_a_favourite_as_having_arrived_over_mcp`, `it_records_a_template_change_as_having_arrived_over_mcp`, `it_records_an_entity_type_change_as_having_arrived_over_mcp`, `it_records_a_relationship_type_change_as_having_arrived_over_mcp`, `it_records_a_life_event_type_change_as_having_arrived_over_mcp`, `a_contact_an_assistant_created_carries_no_marker_of_its_own`
- Feature/Mcp/McpCompartmentTest: `it_reports_a_contact_of_another_account_as_not_existing`, `it_reports_the_me_contact_of_another_account_as_not_existing`, `it_does_not_rename_a_contact_of_another_account`, `it_writes_nothing_into_a_template_of_another_account`, `it_does_not_delete_a_template_of_another_account`, `it_does_not_delete_an_entity_type_of_another_account`, `it_does_not_delete_a_relationship_type_of_another_account`, `it_does_not_delete_a_relationship_category_of_another_account`, `it_does_not_delete_a_life_event_type_of_another_account`, `it_does_not_delete_a_life_event_category_of_another_account`, `it_never_says_forbidden_where_it_means_not_found`
- Feature/Mcp/McpCallRecordingTest: `it_cuts_parameters_that_are_too_long_and_says_so`, `it_records_nothing_for_a_browser_session` (fold one assertion into the previous test)
- Feature/Mcp/McpRateLimitTest: `it_limits_per_minute_on_the_hosted_instance`
- Feature/Mcp/McpRefusalLanguageTest: `it_resolves_a_locale_exactly_as_the_json_api_does`
- Feature/Mcp/McpTransportTest: `it_resolves_the_locale_the_way_the_json_api_does`, `it_is_limited_per_key_rather_than_per_user`

Optional addition: one test in `McpCatalogueTest` that walks every registered tool, reads its schema and calls it with no arguments, asserting an error for each tool that declares a required argument. That replaces the five required argument tests above with one test.

### Patterns
- Identical per tool boilerplate: `it_names_the_api_capability_it_mirrors` in 91 files (91 tests). Examples: `CreateContactTest`, `ListVaultsTest`, `UpdateTemplateTest`.
- The same refusal asserted through several tools that share one trait lookup: 16 tests. Examples: five "of another account" tests duplicated by Get tools, six delete refusals in `McpCompartmentTest`, `ListContactRelationshipsTest::it_refuses_a_contact_of_another_account`.
- More than one refusal per tool when all refusals go through one `catch`: 10 tests. Examples: the four Delete tools with a supplied record refusal, `AddTemplateSectionTest`, `CreateLifeEventTest`. The action tests own the individual rules.
- Validation variants of one rule: 11 tests (required argument, icon, colour, slug format, page size not a number). Examples: `UpdateEntityTypeTest`, `ListContactsTest`, `CreateEntityTypeTest`.
- Empty collection tests: 6 identical files, `it_answers_an_account_holding_nothing_without_erroring`.
- Copied branches in copied code: activation no-op short circuit in 5 tools, pagination in 3 tools (`ListContacts`, `ListLogs`, `ListEmailsSent`). The tests were copied with the code. The real smell is the code, which a shared helper would test once.
- Middleware list assertions instead of behaviour: 2 in `McpTransportTest`.
- Seven near copies of one end to end assertion in `McpChangelogTest`.

### Cost hot spots
Unit/Mcp costs 8 seconds for 319 tests in the audit's local timing, so it is cheap per test. The CI cost is the database: every file uses `RefreshDatabase` and almost every test creates a user with an account, so each of the 160 removals also removes at least one `User::factory()->create()` and its account rows.
- `McpRateLimitTest`: two tests send 61 and 61 authenticated JSON-RPC requests (about 120 full stack requests). Setting the `mcp` limiter to a small number through config for the test would prove the same at a few requests.
- `McpOAuthTest`: five tests each run the full flow (register client, authorize, approve, token exchange). The authenticated cases (`revoked`, `deactivated person`, `admits`, `records the changes`) could issue a token with `Passport::actingAs` or a helper that creates the token directly and keep one full flow test.
- `McpChangelogTest`: nine tests, eight of them building a vault, template, published version and default template link. Seven removed.
- `SearchRecordsTest` (tool): `it_answers_what_the_search_found` starts the real Meilisearch engine, creates an index, waits for it to settle, and deletes it afterwards. It only needs to prove that `vault` and `query` reach the action; matching is proven by `Unit/Actions/SearchRecordsTest::it_finds_a_contact_despite_a_typing_mistake_or_a_missing_accent`. A collection or database Scout driver would do. I kept it in Keep for the argument mapping, not for the engine.
- `McpCompartmentTest` and `GetLifeEventTest::it_never_returns_a_sensitive_value` build several vaults and records for assertions that the trait already proves.

### Proposed guidelines
- A tool or controller test proves one refusal relayed per `catch` or per `abort_unless`. The other refusals of the action belong to the action test.
- Do not write a per tool test of a rule that lives in a shared trait or helper. Test it once, against one tool, in the feature test of the layer that owns the trait.
- Do not add a test that restates a declaration of the class (the route name a tool mirrors, a middleware list, an icon list). A reflection test over the catalogue proves it once for every tool.
- One validation test per tool proves the rule is wired. Do not add one per field, per allowed list or per type error.
- An empty collection is not a branch. Do not write an empty list test unless the code branches on emptiness.
- Copied code is a refactoring, not a reason to copy the tests. Extract the pagination or the activation short circuit and test it once.
- A feature test of a transport (log source, rate limit, locale) calls one tool, once. It does not repeat per tool.
- A test that needs the reference search engine must prove matching, not argument mapping. Mapping uses a fake engine.

### Open questions
- Position tests (`Set*PositionTest`, 8 files) assert the full reordering that the matching `Update*PositionTest` actions already prove. I kept them for the argument mapping (`position`, `uuid`). If you want to be stricter, one assertion that the action was reached would be enough.
- The activation off and on tests (5 files, 10 tests) are identical in shape and each maps `is_active` to a different action pair. I kept them. They are a good candidate for one `#[DataProvider]` test, which would keep the assurance and cut the code, not the tests.
- `McpOAuthTest::it_refuses_the_assistant_of_a_deactivated_person` and `::it_refuses_an_assistant_whose_token_was_revoked` repeat the key based tests in `McpAuthenticationTest`. They may run through a different guard in Passport, so I kept them. Looked at: `McpAuthenticationTest` and `McpOAuthTest`. Not looked at: the guard or middleware code that decides.
- `UpdateContactRelationshipTest::it_refuses_to_name_somebody_with_a_record_of_their_own` is kept as the one relayed refusal. I did not confirm whether its message comes from the same `catch` as the end before start refusal, which I removed. If it does, either one can go.
- `GetLifeEventTypeFieldsTest::it_answers_an_empty_list_for_a_type_with_no_fields` calls `handle()` directly and asserts `[]`. I kept it as a response shape test, but it is close to the empty list tests I removed.
- I did not run the suite, so a removed test that was the only one covering a branch of a tool would only show up as a coverage drop. I checked the branches I could see in the tool source for each removal.

> Checked by the coordinator: 91 files hold `it_names_the_api_capability_it_mirrors`, and `McpCatalogueTest::every_capability_names_an_api_capability_that_exists` exists. The catalogue test proves the named route exists, not that it is the right one, so a tool mapped to the wrong route would go unnoticed. That mapping is a declaration either way and the removal stands.

## Unit/Models, Unit/Enums, Unit/Helpers

I read every test file in the three folders in full: 56 files plus 3 under `Concerns` in Models (465 tests by `grep -c '#\[Test\]'`), 37 files in Enums (147 tests) and 34 files in Helpers (400 tests, one of which, `LocaleResolutionTest::it_matches_what_a_device_announces...`, runs 13 data provider cases). I read the class under test where ownership was in doubt (`Contact` scopes, `Vault`, `StructureFileReader`, the structure actions). The owning layers here are: models own accessors, scopes, computed attributes, model events and guards (frozen versions, append only logs); enums own logic that is not a plain `match` (parsing, ordering, boundaries); helpers own their own algorithms. The audit found that Helpers are the healthy part (one real rule per test, little to remove), Models carry a large block of declaration tests (route keys, uuid, soft delete, factory defaults), and Enums are mostly tests that restate a `match`.

### Totals

| Bucket | Tests |
| --- | --- |
| Keep | 736 |
| Duplicate | 15 |
| Cosmetic | 1 |
| Declaration | 188 |
| Low value | 72 |
| Total | 1012 |

By folder:

| Folder | Keep | Duplicate | Cosmetic | Declaration | Low value | Total |
| --- | --- | --- | --- | --- | --- | --- |
| Unit/Models | 323 | 14 | 0 | 95 | 33 | 465 |
| Unit/Enums | 49 | 0 | 1 | 89 | 8 | 147 |
| Unit/Helpers | 364 | 1 | 0 | 4 | 31 | 400 |

### Findings by file

Models (paths relative to `tests/`).

| File | Tests | Remove | Bucket(s) | Why, citing the owning test when Duplicate |
| --- | --- | --- | --- | --- |
| Unit/Models/StructureOwnershipTest.php | 7 | 7 | Duplicate 5, Declaration 2 | An action test file living in the Models folder. The publish, draft and cross account cases are proven by `Unit/Actions/PublishStructureVersionTest.php` (`it_freezes_a_draft`, `it_makes_the_version_the_one_the_template_offers`, `it_refuses_a_version_with_no_page_at_all`, `it_refuses_a_version_from_another_account`, `it_says_a_section_it_published_went_out_with_the_life_event_type`) and `Unit/Actions/CreateStructureVersionDraftTest.php` (`it_starts_a_draft_from_a_published_version`, `it_leaves_the_published_version_exactly_as_it_was`). The other two restate the owner morph and a unique index. |
| Unit/Models/ContactTest.php | 81 | 14 | Declaration 7, Low value 7 | Uuid, route key, soft delete, fillable, vcard columns, a schema index check. Low: key rebuild after rename is already inside `it_writes_the_string_the_lists_sort_on_whenever_it_is_saved`; the `parentsOf` mirrors of `childrenOf` share `typesBehavingAs()`. |
| Unit/Models/TemplateTest.php | 10 | 8 | Declaration 7, Low value 1 | Relationships, cast, uuid, slug. Keeps newest first ordering and the translation key accessor. |
| Unit/Models/UserTest.php | 24 | 9 | Declaration 5, Low value 4 | Unique index, `$hidden`, defaults, soft delete; framework behaviour (`getAuthPassword`, `hasVerifiedEmail`); overlapping passwordless and ability cases. |
| Unit/Models/RelationshipTest.php | 11 | 8 | Declaration 3, Duplicate 2, Low value 3 | `Relationship` uses `ResolvesModelInVault`, so the two resolve tests repeat `Concerns/ResolvesModelInVaultTest` (`a_model_naming_one_column_resolves_by_it`, `it_still_refuses_a_record_of_another_account`). `it_does_not_resolve_through_a_vault_it_is_not_in` runs its own Eloquent query and tests nothing of the model. |
| Unit/Models/ContactMergeTest.php | 6 | 4 | Declaration 1, Duplicate 2, Low value 1 | Same pattern as `RelationshipTest`; the model uses `ResolvesModelInVault`. |
| Unit/Models/VaultTest.php | 16 | 5 | Declaration 3, Duplicate 2 | The two alphabet tests repeat `Unit/Helpers/SlugBuilderTest::it_keeps_a_name_written_in_japanese` and `::it_falls_back_when_a_name_leaves_nothing_readable` (both go through `SlugBuilder::readable`). |
| Unit/Models/EntityTypeTest.php | 10 | 4 | Declaration | Uuid, route key, plain attributes, factory defaults. |
| Unit/Models/FieldTest.php | 14 | 6 | Duplicate 2, Declaration 4 | `it_stores_nothing_for_a_layout_type` and `it_knows_any_date_can_drive_a_reminder` assert enum methods already asserted in `Unit/Enums/FieldTypeEnumTest::it_says_which_types_store_nothing_at_all` and `::it_says_which_type_a_reminder_may_read_from`. Others: relationship, unique index, cast. |
| Unit/Models/LogTest.php | 18 | 4 | Declaration | Casts, column defaults, FK cascade. |
| Unit/Models/MarketplaceInstallationTest.php | 4 | 4 | Declaration | Factory states and relationships. |
| Unit/Models/ShareLinkVisitTest.php | 3 | 3 | Declaration | Factory defaults. |
| Unit/Models/ShareLinkTest.php | 7 | 3 | Declaration | Relationship, defaults, route key. |
| Unit/Models/SectionTest.php | 8 | 5 | Declaration 3, Low value 2 | Slug, uuid, icon count of 37 (brittle), plain name. |
| Unit/Models/StructureVersionSectionTest.php | 8 | 3 | Declaration | Plain attributes, factory state, unique index. |
| Unit/Models/StructureTest.php | 4 | 3 | Declaration | Cast, uuid, null morph. |
| Unit/Models/EntityTest.php | 13 | 5 | Declaration 3, Low value 2 | Uuid, soft delete, fillable; rebuild after rename is covered by the `sort_name` test. |
| Unit/Models/RelationshipTypeTest.php | 9 | 4 | Declaration 3, Low value 1 | Uuid, soft delete, null default. |
| Unit/Models/MarketplaceListingTest.php | 4 | 3 | Declaration 2, Low value 1 | Route key, uuid, default status. |
| Unit/Models/MarketplaceCategoryTest.php | 4 | 2 | Declaration | Route key, factory default. |
| Unit/Models/ValueListItemTest.php | 5 | 3 | Declaration 2, Low value 1 | Soft delete, defaults, plain label. |
| Unit/Models/RelationshipCategoryTest.php | 6 | 2 | Declaration | Uuid, soft delete. |
| Unit/Models/InstanceSettingTest.php | 4 | 2 | Declaration | Encrypted cast, unique index (see Open questions on the cast). |
| Unit/Models/SectionVersionTest.php | 9 | 2 | Declaration | Relationship, default status. |
| Unit/Models/StructureVersionTest.php | 10 | 3 | Declaration 2, Low value 1 | Default status, relationship, the negative of the frozen guard. |
| Unit/Models/VaultDefaultTemplateTest.php | 2 | 2 | Declaration | Relationship and defaults. |
| Unit/Models/ReminderTest.php | 3 | 2 | Declaration | Soft delete, route key. |
| Unit/Models/FileTest.php | 4 | 2 | Declaration | Route key, factory attributes. |
| Unit/Models/ValueListTest.php | 4 | 2 | Declaration 1, Low value 1 | Soft delete, plain name. |
| Unit/Models/LifeEventTypeTest.php | 3 | 2 | Declaration 1, Low value 1 | Soft delete, plain name. |
| Unit/Models/FieldValueRowTest.php | 3 | 2 | Low value | One test queries the table itself; the other tests Laravel `withoutTouchingOn`. |
| Unit/Models/FieldValueTest.php | 9 | 2 | Declaration 1, Low value 1 | Relationship; framework `withoutTouchingOn`. |
| Unit/Models/NotificationTest.php | 6 | 2 | Declaration 1, Low value 1 | Unique index through `insertOrIgnore`; a second config variant of the prune window. |
| Unit/Models/Concerns/HasUuidsTest.php | 4 | 2 | Duplicate 1, Low value 1 | `it_names_the_uuid_as_the_column_to_fill_rather_than_the_key` is asserted for every model, `File` included, by `every_model_that_uses_it_keeps_a_number_for_a_key_and_has_a_uuid_column` in the same file. |
| Unit/Models/AccountTest.php | 36 | 2 | Declaration 1, Low value 1 | Defaults; overlapping readable address case. |
| Unit/Models/MarketplaceListingVersionTest.php | 4 | 1 | Declaration | Default status. |
| Unit/Models/AdministratorTest.php | 1 | 1 | Declaration | Null relationship. |
| Unit/Models/SecurityKeyTest.php | 6 | 1 | Declaration | FK cascade. |
| Unit/Models/LifeEventCategoryTest.php | 6 | 1 | Declaration | Soft delete. |
| Unit/Models/NotificationChannelTest.php | 12 | 1 | Declaration | `the_disabled_state_records_why_the_product_switched_it_off` writes three attributes and reads them back. |
| Unit/Models/EmailSentTest.php | 7 | 1 | Low value | Third variant of the precedence branch (see Patterns). |

Method names to remove, Models.

- Unit/Models/StructureOwnershipTest.php (delete the file): a_template_and_a_life_event_type_both_own_a_structure, both_kinds_publish_through_the_same_action, both_kinds_refuse_to_publish_a_version_with_no_page, editing_a_published_life_event_type_structure_makes_a_draft, publishing_a_later_version_leaves_the_earlier_one_where_it_is, a_structure_belonging_to_another_account_is_not_found, an_owner_cannot_hold_two_structures
- Unit/Models/ContactTest.php: it_holds_the_private_links_opened_onto_its_page, it_gets_an_unguessable_identifier_and_keeps_a_number_for_a_key, it_looks_up_the_other_ways_a_name_is_written_by_contact_rather_than_reading_every_one, it_is_deleted_softly, it_does_not_let_the_two_ordering_keys_be_mass_assigned, it_carries_no_card_until_a_device_writes_one, it_keeps_the_card_a_device_sent_and_the_name_that_device_chose, it_rebuilds_the_two_ordering_keys_when_the_name_changes, it_writes_the_given_name_first_when_the_contact_asks_for_no_order, it_keeps_a_parent_written_with_a_type_since_renamed_or_withdrawn, it_reads_no_parent_from_a_type_merely_named_like_one, the_me_contact_gets_an_address_like_anybody_else, a_partial_contact_known_by_one_name_still_gets_an_address, it_builds_one_initial_when_there_is_only_one_word_to_take_it_from
- Unit/Models/TemplateTest.php: it_describes_the_kind_of_record_through_its_structure, it_has_no_kind_of_record_when_it_describes_a_contact, it_has_many_versions, it_points_at_the_version_it_offers, it_offers_nothing_while_only_drafts_exist, it_is_reached_by_its_slug, it_is_given_an_unguessable_identifier, it_reads_by_the_name_it_was_given
- Unit/Models/UserTest.php: it_is_the_only_user_of_its_account, it_hides_the_password_when_serialized, it_falls_back_to_the_time_zone_of_the_instance_and_to_the_given_name_first, it_has_no_password_change_date_when_the_password_was_never_set, it_soft_deletes, it_authenticates_against_the_password_column, it_knows_whether_the_email_address_was_confirmed, it_calls_an_account_created_through_a_provider_passwordless, it_lets_an_ability_the_user_holds_through
- Unit/Models/RelationshipTest.php: it_is_addressed_by_its_uuid, it_is_soft_deleted, it_keeps_several_relationships_between_the_same_pair, it_does_not_resolve_a_relationship_of_another_account, it_resolves_a_relationship_of_the_account_asking, it_does_not_resolve_through_a_vault_it_is_not_in, it_answers_twice_for_a_pair_linked_twice, it_survives_a_contact_being_soft_deleted
- Unit/Models/ContactMergeTest.php: it_is_addressed_by_its_uuid, it_does_not_resolve_a_merge_of_another_account, it_resolves_a_merge_of_the_account_asking, it_does_not_resolve_through_a_vault_it_is_not_in
- Unit/Models/VaultTest.php: it_gets_an_unguessable_identifier_and_keeps_a_number_for_a_key, it_holds_no_contact_and_no_visit_to_begin_with, it_is_deleted_softly, it_keeps_the_alphabet_a_name_is_written_in, it_gives_an_address_to_a_name_that_leaves_nothing_readable
- Unit/Models/EntityTypeTest.php: it_gets_an_unguessable_identifier_and_keeps_a_number_for_a_key, it_is_found_in_a_url_by_its_slug, it_keeps_a_singular_and_a_plural_name_of_its_own, it_is_active_and_holds_nothing_to_begin_with
- Unit/Models/FieldTest.php: it_stores_nothing_for_a_layout_type, it_knows_any_date_can_drive_a_reminder, it_holds_the_fields_inside_a_repeater, it_refuses_two_fields_with_the_same_key_in_one_section, it_reads_which_end_a_new_repetition_is_added_at, it_says_nothing_about_the_end_until_somebody_does
- Unit/Models/LogTest.php: it_records_what_a_field_changed_from_and_to, it_records_where_the_action_came_from, it_comes_from_the_web_unless_something_says_otherwise, it_goes_when_the_account_it_belongs_to_goes
- Unit/Models/MarketplaceInstallationTest.php (delete the file): all 4 tests
- Unit/Models/ShareLinkVisitTest.php (delete the file): all 3 tests
- Unit/Models/ShareLinkTest.php: it_has_many_visits, it_shows_nothing_sensitive_until_it_is_asked_to, it_is_addressed_by_its_uuid_and_never_by_its_token
- Unit/Models/SectionTest.php: it_reads_by_the_name_it_was_given, it_defaults_to_a_glyph_it_offers, it_is_reached_by_its_slug, it_is_given_an_unguessable_identifier, it_offers_thirty_seven_glyphs
- Unit/Models/StructureVersionSectionTest.php: it_shows_a_title_of_its_own_only_when_the_structure_version_asks_for_one, it_starts_open_unless_the_structure_version_says_otherwise, it_refuses_to_place_the_same_section_twice_on_one_structure_version
- Unit/Models/StructureTest.php: it_says_which_kind_of_record_it_describes, it_is_addressed_by_an_unguessable_identifier, it_reads_an_owner_that_is_no_longer_there_as_nothing
- Unit/Models/EntityTest.php: it_gets_an_unguessable_identifier_and_keeps_a_number_for_a_key, it_is_deleted_softly, it_does_not_let_the_two_ordering_keys_be_mass_assigned, it_rebuilds_the_two_ordering_keys_when_the_name_changes, it_builds_one_initial_when_there_is_only_one_word_to_take_it_from
- Unit/Models/RelationshipTypeTest.php: it_gets_an_unguessable_identifier, an_ordinary_type_carries_no_behaviour, it_is_deleted_softly, nothing_is_written_with_a_type_nobody_has_used
- Unit/Models/MarketplaceListingTest.php: it_is_reached_by_its_slug, it_is_given_a_uuid_the_api_is_shown, it_is_private_until_somebody_submits_it
- Unit/Models/MarketplaceCategoryTest.php: it_is_reached_by_its_key, it_takes_new_publications_until_it_is_deactivated
- Unit/Models/ValueListItemTest.php: it_reads_by_the_label_the_user_gave_it, it_is_offered_and_used_by_nothing_to_begin_with, it_is_deleted_softly
- Unit/Models/RelationshipCategoryTest.php: it_gets_an_unguessable_identifier, it_is_deleted_softly
- Unit/Models/InstanceSettingTest.php: it_encrypts_the_value_and_reads_it_back (see Open questions), it_holds_one_row_per_setting
- Unit/Models/SectionVersionTest.php: it_knows_the_pages_it_sits_on, it_starts_as_an_editable_draft
- Unit/Models/StructureVersionTest.php: it_starts_as_an_editable_draft, it_places_a_section_by_its_version_rather_than_by_the_section_itself, it_lets_a_draft_be_deleted
- Unit/Models/VaultDefaultTemplateTest.php (delete the file): both tests
- Unit/Models/ReminderTest.php: it_is_only_ever_removed_softly, it_carries_a_public_identifier_that_is_never_its_key
- Unit/Models/FileTest.php: it_is_looked_up_by_its_uuid, it_reads_the_sizes_of_a_picture
- Unit/Models/ValueListTest.php: it_reads_by_the_name_the_user_gave_it, it_is_deleted_softly
- Unit/Models/LifeEventTypeTest.php: it_keeps_the_name_somebody_gave_it, it_can_be_deleted_without_the_row_going
- Unit/Models/FieldValueRowTest.php: it_reads_the_same_way_twice_when_two_repetitions_hold_one_place, it_does_not_save_the_record_while_its_kind_is_set_aside
- Unit/Models/FieldValueTest.php: it_holds_at_most_one_reminder_rule, it_does_not_save_the_record_while_its_kind_is_set_aside
- Unit/Models/NotificationTest.php: it_tells_one_person_about_one_thing_once_per_channel, the_retention_period_is_a_setting_of_the_instance
- Unit/Models/Concerns/HasUuidsTest.php: it_names_the_uuid_as_the_column_to_fill_rather_than_the_key, it_gives_every_record_a_uuid_of_its_own
- Unit/Models/AccountTest.php: it_has_no_purchase_and_no_onboarding_date_by_default, a_new_account_builds_readable_addresses
- Unit/Models/MarketplaceListingVersionTest.php: it_waits_on_a_decision_when_it_is_created
- Unit/Models/AdministratorTest.php (delete the file): it_was_granted_by_nobody_when_the_console_designated_them
- Unit/Models/SecurityKeyTest.php: it_is_deleted_with_its_user
- Unit/Models/LifeEventCategoryTest.php: it_is_soft_deleted
- Unit/Models/NotificationChannelTest.php: the_disabled_state_records_why_the_product_switched_it_off
- Unit/Models/EmailSentTest.php: it_reads_as_complained_once_the_recipient_marked_it_as_spam

Enums.

| File | Tests | Remove | Bucket(s) | Why |
| --- | --- | --- | --- | --- |
| Unit/Enums/AccessEndReasonEnumTest.php | 1 | 1 | Declaration | Lists the case values. |
| Unit/Enums/AccessKindEnumTest.php | 1 | 1 | Declaration | Non empty label and the case values. |
| Unit/Enums/AdministratorActionEnumTest.php | 2 | 2 | Declaration | Non empty description; `translationKeys` equals the description. `TranslatableEnumsTest::every_case_gives_at_least_one_string_to_translate` already covers every case of every enum. |
| Unit/Enums/ApiKeyScopeEnumTest.php | 2 | 2 | Declaration | Values and `translationKeys`. |
| Unit/Enums/EmailDeliveryStatusEnumTest.php | 2 | 2 | Declaration | Case values and a case count of four. |
| Unit/Enums/McpCallResultEnumTest.php | 2 | 2 | Declaration | Labels, values, a `match` of tone. |
| Unit/Enums/McpConnectionTypeEnumTest.php | 1 | 1 | Declaration | Labels and values. |
| Unit/Enums/NotificationDeliveryModeEnumTest.php | 1 | 1 | Declaration | `values()`. |
| Unit/Enums/NotificationTypeEnumTest.php | 1 | 1 | Declaration | `values()`. |
| Unit/Enums/RelationshipBehaviorEnumTest.php | 1 | 1 | Declaration | Case values. |
| Unit/Enums/RelationshipCategoryEnumTest.php | 2 | 2 | Declaration | Case values, labels. |
| Unit/Enums/RelationshipStatusEnumTest.php | 3 | 3 | Declaration | Values, labels, translation keys. |
| Unit/Enums/ReminderInactiveReasonEnumTest.php | 1 | 1 | Declaration | Case values. |
| Unit/Enums/RepetitionOrderEnumTest.php | 4 | 4 | Declaration | Values, labels, a boolean `match`, translation keys. |
| Unit/Enums/SnapshotReasonEnumTest.php | 2 | 2 | Declaration | `isAutomatic()` is a `match` restated case by case. |
| Unit/Enums/SsoProviderEnumTest.php | 4 | 4 | Declaration | Values, brand names, driver class strings. |
| Unit/Enums/UserActionEnumTest.php | 5 | 5 | Declaration | Asserts English wording. `Unit/Models/LogTest::it_describes_the_action_with_its_parameters` already proves the description path. |
| Unit/Enums/FieldTypeEnumTest.php | 17 | 15 | Declaration | 15 of 17 restate a `match` (column per type, which type has an input, which type uses a list). Keep the two invariant loops. A single test that every non null `valueColumn()` exists in `field_values` would add what these lack. |
| Unit/Enums/NotificationChannelEnumTest.php | 15 | 11 | Declaration 10, Low value 1 | Labels, phrases, sentences, capability flags, `values()` restated. Keep the four `isConfiguredOnTheInstance` tests, which read config. |
| Unit/Enums/DefaultFieldEnumTest.php | 6 | 5 | Declaration | Restates the `valueList()` and `type()` mapping. Keep the invariant "a field whose type asks for a list names one". |
| Unit/Enums/RelationshipTypeEnumTest.php | 8 | 5 | Declaration 4, Low value 1 | Count of ten, role names, category per type. Keep the two behaviour sets and the translation key dedupe. |
| Unit/Enums/InstanceSettingEnumTest.php | 9 | 4 | Declaration 3, Low value 1 | Group mapping, two warning texts, description ends with a period. Keep the config key, env variable and uniqueness cross checks. |
| Unit/Enums/OfferKindEnumTest.php | 4 | 3 | Declaration | `renews()`, `billingInterval()`, non empty copy. |
| Unit/Enums/ReminderCadenceEnumTest.php | 4 | 3 | Declaration | `recurring()`, `values()`, label. |
| Unit/Enums/VaultColourEnumTest.php | 6 | 3 | Declaration 2, Cosmetic 1 | `it_gives_every_colour_a_tint_naming_its_own_border` asserts class substrings (`border-`, `dark:text-`), which `testing.md` forbids. The two WCAG contrast tests are worth keeping. |
| Unit/Enums/NumberFormatEnumTest.php | 6 | 3 | Declaration 1, Low value 2 | Label; a larger number and the example are repeats of the separator test. |
| Unit/Enums/ShareLinkExpiryEnumTest.php | 4 | 2 | Declaration | Help text substring, label and `translationKeys`. |
| Unit/Enums/DateFormatEnumTest.php | 5 | 2 | Declaration 1, Low value 1 | Label; example contains "21". |
| Unit/Enums/TimeFormatEnumTest.php | 4 | 2 | Declaration 1, Low value 1 | Label; example restates the two formats. |
| Unit/Enums/ContactListColumnSourceEnumTest.php | 3 | 1 | Low value | `assertCount(7, shared())` breaks every time a column is added. |
| Unit/Enums/McpOperationEnumTest.php | 3 | 1 | Declaration | Label and values. |
| Unit/Enums/NameOrderEnumTest.php | 4 | 1 | Declaration | Label. |
| Unit/Enums/ReminderOccurrenceCancelledReasonEnumTest.php | 4 | 1 | Declaration | `values()`. |
| Unit/Enums/SortDirectionEnumTest.php | 3 | 1 | Declaration | Values. |

Method names to remove, Enums.

- Delete these files entirely (35 tests): AccessEndReasonEnumTest, AccessKindEnumTest, AdministratorActionEnumTest, ApiKeyScopeEnumTest, EmailDeliveryStatusEnumTest, McpCallResultEnumTest, McpConnectionTypeEnumTest, NotificationDeliveryModeEnumTest, NotificationTypeEnumTest, RelationshipBehaviorEnumTest, RelationshipCategoryEnumTest, RelationshipStatusEnumTest, ReminderInactiveReasonEnumTest, RepetitionOrderEnumTest, SnapshotReasonEnumTest, SsoProviderEnumTest, UserActionEnumTest
- Unit/Enums/FieldTypeEnumTest.php: remove everything except it_never_names_a_column_for_a_type_that_stores_nothing and it_never_offers_a_control_for_a_type_that_stores_nothing
- Unit/Enums/NotificationChannelEnumTest.php: it_names_every_channel, it_names_each_channel_inside_a_sentence, it_says_in_a_full_sentence_where_something_went, it_says_which_channels_the_account_has_to_supply_a_destination_for, only_telegram_is_handed_its_own_destination, a_text_message_asks_the_account_for_no_credential_because_the_provider_is_the_instances, only_the_text_message_accepts_a_wrong_destination_without_complaining, a_channel_needing_no_integration_is_unaffected_by_one_being_absent, it_says_how_long_a_message_may_be, it_names_the_laravel_channel_that_carries_it, it_lists_its_values
- Unit/Enums/DefaultFieldEnumTest.php: it_names_the_list_an_email_a_phone_and_an_address_take_their_label_from, it_names_the_list_a_choice_field_offers, it_names_no_list_for_a_field_that_reads_from_none, it_says_which_fields_identify_a_record, it_gives_an_email_and_a_phone_their_own_type
- Unit/Enums/RelationshipTypeEnumTest.php: it_supplies_ten_types, a_directed_type_reads_differently_from_each_side, a_symmetric_type_reads_the_same_from_both_sides, every_type_names_the_category_it_is_filed_under, nothing_is_filed_under_other_to_begin_with
- Unit/Enums/InstanceSettingEnumTest.php: it_describes_every_setting, it_fixes_only_what_describes_the_deployment, it_warns_when_a_telegram_credential_is_replaced, it_warns_when_registration_is_switched_on
- Unit/Enums/OfferKindEnumTest.php: it_names_describes_and_sells_every_kind, it_renews_unless_it_is_bought_once, it_knows_how_the_provider_has_to_bill_each_kind
- Unit/Enums/ReminderCadenceEnumTest.php: it_offers_only_the_recurring_cadences_once_somebody_says_it_comes_back, it_lists_its_values, it_carries_a_translatable_label_for_every_case
- Unit/Enums/VaultColourEnumTest.php: it_names_every_colour, it_offers_eight_colours_beside_the_neutral_default, it_gives_every_colour_a_tint_naming_its_own_border
- Unit/Enums/NumberFormatEnumTest.php: it_names_every_format, it_separates_the_thousands_of_a_larger_number_too, it_shows_a_number_past_the_thousand_and_past_the_point
- Unit/Enums/ShareLinkExpiryEnumTest.php: it_warns_about_the_link_somebody_has_to_remember_to_revoke, every_case_carries_a_name_and_a_sentence_for_the_screen
- Unit/Enums/DateFormatEnumTest.php: it_names_every_format, it_shows_a_day_the_month_could_not_be
- Unit/Enums/TimeFormatEnumTest.php: it_names_every_format, it_shows_the_same_afternoon_hour_written_both_ways
- Unit/Enums/ContactListColumnSourceEnumTest.php: it_leaves_template_fields_out_of_the_shared_columns
- Unit/Enums/McpOperationEnumTest.php: it_names_every_operation
- Unit/Enums/NameOrderEnumTest.php: it_names_every_order
- Unit/Enums/ReminderOccurrenceCancelledReasonEnumTest.php: it_lists_its_values
- Unit/Enums/SortDirectionEnumTest.php: it_runs_one_way_or_the_other

Extend `Unit/Enums/TranslatableEnumsTest.php` by one assertion before deleting the label tests: for every `Translatable` enum, every case has a non empty `translationKeys()` (it already does) and, where the enum has `label()`, `label()` is non empty. That one reflection test replaces roughly 25 of the removals above.

Helpers.

| File | Tests | Remove | Bucket(s) | Why |
| --- | --- | --- | --- | --- |
| Unit/Helpers/TimezoneCatalogueTest.php | 16 | 6 | Declaration 2, Low value 4 | `assertCount(419, all())` breaks on a tzdata update; the position test restates data; the rest assert "not null" or "same count". |
| Unit/Helpers/StructureFileReaderTest.php | 54 | 5 | Low value 4, Duplicate 1 | Primitive checks owned by `StructureFileValuesTest`: key (`it_reads_a_key_and_refuses_anything_that_is_not_one`), number (`it_refuses_a_number_outside_its_range_or_written_as_words`), length (`it_refuses_words_that_are_not_words_and_words_that_are_too_many`), unknown name (`it_refuses_a_name_this_version_does_not_know_and_says_where`), locale (`it_reads_a_locale_this_instance_ships_and_quietly_forgets_any_other`). |
| Unit/Helpers/LocaleCollationTest.php | 19 | 4 | Low value | Collator caching, determinism, two restatements of the fold tests. |
| Unit/Helpers/FieldValueRulesTest.php | 23 | 3 | Low value | Third variants of retired label and of the label rule for an address, and the required file twin of the optional file test. |
| Unit/Helpers/FieldSettingsCatalogueTest.php | 12 | 3 | Declaration 2, Low value 1 | Fillable list, a duplicate of `FieldTypeEnum::usesValueList`, and "every type answers an array". |
| Unit/Helpers/PageSizeTest.php | 12 | 2 | Low value | Laravel paginate and `withQueryString` behaviour. |
| Unit/Helpers/SlugBuilderTest.php | 17 | 2 | Low value | Separator joined slug (the counting variant is kept) and the readable numbering twin of `it_counts_up_until_nothing_answers_to_it`. |
| Unit/Helpers/FieldValueFormatterTest.php | 27 | 2 | Low value | Long text, and the retired address label (same branch as the retired phone label). |
| Unit/Helpers/AvailableLocalesTest.php | 7 | 1 | Low value | `all()` is `codes()` plus `details()`, both proven. |
| Unit/Helpers/CountedPhraseWordingsTest.php | 7 | 1 | Low value | Restates the previous Polish test. |
| Unit/Helpers/MarketplacePayloadTest.php | 10 | 1 | Low value | "Same payload hashes the same" tests `hash()`. |
| Unit/Helpers/QuietHoursTest.php | 11 | 1 | Low value | Noon is not quiet; the boundaries are tested separately. |
| Unit/Helpers/ReminderScheduleTest.php | 23 | 1 | Low value | `it_converts_the_local_hour_of_the_rule_into_an_instant` gives 09:00, which is the factory default, so it equals `it_enumerates_one_deadline_per_cycle_on_the_day_itself`. |
| Unit/Helpers/ReservedAddressWordsTest.php | 4 | 1 | Low value | Asserts three literals that the loop test already walks. |
| Unit/Helpers/SsoProviderAvailabilityTest.php | 6 | 1 | Low value | A variant of the absent package case. |
| Unit/Helpers/StripeCatalogueTest.php | 18 | 1 | Low value | Also asserted inside the failure test. |
| Unit/Helpers/StructureFileValuesTest.php | 20 | 1 | Low value | Same limit as the `nullableString` test. |

Method names to remove, Helpers.

- Unit/Helpers/TimezoneCatalogueTest.php: it_carries_every_canonical_zone, it_carries_the_position_of_a_zone, it_carries_the_zone_a_new_account_starts_on, it_does_not_know_a_zone_that_does_not_exist, it_indexes_every_zone_it_carries, it_finds_a_zone_for_a_click_on_open_ocean
- Unit/Helpers/StructureFileReaderTest.php: it_refuses_a_name_this_version_does_not_know, it_refuses_a_label_longer_than_the_column_holds, it_refuses_a_key_that_is_not_a_key, it_refuses_a_number_written_as_words, it_drops_a_locale_the_application_does_not_ship
- Unit/Helpers/LocaleCollationTest.php: it_reuses_one_collator_for_a_language, it_orders_a_language_it_knows_nothing_about_the_same_way_every_time, it_folds_a_term_and_a_name_to_the_same_text_where_the_language_folds, it_does_not_fold_a_term_and_a_name_together_where_the_language_keeps_them_apart
- Unit/Helpers/FieldValueRulesTest.php: it_refuses_a_label_nobody_offers_any_more, it_asks_for_the_label_of_an_address_naming_a_list, a_required_file_field_blocks_saving_and_says_why_while_file_upload_is_off_for_the_account
- Unit/Helpers/FieldSettingsCatalogueTest.php: every_kind_of_field_answers, a_field_can_be_given_every_setting_its_kind_takes, only_a_field_reading_from_a_list_offers_choices
- Unit/Helpers/PageSizeTest.php: it_pages_a_query_at_the_size_asked, it_keeps_the_query_string_in_its_links
- Unit/Helpers/SlugBuilderTest.php: it_joins_the_words_with_whatever_it_is_told_to, it_numbers_a_readable_slug_something_already_answers_to
- Unit/Helpers/FieldValueFormatterTest.php: it_reads_a_long_text_back, it_still_reads_an_address_label_nobody_offers_any_more
- Unit/Helpers/AvailableLocalesTest.php: it_hands_every_language_with_what_is_known_about_it
- Unit/Helpers/CountedPhraseWordingsTest.php: polish_reads_a_third_wording_that_two_forms_could_never_reach
- Unit/Helpers/MarketplacePayloadTest.php: it_hashes_the_same_payload_the_same_way_every_time
- Unit/Helpers/QuietHoursTest.php: it_lets_the_middle_of_the_day_through
- Unit/Helpers/ReminderScheduleTest.php: it_converts_the_local_hour_of_the_rule_into_an_instant
- Unit/Helpers/ReservedAddressWordsTest.php: it_reads_the_routes_rather_than_a_copy_of_them
- Unit/Helpers/SsoProviderAvailabilityTest.php: it_offers_nothing_when_no_driver_is_installed_at_all
- Unit/Helpers/StripeCatalogueTest.php: it_is_available_once_it_has_been_read
- Unit/Helpers/StructureFileValuesTest.php: it_refuses_a_reference_longer_than_what_can_be_stored

### Patterns

- **Identity and housekeeping tests on every model** (Declaration, about 45 in Models). Uuid regex or `assertNotNull($x->uuid)`, `getRouteKeyName()`, `assertSoftDeleted`, factory defaults and fillable lists. Examples: `RelationshipCategoryTest::it_gets_an_unguessable_identifier`, `ReminderTest::it_is_only_ever_removed_softly`, `ContactTest::it_does_not_let_the_two_ordering_keys_be_mass_assigned`. `HasUuidsTest::every_model_that_uses_it_keeps_a_number_for_a_key_and_has_a_uuid_column` already walks every model through a data provider, so the per model copies prove nothing more. The same approach would fit a `RouteKeyTest` over models, but only if the route key is considered a contract at all.
- **Plain attribute readback** (Low value, 9 in Models). `it_reads_by_the_name_the_user_gave_it` appears on `ValueList`, `Section`, `Template` and `ValueListItem`. The translation key fallback beside it is the real logic and is kept. The `newest first` relationship ordering test is repeated on `Structure`, `Template`, `Section`, `MarketplaceListing`; each is a different relationship, so they stay, but they could share one data provider.
- **The frozen version guard is written five times**: `SectionVersion`, `StructureVersion`, `StructureVersionPage`, `StructureVersionSection`, `Field` and `MarketplaceListingVersion` each test refuse add, refuse change, refuse delete. These are real model events and are kept. They would compress into one data provider over the models that raise `PublishedVersionIsFrozen`.
- **Mirrored tests for symmetric scopes** (Low value, 2 removed, about 8 more collapsible). `ContactTest` tests `childrenOf` and `parentsOf` with the same seven scenarios. `typesBehavingAs()` is shared, so only the direction and the vault scope are scope specific.
- **Enum tests that restate a `match`** (Declaration, 89). Examples: `SsoProviderEnumTest::it_names_every_provider_the_way_its_own_company_writes_it`, `RelationshipStatusEnumTest::it_reads_each_state_back_to_the_user`, `NotificationChannelEnumTest::it_names_each_channel_inside_a_sentence`. Seventeen of the 37 enum files are removable in full for this reason.
- **Label and translation conformance copied per enum** (11 enums). `it_names_every_format`, `it_names_every_order`, `every_case_offers_its_name_for_translation`, `it_carries_a_translatable_label_for_every_case`. `TranslatableEnumsTest` already walks `app/Enums` by reflection and covers `translationKeys()`.
- **Tests that exercise the framework** (Low value, 6). `withoutTouchingOn` (twice), `getAuthPassword`, `hasVerifiedEmail`, `paginate` and `withQueryString`, SHA of a payload.
- **Cross layer duplicates of the action layer.** `StructureOwnershipTest` and the two `resolveRouteBinding` tests per model that already use a trait tested once.
- **Variants of a branch already proven** (Low value). `EmailSentTest` has three tests for `Complained` and one more for `Bounced` plus `Delivered`; `FieldValueRulesTest` has choice, label, retired label and address label variants of the same rule; `LocaleCollationTest` folds the same word four ways.
- **Helpers that sit next to their only caller.** `LocaleResolution` is also driven through `Feature/Localization/WebLocaleResolutionTest.php` and `ApiLocaleResolutionTest.php`, which repeat the priority chain (session beats account beats device beats browser beats app locale). The helper test owns the chain; see Open questions.

### Cost hot spots

- `Unit/Helpers/StructureFileReaderTest.php` declares `RefreshDatabase` for 54 tests; only 5 (`it_reads_a_file_this_application_wrote`, `it_reads_a_section_file`, `it_reads_the_lists_of_choices_by_the_reference_the_fields_name`, `it_keeps_the_repeater_a_field_sits_in`, `it_reads_back_a_life_event_type_whose_fields_carry_settings`) build models. The other 49 work on arrays. Splitting the five into their own class removes about 49 transactions per run, which is where MySQL and PostgreSQL spend the extra time.
- `Unit/Helpers/StripeCatalogueTest.php` (18 tests) and `StripeCharge`/`FlagState` are the same case in smaller form: `RefreshDatabase` on the whole class, database needed by about 4 of 18.
- `Unit/Helpers/ShareLinkSummaryTest.php`: `contactWithThreeSections()` creates about 20 rows (user, vault, template, structure version, page, three sections with versions and placements, contact) and runs in 9 tests. Build it once per test class or reduce it to one section; `endsLine` tests do not need a contact at all.
- `Unit/Helpers/PageSizeTest.php`: four tests create 30 `Account` rows to prove a page size of 10. Three rows with a page size of 1 prove the same.
- `Unit/Helpers/ContactListCellsTest.php`, `FieldValueFormatterTest.php`, `ValueListSnapshotTest.php`, `MarketplacePayloadTest.php`: model graphs built inline per test, each with a user and an account. Acceptable at this size, but `MarketplacePayloadTest` repeats the same section, version and field block three times to assert three keys of one payload.
- `Unit/Models/ContactTest.php`: 81 tests, each `Contact::factory()` also creates a name representation. The 14 relationship scope tests create 2 to 4 contacts and relationship rows each. The 14 removals above shed about 17 percent of the class.
- `Unit/Models/SectionTest::it_offers_no_glyph_the_icon_component_cannot_draw` renders a Blade component 37 times in one test. It is cross checking a real defect class, so keep it, but it is a View concern in a Models folder.
- `Unit/Models/McpCallTest` runs `model:prune` through Artisan twice. It could call `McpCall::query()->prunable()` the way `NotificationTest` does for its model.
- `Unit/Enums/NotificationChannelEnumTest::a_text_message_is_configured_only_when_the_server_has_a_provider` calls `refreshApplication()` inside a loop, three times. Use a data provider so each rebuild is its own test, or reset config instead of rebuilding the application.
- `Unit/Enums/InstanceSettingEnumTest::it_names_an_environment_variable_the_matching_configuration_file_actually_reads` reads one config file per setting in a loop; the whole class needs the application container. It is cheap, but the whole Enums folder extends `Tests\TestCase`, which boots the framework for tests that only call `->label()`. A plain `PHPUnit\Framework\TestCase` would do for pure enums.
- `Unit/Helpers/AvailableLocalesTest.php` writes files to disk in `setUp()` for every test. Only the `File::put` calls of the tests that need them are required.
- `Unit/Helpers/MaterializedDatabaseEngineTest.php` skips on the wrong driver in 7 of 8 tests on every run (one engine each); not a cost problem, but those skips are noise on CI.

### Proposed guidelines

- Do not test that a model has a uuid, a route key, soft deletes, factory defaults or a fillable list. One data provider over `app/Models` per attribute, or nothing.
- Do not test an enum `match` case by case. Test an enum only when a method has logic (parsing, ordering, a boundary, a config read), and assert invariants across all cases with reflection.
- One reflection test covers "every translatable enum case has a non empty label and translation keys". Do not add a labels test to an enum file.
- A test that asserts a class string, even through a helper method that returns one (`VaultColourEnum::tint()`), is Cosmetic. Assert contrast, or the value behind the class, instead.
- A model that uses `ResolvesModelInAccount` or `ResolvesModelInVault` gets no resolve test of its own.
- Do not put action tests in `tests/Unit/Models`. If a test needs `new SomeAction(...)->execute()` it belongs in `tests/Unit/Actions`.
- Do not write two tests for one branch because one asserts a result and another asserts its mirror image (`childrenOf` and `parentsOf`, retired phone label and retired address label). One data provider with both cases.
- Do not test framework behaviour: `paginate`, `withQueryString`, `withoutTouchingOn`, `hasVerifiedEmail`, `getAuthPassword`, `hash()`.
- A test class declares `RefreshDatabase` only when most of its tests touch the database. Move the few that do into their own class.
- A fixture builder used by every test of a class (`contactWithThreeSections`) is built once per class or reduced to what the assertions read.
- Do not assert the size of a catalogue that follows external data (`assertCount(419, TimezoneCatalogue::all())`, `assertCount(37, Section::ICONS)`).

### Open questions

- `InstanceSettingTest::it_encrypts_the_value_and_reads_it_back`. The skill says never test a cast, so I marked it Declaration. It does prove a secret (`MailPassword`) is not stored in clear text, which is a security property and not a mapping. Decide whether the rule on casts should carry an exception for `encrypted`. I left it in the removal list; move it to Keep if you want the security assertion.
- `Feature/Localization/WebLocaleResolutionTest.php` and `ApiLocaleResolutionTest.php` (not in my scope, I read only their test names) repeat the priority chain that `Unit/Helpers/LocaleResolutionTest.php` proves (`on_a_screen_a_choice_made_in_this_session_beats_the_account` and the other `on_a_screen_*` and `on_the_api_*` tests, 9 in total). Under the ownership table the helper owns the chain, so the Feature copies are the Duplicates, not these. I did not open their bodies, so I did not confirm they assert nothing more than the middleware calls the helper. The auditor of `Feature/Localization` should check that.
- `FieldTest::it_stores_nothing_for_a_layout_type` and `::it_knows_any_date_can_drive_a_reminder` are Duplicates of tests in `FieldTypeEnumTest` that I have also marked for removal as Declaration (15 of 17). I kept `it_never_names_a_column...` and `it_never_offers_a_control...` there. If the whole `FieldTypeEnumTest` is retired in favour of a reflection test, the Field copies are removed regardless.
- `StructureOwnershipTest::both_kinds_refuse_to_publish_a_version_with_no_page` runs the life event type path of `PublishStructureVersion`. `PublishStructureVersionTest::it_refuses_a_version_with_no_page_at_all` uses a template. I did not find a life event type specific branch in the action (`PublishStructureVersionTest` has two life event type cases at `it_says_a_section_it_published_went_out_with_the_life_event_type` and `it_writes_the_publication_of_custom_fields_to_the_logs_as_the_life_event_type_it_is`), so I judged the path shared. I did not read `PublishStructureVersion` itself.
- `StructureFileReaderTest` and `StructureFileValuesTest`: the reader tests 4 primitive refusals the values class already owns. The reader calls the values class for each, but I did not read `StructureFileReader` end to end to confirm that no field goes around it. If one does, that field's refusal test is a Keep.
- `ContactTest::it_keeps_a_parent_written_with_a_type_since_renamed_or_withdrawn` and `it_reads_no_parent_from_a_type_merely_named_like_one` are marked Low value because `childrenOf` and `parentsOf` both call `typesBehavingAs()` (read in `app/Models/Contact.php`). The child side tests are kept.
- I did not check Models and Helpers tests against Feature and Action tests beyond the cases named above. For example, `Unit/Models/ContactTest` search tests may overlap `MaterializedDatabaseEngineTest`; they cover different layers (toSearchableArray against engine query), so I kept both.
- Timings are not measured. The cost notes come from reading the tests and the CPU table in `docs/TEST_SUITE_AUDIT.md` (Models 14 s, Helpers 8 s locally), not from a run. I was told not to run the suite.

> Checked by the coordinator: `StructureFileReaderTest` holds 54 tests under `RefreshDatabase`, and factory calls appear in a handful of them only (lines 40 to 90, 375, and 777 to 844); moving the database tests into a file of their own lets the other tests run without a transaction. `PageSizeTest` creates 30 accounts at lines 89 and 101. One disagreement: keep `InstanceSettingTest::it_encrypts_the_value_and_reads_it_back`. It reads the raw column and asserts the mail password is not stored in clear text, which is a security property, not a restated cast.

## Unit (Notifications, Jobs, Listeners, Policies, Console, Http and the rest)

Files read in full: all 26 files in tests/Unit/Notifications, 22 in Jobs, 17 in Listeners, 13 in Policies, 10 in Console/Commands, 9 in Http/Dav, 2 in Translation, 2 in Providers, 2 in Migrations, 1 each in Seeders, Rules and Features, and the three top level files. I also read the classes under test where ownership was in doubt (LocalizeCommand, the jobs, the listeners) and opened the owning tests I cite (Actions/CreateDefaultValueListsTest, CreateDefaultRelationshipTypesTest, CreateDefaultLifeEventCategoriesTest, CreateNotificationPreferenceTest, GenerateReminderOccurrencesTest, NotifyReminderOccurrenceTest, RollBackAccountImportTest, SendNotificationDigestTest). Method counts from `grep -c '#\[Test\]'`: Notifications 145, Jobs 172, Listeners 75, Policies 68, Console 99, Http 106, Translation 18, Providers 26, Migrations 9, Seeders 4, Rules 7, Features 3, top level 6 (ExampleTest has one method without the attribute, counted here as 1), 739 in all. This layer should own: the copy and routing facts of each email (subject, panel, action URL, one conformance check for journal type, channel and queue), what each job decides by itself (not what the action it calls already proves), what each listener does with an event, the three outcomes of each policy ability, and the wire contract of the DAV adapters. Most of this area is sound. The removals are concentrated in conformance tests (50 in Notifications), seed data restated from enums (21 in two jobs), and one command test that rescans the whole codebase 55 times.

### Totals
| Bucket | Tests |
| --- | --- |
| Keep | 584 |
| Duplicate | 21 |
| Cosmetic | 2 |
| Declaration | 96 |
| Low value | 36 |
| Total | 739 |

Declaration removals are partly replaced by new data provider tests (about 5: one for notifications, two for Stripe listeners, one for each DAV backend refusal group), so the net saving is about 150 methods, not 155.

### Findings by file
| File (path relative to tests/) | Tests | Remove | Bucket(s) | Why, citing the owning test when Duplicate |
| --- | --- | --- | --- | --- |
| Unit/ExampleTest.php | 1 | 1 | Low value | Laravel scaffolding, asserts true is true. Delete the file. |
| Unit/TestCaseTest.php | 2 | 2 | Low value | Tests the `assignUserToAccount` helper. A broken helper fails hundreds of tests loudly. |
| Unit/DatabaseViewerTest.php | 4 | 1 | Declaration | `it_is_installed` is `class_exists`. The other three guard that the viewer is off and authenticated outside local, keep. |
| Unit/Notifications (25 files) | 145 | 56 | Declaration 50, Cosmetic 2, Low value 4 | See the list below. Journal type, `via` and queue are one fact each per class. |
| Unit/Jobs/SeedLifeEventCategoriesTest, SeedNotificationPreferenceTest, SeedRelationshipTypesTest, SeedValueListsTest | 4 | 4 | Duplicate | One line wrappers. Owned by Actions/CreateDefaultLifeEventCategoriesTest::it_gives_the_account_the_eight_supplied_categories, CreateNotificationPreferenceTest::it_gives_an_account_the_row_delivery_reads, CreateDefaultRelationshipTypesTest::it_gives_the_account_the_five_categories_in_order, CreateDefaultValueListsTest::it_gives_the_account_every_list_the_product_supplies. The jobs are also run, un-faked, by RollBackAccountImportTest's `accountHolder()` and CreateAccountTest. |
| Unit/Jobs/CreateDefaultTemplateTest.php | 40 | 16 | Duplicate 3, Declaration 9, Low value 4 | Seed data restated from the enums, and repeats of one assertion inside the file. |
| Unit/Jobs/CreateDefaultLifeEventTypesTest.php | 14 | 6 | Duplicate 1, Declaration 3, Low value 2 | Same pattern. |
| Unit/Jobs/LogUserActionTest.php | 15 | 2 | Duplicate 1, Low value 1 | Defaults tested twice, a negative variant of a positive test. |
| Unit/Jobs/DetectDuplicateContactsTest.php | 24 | 1 | Low value | Third negative variant. |
| Unit/Jobs/DeliverDueNotificationsTest.php | 8 | 2 | Duplicate | The action owns both rules. |
| Unit/Jobs/GenerateDueReminderOccurrencesTest.php | 4 | 1 | Duplicate | Idempotence is owned by the action. |
| Unit/Jobs/PurgeOrphanedFilesTest.php | 7 | 1 | Low value | Not an account check, repeats the "used file" case. |
| Unit/Jobs/RecordVaultVisitTest.php | 3 | 1 | Low value | Weaker twin of the visitedAt test. |
| Unit/Listeners/HandleStripe* (7 files) | 41 | 14 | Declaration | `tries` and `backoff` asserted per class (7), event type filter repeated per class (7). |
| Unit/Listeners/CheckDatabaseIsReadyTest.php | 3 | 1 | Declaration | Reads the listener map. |
| Unit/Listeners/RecordEmailBouncedTest, RecordEmailComplainedTest, RecordEmailDeliveredTest, RecordEmailFailedTest | 8 | 4 | Duplicate | Each file's first test dispatches the provider event and sees the column written, which proves the listener is wired. |
| Unit/Policies/ContactPolicyTest.php | 7 | 1 | Low value | `the_user_object_answers_the_same_as_the_gate` tests Laravel's `Authorizable::can`. |
| Unit/Console/Commands/LocalizeCommandTest.php | 55 | 5 | Duplicate 2, Low value 3 | See Cost hot spots for the larger restructure. |
| Unit/Console/Commands/ScreenshotLinkCommandTest.php | 5 | 1 | Duplicate | `it_mints_a_link_that_signs_the_user_in` only asserts success, which `the_link_it_mints_works` asserts and goes further. |
| Unit/Console/Commands/SetupApplicationCommandTest.php | 7 | 1 | Low value | Asserts the storage link step is skipped under the testing environment. |
| Unit/Http/Dav/CalendarBackendTest.php | 10 | 5 | Declaration | Five identical "throws Forbidden" tests. One data provider. |
| Unit/Http/Dav/AddressBookBackendTest.php | 12 | 3 | Declaration 2, Duplicate 1 | Two Forbidden refusals. `it_tells_what_changed_in_the_shape_the_standard_asks_for` only checks array keys, Http/Dav/CollectionChangesTest::it_tells_what_was_added_changed_and_removed_since_a_token owns the content. |
| Unit/Http/Dav/VCardMergerTest.php | 31 | 1 | Low value | `it_writes_the_label_of_an_email_as_the_type_a_card_knows` repeats `it_writes_a_standard_label_as_the_type_every_address_book_reads` for another field type. |
| Unit/Http/Dav/CardDavPluginTest.php | 17 | 1 | Low value | `it_relies_on_the_parser_naming_the_line_it_cannot_read` tests the sabre/vobject library. |
| Unit/Translation/FrenchTypographyTest.php | 7 | 7 | Declaration | Every test reads a value out of `config/localization.php` and compares it with a literal copy of the same value. `monica:localize --check` enforces the real thing against the catalogue. |
| Unit/Translation/CountedPhraseTest.php | 11 | 5 | Declaration 2, Duplicate 1, Low value 2 | See list below. |
| Unit/Providers/AppServiceProviderTest.php | 23 | 2 | Declaration 1, Low value 1 | `hasMacro` check, and a ring letter collation case that tests ICU. |
| Unit/Migrations/BackfillNotificationPreferencesTest.php, BackfillContactNameRepresentationsTest.php | 9 | 9 | Low value | One shot backfills that have run in production. Migrations are not edited once shipped, so nobody introduces a defect here. Delete once every environment has migrated (see Open questions). |
| Unit/Seeders/MarketplaceCategorySeederTest.php | 4 | 1 | Declaration | `it_files_the_categories_at_the_top_level` asserts no parent. |

Method names to remove, grouped by file.

- Unit/ExampleTest.php: `test_that_true_is_true` (delete file).
- Unit/TestCaseTest.php: `it_puts_a_user_in_an_account`, `it_takes_the_user_out_of_the_account_they_were_in` (delete file).
- Unit/DatabaseViewerTest.php: `it_is_installed`.
- Unit/Notifications, Declaration (journal type, channel, queue). Replace by one test with a `#[DataProvider]` listing notification class, expected `EmailType`, `via` and queue, plus one reflection test that every `HasEmailType` class and every `EmailType` case is listed.
  - AccessEndedTest: `it_goes_out_as_an_email_on_the_high_queue`, `it_belongs_in_the_journal_as_access_that_ended`.
  - AccessUnlockedTest: `it_goes_out_as_an_email_on_the_high_queue`, `it_belongs_in_the_journal_as_access_that_was_unlocked`.
  - AccountExportFailedTest, AccountExportReadyTest, AccountImportEndedTest: `it_is_recorded_as_the_email_saying_an_export_failed`, `..._an_export_is_ready`, `..._an_import_ended` (each also asserts the queue).
  - ChannelDisabledTest: `it_goes_out_by_email_rather_than_on_the_channel_that_stopped_working`, `it_belongs_in_the_journal_as_a_channel_switched_off`.
  - ChannelProofCodeTest: `it_goes_out_on_the_channel_being_proved_rather_than_any_other`, `it_names_the_channel_carrying_it`, `it_goes_on_the_queue_that_cannot_be_kept_waiting`, `it_belongs_in_the_journal_as_a_code`.
  - LoginFailedTest: `it_goes_out_as_an_email`, `it_belongs_in_the_journal_as_a_failed_sign_in`.
  - MagicLinkCreatedTest: `it_goes_out_as_an_email`, `it_goes_on_the_queue_the_impatient_ones_go_on`, `it_belongs_in_the_journal_as_a_sign_in_link`.
  - MarketplaceListingApprovedTest: `it_goes_out_as_an_email`, `it_belongs_in_the_journal_as_an_approved_listing`. Low value: `it_boxes_nothing`.
  - MarketplaceListingRejectedTest: `it_goes_out_as_an_email`, `it_belongs_in_the_journal_as_a_rejected_listing`. Cosmetic: `it_reads_the_reason_before_what_to_do_next` (position of two strings in the render).
  - NewLoginDetectedTest: `it_goes_out_as_an_email`, `it_belongs_in_the_journal_as_a_new_sign_in`.
  - NotificationDigestTest: `it_goes_out_as_an_email`, `it_belongs_in_the_journal_as_a_summary`. Cosmetic: `it_sets_the_wording_of_each_reminder_apart_from_what_it_is_about` (asserts `</strong>`). Low value: `it_words_nothing_again` (same lines as `it_carries_every_line_it_was_handed`).
  - PasskeyAddedTest, PasskeyRemovedTest, PasswordChangedTest, PasswordRemovedTest, PasswordSetTest: `it_goes_out_as_an_email` and the `it_belongs_in_the_journal_as_...` test of each.
  - PasswordResetRequestedTest: `it_goes_on_the_queue_the_impatient_ones_go_on`, `it_belongs_in_the_journal_as_a_password_reset`.
  - ReminderDueTest: `it_goes_out_as_an_email`, `it_goes_on_the_queue_a_deadline_cannot_wait_on`, `it_belongs_in_the_journal_as_a_reminder_that_came_due`. Low value: `a_text_message_carries_no_client_of_its_own_so_the_instances_is_used`.
  - SecurityKeyAddedTest, SecurityKeyRemovedTest: `it_belongs_in_the_journal_as_a_security_key_that_was_added` and `..._removed`.
  - SignInProviderDisconnectedTest: `it_is_recorded_as_its_own_kind_of_email`, `it_goes_on_the_high_queue`, `it_is_sent_by_email`.
  - UserIpChangedTest: `it_goes_out_as_an_email`, `it_belongs_in_the_journal_as_a_sign_in_from_a_new_place`.
  - VerifyEmailTest: `it_goes_out_as_an_email`, `it_belongs_in_the_journal_as_an_address_confirmation`. Low value: `it_signs_the_link_so_it_cannot_be_forged` (the signed URL is Laravel's `VerifyEmail`).
- Unit/Jobs/CreateDefaultTemplateTest.php.
  - Duplicate: `it_seeds_no_section_grey_so_the_mark_is_discoverable` (same file, `it_seeds_every_section_wearing_its_mark` already asserts every section has an icon and a colour equal to the enum), `it_lets_a_contact_hold_as_many_notes_as_somebody_writes` (`it_lets_the_documents_the_photos_and_the_notes_be_added_to_again` asserts repeatable, no max, one Textarea child), `it_puts_the_notes_on_the_tab_a_contact_opens_on` (`it_puts_each_section_on_the_page_it_belongs_to` asserts the exact sections of each page).
  - Declaration: `it_gives_the_two_sections_that_share_a_page_different_marks`, `it_seeds_no_glyph_a_section_may_not_be_marked_with`, `it_says_what_may_be_uploaded_into_the_fields_that_take_a_file`, `it_highlights_what_the_rest_of_the_contact_is_read_in_the_light_of`, `it_offers_the_birthday_to_whatever_reminds_people_of_them`, `it_requires_none_of_them_so_a_contact_may_be_written_down_in_a_hurry`, `it_offers_female_male_and_unknown_with_none_chosen`, `it_asks_for_a_person_on_every_caregiver_row_and_nothing_else`, `it_makes_the_notes_collect_at_the_top_and_leaves_the_rest_collecting_at_the_end`.
  - Low value: `it_gives_no_direction_to_a_field_that_is_not_a_repeater`, `it_writes_no_value_and_no_row`, `it_leaves_the_vaults_of_another_account_alone`, `it_leaves_another_account_alone` (keep `it_leaves_the_templates_of_other_accounts_alone`).
- Unit/Jobs/CreateDefaultLifeEventTypesTest.php: Duplicate `it_reads_the_fields_in_no_group_before_any_group` (`it_writes_the_fields_each_type_asks_for` loops over every type and checks order and `shows_title` per block). Declaration `it_seeds_no_glyph_a_type_may_not_be_marked_with`, `it_carries_the_settings_a_field_needs_to_be_read_as_a_measurement`, `it_keeps_the_choices_of_a_field_in_a_list_of_that_field_alone`. Low value `it_offers_no_supplied_list_of_choices_in_the_account_vocabulary`, `it_marks_no_supplied_field_sensitive_since_no_screen_can_take_the_mark_off`.
- Unit/Jobs/LogUserActionTest.php: Duplicate `it_takes_the_action_for_a_web_one_unless_told_otherwise` (`it_reads_as_the_browser_when_nothing_says_otherwise`). Low value `it_touches_no_vault_unless_it_is_given_one`.
- Unit/Jobs/DetectDuplicateContactsTest.php: `it_proposes_nothing_for_two_plainly_different_people` (`it_proposes_nothing_for_two_siblings_who_share_only_a_family_name` and `it_never_pairs_contacts_of_two_vaults` bound the negative case).
- Unit/Jobs/DeliverDueNotificationsTest.php: `it_tells_nobody_about_a_deadline_already_answered` (NotifyReminderOccurrenceTest::it_makes_nothing_about_a_deadline_somebody_already_answered), `it_holds_everything_back_while_quiet_hours_are_running` (NotifyReminderOccurrenceTest::it_holds_a_notification_due_in_the_small_hours_until_the_quiet_is_over, and the job's own `quiet_hours_hold_that_email_back_without_moving_the_deadline` runs it end to end).
- Unit/Jobs/GenerateDueReminderOccurrencesTest.php: `it_writes_nothing_new_when_it_runs_again` (GenerateReminderOccurrencesTest::it_writes_nothing_the_second_time_over_the_same_window).
- Unit/Jobs/PurgeOrphanedFilesTest.php: `it_leaves_the_files_of_another_account_where_they_are`.
- Unit/Jobs/RecordVaultVisitTest.php: `it_writes_down_when_the_vault_was_opened`.
- Unit/Listeners Stripe: `it_is_retried_before_it_is_abandoned` in HandleStripeCheckoutSessionCompletedTest, DisputeClosedTest, InvoicePaidTest, RefundReversedTest, RefundTest, SubscriptionEndedTest, SubscriptionTest. `it_ignores_every_other_event` in CheckoutSessionCompleted, DisputeClosed, InvoicePaid, RefundReversed, Refund, Subscription, and `it_changes_nothing_for_an_update` in SubscriptionEnded. Add one test over all `HandleStripe*` classes (a provider with class, handled type) asserting the foreign event type does nothing, and a reflection test that every listener declares `tries` above 1 and a non empty `backoff`.
- Unit/Listeners/CheckDatabaseIsReadyTest.php: `it_is_the_listener_the_health_check_reaches`.
- Unit/Listeners RecordEmail*: `it_is_the_listener_the_provider_event_reaches` (Bounced), `it_is_the_listener_the_provider_event_reaches` (Complained, Delivered, Failed, same name). Merge the four files into one provider test (event class, payload type, column).
- Unit/Policies/ContactPolicyTest.php: `the_user_object_answers_the_same_as_the_gate`.
- Unit/Console LocalizeCommandTest: Duplicate `it_does_not_mistake_a_blade_component_attribute_for_a_phrase_that_shows_a_number` (runs the same full `--check` as `it_keeps_the_locale_files_in_step_with_the_code` and expects success), `it_names_the_call_rather_than_the_placeholder_when_the_call_is_the_mistake` (same scratch file and same message as `it_fails_when_several_wordings_are_asked_for_with_a_call_that_picks_none`). Low value `it_leaves_the_english_source_alone_because_english_declares_no_typography`, `it_reports_a_value_quoted_with_curly_double_quotes` (same branch as the straight quote test), `it_reports_a_value_written_with_no_space_at_all_before_a_question_mark` (same branch as the ordinary space test).
- Unit/Console ScreenshotLinkCommandTest: `it_mints_a_link_that_signs_the_user_in`.
- Unit/Console SetupApplicationCommandTest: `it_leaves_the_storage_link_alone_while_testing`.
- Unit/Http/Dav CalendarBackendTest: `it_refuses_to_make_a_calendar`, `it_refuses_to_delete_a_calendar`, `it_refuses_to_write_an_entry`, `it_refuses_to_change_an_entry`, `it_refuses_to_remove_an_entry` (one provider test).
- Unit/Http/Dav AddressBookBackendTest: `it_refuses_to_make_an_address_book`, `it_refuses_to_delete_an_address_book` (one provider test), `it_tells_what_changed_in_the_shape_the_standard_asks_for`.
- Unit/Http/Dav VCardMergerTest: `it_writes_the_label_of_an_email_as_the_type_a_card_knows`. CardDavPluginTest: `it_relies_on_the_parser_naming_the_line_it_cannot_read`.
- Unit/Translation FrenchTypographyTest: all 7 (delete file). CountedPhraseTest: Declaration `it_asks_each_shipped_language_for_the_number_of_wordings_that_language_needs`, `every_shipped_language_says_how_many_wordings_it_needs` (a copy of `MessageSelector`'s table, and the `--check` already derives it per locale). Duplicate `every_segment_of_every_counted_phrase_in_the_catalogue_interpolates_the_count` (Console LocalizeCommandTest::it_keeps_the_locale_files_in_step_with_the_code runs `wordingsMissingTheCount` on every locale file). Low value `it_falls_back_to_the_english_wording_and_the_english_rules_when_a_phrase_is_not_translated` (framework), `the_readings_that_were_wrong_at_one_now_agree_with_their_number` (literal strings of the test, not of the catalogue).
- Unit/Providers AppServiceProviderTest: `it_registers_the_collation_macro_on_collections`, `it_files_a_letter_carrying_a_ring_with_its_plain_letter`.
- Unit/Migrations: all 5 in BackfillNotificationPreferencesTest and all 4 in BackfillContactNameRepresentationsTest.
- Unit/Seeders MarketplaceCategorySeederTest: `it_files_the_categories_at_the_top_level`.

### Patterns
- Conformance tests per notification class (50). Every notification test file ends with the same three or four facts: `via` is `['mail']`, the queue is `high`, `emailType()` returns a case. 25 `it_belongs_in_the_journal_as_...`, 18 `it_goes_out_as_an_email` style, 5 standalone queue tests. They restate the class body. One provider test over all classes, plus one reflection test that every `HasEmailType` class is in the table, proves the same and also catches a notification added without a row. The testing.md "Notification tests" section currently asks for the `emailType()` assertion per class, which is what produced this. It should be amended.
- Default data restated from enums (21 tests in CreateDefaultTemplateTest and CreateDefaultLifeEventTypesTest). Examples: mime types of the file field, highlighted fields, 18 fields all not required, glyphs in the allowed set. The enum is the declaration, and the test is a second copy that fails on every product copy change. Keep the tests of the job's own mechanics: the template is published, assembly order, value lists frozen, vaults pointed at the version, idempotence, locale, log once.
- Same test written N times per class (Stripe listeners, RecordEmail listeners, DAV Forbidden backends). `it_is_retried_before_it_is_abandoned` and `it_ignores_every_other_event` appear 7 times each. RecordEmail* are four files differing in one event class and one column.
- A job test that re-proves its action (about 8). The four Seed* jobs, `DeliverDueNotifications` answered and quiet hours cases, `GenerateDueReminderOccurrences` idempotence, `LogUserAction` defaults.
- Tests of the framework or a vendor package. User::can, Laravel's signed URL on the verification mail, ICU collation of a ring letter, MessageSelector's fallback, sabre/vobject's error message, the plural table of the framework.
- Tests of shipped one shot work (9 migration backfills, `ExampleTest`).
- Within file variants that follow each other: Localize quote marks (straight, curly), spaces (ordinary, none), CreateDefaultTemplate other-account variants (three).

### Cost hot spots
1. `tests/Unit/Console/Commands/LocalizeCommandTest.php`, 31 seconds for 55 tests, about 0.55 s each. `LocalizeCommand::handle()` always does the following, whatever the test wants to prove: reads and tokenizes all 1,431 PHP and Blade files under `app` and `resources/views` (`files()`, `codeOf()` with `token_get_all`), boots LaravelLang's `Update::prepare()->collect()` for the vendor strings, then loops over 31 locales, rereading and `json_encode`ing a 3,900 line file for each (`synchronize()`). The tests point only the lang path at a temp directory (`useLangPath`). Scratch files are written into the real `app/` and `resources/views/scratchtest` and removed in tearDown, which is not safe under parallel runs and leaves junk if a run dies. Four of the tests also assert against the real codebase (`it_ignores_a_call_written_inside_a_comment` expects a comment somewhere in `app` to contain "Why this is empty.").
   Proposed split, keeping the same assurance:
   - Run the full scan once. `it_keeps_the_locale_files_in_step_with_the_code` stays as is. It is the only place CI runs `monica:localize --check` (no workflow step does, I grepped `.github`), so it must stay. Cost about 0.5 s.
   - Move the checks to a class with pure methods. Candidates are already self contained: `typographyRulesBrokenBy(translation, locale)`, `wordingsMissingTheCount`, `wordingsIn`, `wordingsExpected`, `carriesACondition`, `isPinnedToOneNumber`, `recordMisuses` and `guardsZeroBefore` (take a string of code and return a list of misuses), `proseOf`. About 40 of the 55 tests (all typography, counted phrase, misuse and pin tests) then become milliseconds long unit tests with a `#[DataProvider]`, no Artisan, no filesystem, no database. This is the stronger option, since each test then names the rule it proves instead of grepping console output.
   - Give the command a scan seam for the few tests that must go through `handle()`: a `config('localization.scan_paths')` defaulting to `[app, resources/views]`, set by the test to `tests/Fixtures/Localize/` holding three tiny files (one with `__()`, one comment, one group file call, one Blade file with a pinned `trans_choice`). With a fixture directory the scan reads 4 files instead of 1,431. Also add a way to skip the LaravelLang collection and limit `locales()` to two (`config` again, or filter `AvailableLocales::codes()`), since 31 locale rewrites are the other half of the cost. Roughly 8 command level tests remain: writes en, enum strings, group file, comment, keeps translations, drops removed strings, `--check` writes nothing, locale order.
   - Estimated result: 31 s to about 2 to 3 s, and no file written under `app/`. I did not run anything to measure it.
2. `tests/Unit/Jobs/PurgeExpiredAccountExportsTest::it_expires_every_export_when_there_are_more_than_one_page_of_them` creates 1,001 `AccountExport` rows through the factory to cross `lazyById`'s default chunk of 1,000. Cheaper: the job reads `lazyById()` with the default, so either let the job take the chunk size from a constant the test can lower (and use 3 rows with chunk size 2), or leave it and accept the one slow test. The page boundary is a real bug class, so keep the test but make it cheap.
3. `CreateDefaultTemplateTest` (40 tests, 864 lines) runs the full seeding job, a few hundred rows, in each test, plus `CreateDefaultValueLists` in each `userWithValueLists()`. Removing the 16 above and merging the remaining assertions of the contact template into two or three tests (one run each) cuts roughly 25 job runs. The same applies to CreateDefaultLifeEventTypesTest (14 runs of 40 types).
4. Notification tests: every test calls `User::factory()->create()` under `RefreshDatabase`, but `toMail($user)` only reads attributes. `User::factory()->make()` and dropping `RefreshDatabase` works in most files (not VerifyEmail and MagicLink, which read an id or a row). About 140 inserts and per test transaction overhead.
5. `Unit/Translation/CountedPhraseTest::every_counted_phrase_renders_cleanly_at_zero_one_two_and_a_thousand_in_every_shipped_locale` does about 4 x 31 x (counted keys) `trans_choice` calls. It is a catalogue test with no I/O beyond reading the 31 json files. Keep, but it shares the file reads with the localize test, so no change needed.
6. `Unit/Console/Commands/SetupApplicationCommandTest` runs the real `monica:setup`, which migrates (3 of 7 tests). Acceptable, the command's orchestration is its subject.

### Proposed guidelines
- A fact that is the same for every class implementing an interface (journal type, channel, queue, retry settings, forbidden operation) is tested by one `#[DataProvider]` test over all implementations, plus one reflection test that no implementation is missing. Never one test method per class.
- Do not assert data that an enum, a config file or a constant already declares by comparing it with a literal copy of the same data. Test the mechanics that consume it, and test one snapshot at most.
- A job that only calls an action gets one test, that it calls the action for the right record (or none when the action's test and the controller's cover it). Not a re-run of the action's assertions.
- If the job's query or loop decides something (a scope, a window, a chunk), test that decision with the smallest data. Do not repeat the action's refusals.
- A test of a command that reads the codebase must read a fixture directory, never the real `app/`, and must never write files into `app/` or `resources/`. Extract pure checks into a class and test them without Artisan.
- Do not test shipped one shot backfill migrations after they have run in production. Test a backfill only if it can run again against new rows.
- Do not test Laravel, sabre or ICU behaviour. Test only the code of ours that calls them.
- A test with no `#[Test]` attribute, `ExampleTest`, and tests of a test helper are deleted, not kept.
- When two tests drive the same code with the same arrange step and differ only in one literal (curly versus straight quote, no space versus ordinary space), keep one.
- In a test of an HTTP or console adapter where the first test already dispatches the real event, do not add a test that reads the listener map.

### Open questions
- Migrations (9 tests): I classified them Low value on the strength of the project rule that shipped migrations are never edited. I did not check which environments have run `2026_09_02_000006_backfill_notification_preferences.php` and `2026_10_06_000002_backfill_contact_name_representations.php`. If any environment has not, keep them until it has.
- Notification conformance: removing the per class `emailType()` tests contradicts the current wording of `.claude/rules/testing.md` (Notification tests). The rule needs a change to match the provider approach, which is a decision for you.
- CreateDefaultTemplateTest declarations: they also act as a snapshot guard on the product's default template. If you want a guard, one snapshot test of the whole contact template and one of the animal template (field key to type, as `it_lays_the_animal_template_out_as_supplied` already does) replaces the 9. I assumed the enums are the source of truth.
- `Features/ShippedStateTest::it_ships_a_withdrawn_capability_switched_off_for_every_account` (4 data sets): kept because flags.md says a test of what ships must call `Feature::discover()`. It has to be edited at every flag release. Debatable, left in Keep.
- `Policies`: all 68 left as Keep apart from one, per the brief. I did not check whether the matching action tests assert the same refusal wording again (that would make the action level assertion the duplicate). I did not open Actions tests for the policy wording.
- `Http/Dav`: I did not check whether tests/Feature has CardDAV route tests that already cover `CardDavPlugin` or the backends end to end. That is outside this scope.
- LocalizeCommand split: I read the command and the tests but did not run them or measure what share of the 31 seconds is the scan, the vendor collect, or the 31 locale writes. The proposed order of the saving is an estimate.
- RecordEmail* first tests (4) are Keep, but `RecordEmailComplained`, `Delivered` and `Failed` differ from `Bounced` only in event class and column (checked with `diff` for Bounced and Failed, assumed for the other two).

> Checked by the coordinator: no file in `.github/workflows` runs `monica:localize`, so `LocalizeCommandTest` is the only place CI enforces the translation rules, and the full `--check` run must survive any rewrite. The test writes `app/Scratch.php` and `resources/views/scratchtest/` into the real tree (lines 679 to 685) and removes them in `tearDown()`; a test killed midway leaves them behind. `tests/Unit/ExampleTest.php` asserts `true` and nothing else. The migration backfill tests were not checked against production state.

## Proposed guidelines for the testing rules

Drawn from the eleven sections above, merged and deduplicated. Each rule names what it would have
prevented. They are proposals for `.claude/rules/testing.md` and the `tests` skill; nothing has been
changed in either.

### Where a test lives

1. **A controller test proves the HTTP and stops.** One render with one sentence that identifies the
   screen, the inline validation, the redirect and flash (or the JSON shape), and one refusal from the
   action reaching the reader. Nothing about what the screen lists, counts, orders, pages or labels,
   and nothing about the rows the action writes. *Would have prevented about 800 controller tests,
   the largest group in this audit.*
2. **Name the owner before you write a second test of a rule.** When a test feels needed one layer
   up, open the owning test first. If it is there, stop. If it is not, add it there.
3. **A helper, trait or middleware is tested once, where it lives.** Callers prove the wiring in one
   test. This covers `SlugBuilder`, `TextSanitizer`, `PageSize::paginate`, `ResolvesModelInVault`,
   `ResolvesModelInAccount`, the seeders, the locale resolution and the flag middleware.
4. **A route group gate is tested once over all its routes**, by one data provider or one reflection
   test: `password.confirm`, `signed`, the admin space, a flag. Never one test per route.
5. **A job that only calls an action gets one test**, that it calls it for the right record. The
   decisions the job makes itself (a scope, a window, a chunk) are tested with the smallest data.

### What a test asserts

6. **Assert the journal write inside the test of what the action does.** Never in a test of its own,
   and never in a controller test. (Already in `testing.md`, broken 22 times.)
7. **Do not assert that a column was left alone** unless something other than the action (an
   observer, a cascade, a shared helper) could write it. Name that thing in the test name.
8. **Do not restate a declaration.** No test that a model has a uuid, a route key, soft deletes or a
   fillable list; that an enum `match` returns each label; that a tool mirrors a route; that a
   middleware is on a route. When every implementation of an interface must agree on a fact, one data
   provider test over all of them, plus one reflection test that none is missing.
9. **Do not pin the size of a catalogue** (`assertCount(166, ...)`, 37 icons, 419 time zones). Assert
   that two sources agree instead.
10. **Do not test the framework or a vendor**: pagination, signed URL expiry, `EloquentUserProvider`,
    ICU, sabre.
11. **A test must be able to fail.** No assertion comparing a value with itself, no "nothing was
    written" after faking the writer.
12. **Cosmetic, again, with the cases found.** Besides class strings: a bare tag, a regular expression
    over markup, a count of `aria-*` attributes, an Alpine expression, a positioning directive, an icon
    or tint name returned by a view model, `assertSeeInOrder` of navigation labels.

### How a test is built

13. **Variants go in a `#[DataProvider]`.** Validation is one test per endpoint with the invalid
    payloads as rows, never one method per field. The Settings area has no data provider at all and
    83 low value variant tests.
14. **Build the rows the rule needs, not the rows the constant needs.** Paging is proven with three
    rows and a page size argument, not 30 accounts or 120 events.
15. **Declare `RefreshDatabase` only where most tests touch the database.** Move the few that do into a
    class of their own (`StructureFileReaderTest`: 54 tests, a handful use it).
16. **A fixture builder used in three files or more lives once** in `tests/Fixtures` or `TestCase`.
    Eight Settings files carry a private copy of the same published template builder.
17. **A test that needs the real search engine proves matching** (typo tolerance, accents, ranking,
    word order) and nothing else. Scoping, filtering and argument mapping run on the database driver
    or a fake.
18. **A command test never scans or writes the real `app/` or `resources/`.** It reads a fixture
    directory, and the checks it runs are a class tested without Artisan.
19. **A skip that depends on the driver is checked against the CI matrix.** A test that never runs on
    the job that runs is not assurance.
20. **No `dump()`, `dd()`, unused helpers or scaffolding (`ExampleTest`) in a commit.**

### Rules to change

- **Notification tests.** `testing.md` asks every notification test to assert `emailType()`. That is
  the per class conformance this audit counts as Declaration (50 of 145 tests). Replace it with one data
  provider test over every class implementing `HasEmailType`.
- **The wrong vault test.** `testing.md` asks a controller to prove the wrong vault of the same account
  returns not found. Keep that for controllers whose bound model uses `ResolvesModelInAccount` and
  relies on `abort_unless`. Where the model uses `ResolvesModelInVault`, the trait test owns it, once
  that test covers the vault branch.
- **Migrations.** Add: a one shot backfill migration is not tested once it has run in production.
