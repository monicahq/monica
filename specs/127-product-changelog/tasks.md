# Tasks: Product changelog ("What's new")

**Input**: [spec.md](spec.md), [plan.md](plan.md), [research.md](research.md), [data-model.md](data-model.md), [contracts/api.md](contracts/api.md)

Tests are included: `CLAUDE.md` makes a covering test part of "done", and SC-004 names them.

## Phase 1: Setup

- [X] T001 Create `changelog/.gitkeep` and test fixture entries in `tests/Fixtures/Changelog/Valid/` (The Office data: on 2026-11-16 a `feature` at 09:00 and two `fix` at 08:00 and 15:00; an `improvement` at 2026-11-16T23:30:00Z; an entry 60 days old; one dated in the future) in the format of `data-model.md`
- [X] T002 [P] Create one defective file per case in `tests/Fixtures/Changelog/Invalid/`: missing `id`, `id` not a 26 character Crockford ULID, a shared `id`, `type` not "`feature`, `improvement` or `fix`", `published_at` not "ISO 8601 in UTC (`Z`)", `link` `https://evil.test`, `link` `//evil.test`, `link` `/\evil.test`, empty body

## Phase 2: Foundational

- [X] T003 Create `app/Enums/ChangelogEntryTypeEnum.php` (`Feature`, `Improvement`, `Fix`, values `feature`, `improvement`, `fix`), `app/Enums/ChangelogVoteDirectionEnum.php` (`Up`, `Down`), `app/Enums/ChangelogCommentStatusEnum.php` (`New`, `Reviewed`, `Hidden`)
- [X] T004 Create `app/Helpers/ChangelogEntry.php`: readonly `id`, `title`, `type` (`ChangelogEntryTypeEnum`), `area` (`?string`), `publishedAt` (`CarbonImmutable` UTC), `link` (`?string`), `body`, `file`; `day(): string` (UTC `Y-m-d`); `isFix(): bool`
- [X] T005 Create `app/Helpers/ChangelogEntries.php` with `__construct(?string $directory = null)` defaulting to `base_path('changelog')`: hand parser of the two `---` lines and `key: value` lines, trimming one pair of quotes (research R2); `all()` cached forever under a key from sorted file names and `filemtime`s (R3); `visible()` (valid and `published_at <= now()`); `find(string $id): ?ChangelogEntry` (visible only); `problems(): list<string>` naming file and defect; a link failing `^/(?![/\\])\S*$` is dropped at read time (R8)
- [X] T006 Create migration `database/migrations/2026_11_17_000001_create_changelog_tables.php` (`up()` only) with the four tables of `data-model.md`: `user_id` as `foreignIdFor(User::class)->constrained()->cascadeOnDelete()`, `entry_id` `char(26)` never a foreign key, unique (`user_id`, `entry_id`) on reads and votes, index (`entry_id`, `status`) on comments, `author_user_id` nullable `nullOnDelete()`
- [X] T007 [P] Create models `app/Models/ChangelogSeenEntry.php`, `ChangelogVote.php`, `ChangelogComment.php` (casts to the enums, `uuid` route key, `hasMany` replies, `belongsTo` user), `ChangelogCommentReply.php`, with full model docblocks and factories in `database/factories/`
- [X] T008 [P] Unit tests in `tests/Unit/Helpers/ChangelogEntriesTest.php`: parsing of `Valid/`; `visible()` drops the future entry; cache rebuilt after a `touch()`; `problems()` empty for `Valid/` and one message per `Invalid/` file; the shipped `changelog/` folder reports no problem (FR-009)
- [X] T009 Add `changelog/` as a source to `stringsToTranslate()` in `app/Console/Commands/LocalizeCommand.php`: each visible entry's `title`, `area` and body become keys (research R1); test in `tests/Unit/Console/Commands/LocalizeCommandTest.php` that a fixture entry's title is reported missing

## Phase 3: User Story 1, read what changed day by day (P1)

- [X] T010 [US1] Create `app/ViewModels/ChangelogViewModel.php` (`__construct(User $user, int $period, ChangelogEntries $entries)`): `period()` 30 or 90; `figures()` days with changes, features, improvements, fixes in the period; `days()` UTC days newest first, each with `label()` ("Today", "Yesterday" from the UTC date of now, else the reader's date format), `entries()` features and improvements by `published_at` descending, `fixes()` by `published_at` descending; texts through `__()`, body via `KnowledgeMarkdown::toHtml()`; comment count of visible public comments, the reader's vote
- [X] T011 [US1] Create `app/Http/Controllers/App/ChangelogController.php` `index`: `period` from the query (`90` else `30`, research R7), renders `resources/views/app/changelog/index.blade.php`; route `GET changelog` named `changelog.index` in `routes/web.php`
- [X] T012 [US1] Build `resources/views/app/changelog/index.blade.php` from `Changelog v2.dc.html` in `x-shell`: `x-page-header` "What's new" and the introduction; period links with `aria-current`; four figures; per day a grid `grid-cols-[180px_minmax(0,1fr)]` stacking below `md`; the empty period sentence; partials `app/changelog/partials/entry.blade.php` (badge New/Improved, area, title, body, link with arrow) and `partials/fixes.blade.php` ("N fixes" via `trans_choice()`, built in the view model, one row per fix)
- [X] T013 [US1] Create `app/Http/Controllers/Api/ChangelogController.php` `index` and `app/Http/Resources/ChangelogEntryResource.php` per `contracts/api.md` (route `GET, QUERY changelog`, `api.v1.changelog.index`)
- [X] T014 [P] [US1] Unit tests in `tests/Unit/ViewModels/ChangelogViewModelTest.php`: one group per UTC day, the 23:30 UTC entry stays on its day, fixes after features and improvements, ordering, 30 against 90 days, the four figures, "Today" and "Yesterday" labels
- [X] T015 [P] [US1] Feature tests `tests/Feature/Controllers/App/ChangelogControllerTest.php` (page answers with "What's new", `period=90`) and `tests/Feature/Controllers/Api/ChangelogControllerTest.php` (`$jsonStructure`, order, `meta`)

## Phase 4: User Story 2, what is new since my last visit (P1)

- [X] T016 [US2] Create `app/Actions/MarkChangelogSeen.php`: inserts a `changelog_seen_entries` row for every visible unseen entry, nothing for one already read (research R4)
- [X] T017 [US2] Add to `ChangelogViewModel` `unseenCount()` (visible, published after `users.created_at`, no read row), `isNew(ChangelogEntry)` from the state before the visit, `showsSeenDivider(day)` before the first day holding no unseen entry; `ChangelogController::index` reads the state, then runs `MarkChangelogSeen`
- [X] T018 [US2] Add What's new to `resources/views/components/bar-links.blade.php` (icon `activity`, `route('changelog.index')`) with a count badge whose `aria-label` reads `trans_choice(':count change you have not seen|:count changes you have not seen', …)`, built outside the view, hidden at zero; "New" badge and "Seen on your last visit" divider in the page
- [X] T019 [US2] Create `app/Http/Controllers/Api/ChangelogSeenController.php` `update` (`PUT changelog/seen`, 204); `is_seen` and `meta.unseen_count` in the index answer
- [X] T020 [P] [US2] Tests: `tests/Unit/Actions/MarkChangelogSeenTest.php`; unseen count, account creation cut off and divider in `ChangelogViewModelTest`; API seen route in `tests/Feature/Controllers/Api/ChangelogSeenControllerTest.php`

## Phase 5: User Story 3, vote and comment (P2)

- [X] T021 [US3] Create `app/Actions/CastChangelogVote.php` (one vote per person per entry, switch on the other direction) and `app/Actions/WithdrawChangelogVote.php`; both throw `ModelNotFoundException` for an unknown or future entry
- [X] T022 [US3] Create `app/Actions/PostChangelogComment.php`: body `TextSanitizer::plainText()`, "1 to 2000" characters, `is_anonymous` forced false when `is_private`, status `new`
- [X] T023 [US3] Create `app/ViewModels/ChangelogCommentsViewModel.php`: public not hidden comments plus the reader's own, author "Anonymous" for anonymous ones except "Only you can see this is yours" for the author, public replies with "Administrator", the count
- [X] T024 [US3] Create `app/Http/Controllers/App/ChangelogVoteController.php` (`update`, `destroy`) and `ChangelogCommentController.php` (`index`, `create` with `throttle:10,1`) and their routes (`whereUlid('entry')`); `resources/views/app/changelog/partials/comments.blade.php` loaded with Alpine Ajax (`x-target`, research R6); vote forms with `aria-pressed` and "Thanks, noted"; a disclosure with `aria-expanded` on each fix row
- [X] T025 [US3] Create `app/Http/Controllers/Api/ChangelogVoteController.php`, `ChangelogCommentController.php` and `app/Http/Resources/ChangelogCommentResource.php` per `contracts/api.md`
- [X] T026 [P] [US3] Tests: `tests/Unit/Actions/CastChangelogVoteTest.php`, `WithdrawChangelogVoteTest.php`, `PostChangelogCommentTest.php`; `tests/Unit/ViewModels/ChangelogCommentsViewModelTest.php` (others' private and hidden left out and uncounted, anonymous never named); web and API controller tests for both resources (validation, 404 on unknown entry)

## Phase 6: User Story 4, administrators moderate and answer (P2)

- [X] T027 [US4] Add `ChangelogCommentReviewed`, `ChangelogCommentHidden`, `ChangelogCommentShown`, `ChangelogCommentReplied` to `app/Enums/AdministratorActionEnum.php` with their translated labels
- [X] T028 [US4] Create `app/Actions/ReviewChangelogComment.php`, `HideChangelogComment.php` (public comments only), `ShowChangelogComment.php`, `ReplyToChangelogComment.php` (public reply stored, private reply emailed through `app/Notifications/ChangelogReplySent.php`, both mark the comment reviewed); each checks `isAdministrator()` and dispatches `LogAdministratorAction`, as `SendSupportReply`
- [X] T029 [US4] Create `app/ViewModels/Admin/ChangelogModerationViewModel.php` (tabs To review, Private, Hidden, All comments with counts; filters search on comment, entry title, name, email; type; visibility; period; list of entries holding matches newest first; the selected entry's comments, votes, id, file, counts) and `ChangelogVotesViewModel.php` (sort `down`, `up`, `new`)
- [X] T030 [US4] Create admin controllers `Changelog{Comment,Vote,CommentReview,CommentHiding,CommentReply}Controller.php` in `app/Http/Controllers/App/Admin/` and the six routes of plan, Routes, in `routes/administrator.php`
- [X] T031 [US4] Build `resources/views/app/admin/changelog/index.blade.php` and `votes.blade.php` from `Admin Changelog.dc.html` in `x-admin-layout`; Hide asks first; add Changelog (icon `journal`, count of comments to review) to the nav in `resources/views/layouts/admin.blade.php` through `AdminNavigationViewModel::changelogToReviewCount()`
- [X] T032 [P] [US4] Tests: one per action in `tests/Unit/Actions/`, `tests/Unit/Notifications/ChangelogReplySentTest.php`, `tests/Unit/ViewModels/Admin/ChangelogModerationViewModelTest.php` and `ChangelogVotesViewModelTest.php`, admin controller tests; add the admin URLs to the data provider of `tests/Feature/Controllers/App/Admin/AdminAccessTest.php`

## Phase 7: User Story 5, coding agents write the entries (P2)

- [X] T033 [US5] Create `.claude/skills/changelog/SKILL.md` in the layout of the other skills: the qualifying and excluded changes of the brief, the file format of `data-model.md`, a fresh ULID never used before, `published_at` now in UTC, one entry per change, never from commit messages, never change an `id`, then `php artisan monica:localize` and Sonnet subagents for every locale
- [X] T034 [US5] Add to "When a change is done" in `.claude/CLAUDE.md` one line: a user facing change carries its entry, per the `changelog` skill
- [X] T035 [US5] Write the first entry `changelog/<ulid>-whats-new.md` (`feature`, area "Account") announcing What's new itself

## Phase 8: Polish

- [X] T036 Add the What's new endpoints to `resources/docs/api/` (api-docs-writer skill) and an entry to the API changelog
- [X] T037 Run `php artisan monica:localize` and translate every missing key, the entry included, with Sonnet subagents; `php artisan monica:localize --check` passes
- [X] T038 Add the new public or protected routes to `tests/Feature/RouteProtectionTest.php` as it requires; run the whole suite (shared `bar-links` and admin layout changed)
- [X] T039 `vendor/bin/phpstan analyse --no-progress` and `vendor/bin/pint --dirty --format agent` clean; accessibility-reviewer and rules-reviewer agents on the diff

## Dependencies

Phase 1 → Phase 2 → US1 → US2 (badge reads the view model) → US3 → US4 (moderates US3 comments). US5 depends on Phase 2 only (the entry check) and can run beside US1. Polish last.

Parallel: T001–T002; T007–T008 after T006; each story's test task beside its implementation; T033–T034 beside US1.

## Implementation strategy

MVP is US1 and US2: entries read, grouped, counted and marked seen, with the skill (US5) so entries start arriving. Then US3 feedback, then US4 moderation, which needs US3 data to be useful.
