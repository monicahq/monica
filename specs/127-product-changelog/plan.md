# Implementation Plan: Product changelog ("What's new")

**Branch**: `2026-11-17-product-changelog` | **Date**: 2026-11-17 | **Spec**: [spec.md](spec.md)

## Summary

Entries are Markdown files in `changelog/`, read and cached by `App\Helpers\ChangelogEntries` and
translated through `monica:localize` like any other string. What's new reads them, grouped by UTC day,
with seen marks, votes and comments held in four tables keyed by the entry ULID. The admin space gains
a Changelog section to review, answer and hide comments and read votes. Decisions are in
[research.md](research.md), storage in [data-model.md](data-model.md), the API in
[contracts/api.md](contracts/api.md).

## Technical Context

**Language/Version**: PHP 8.5, Laravel, Blade, Tailwind CSS 4, Alpine Ajax; no new dependency (R2)
**Storage**: files in `changelog/`; MySQL/SQLite tables of data-model.md | **Testing**: PHPUnit
**Target Platform**: the signed in app, the admin space and `/api/v1` of the Laravel monolith
**Performance Goals**: the badge adds one indexed query and no file parse per page (R3)
**Constraints**: WCAG 2.2 AA; every string and entry in every configured locale; UTC days
**Scale/Scope**: 4 migrations, 4 models, 9 actions, 3 enums, 1 policy, 5 view models, 9 web/admin and 4 API controllers

## Constitution Check

| Principle | Status |
| --- | --- |
| I. Actions | Pass: every write is an action; controllers validate and delegate |
| II. Simplicity | Pass: files plus four tables; no YAML parser, no CMS (R2) |
| III. Tested | Pass: see Tests; the entry check runs in the suite |
| V. Localized | Pass: entries are translation keys (R1); destructive action: hiding asks first |
| VI, VIII | Pass: additive screens and endpoints; a hidden comment stays readable to its author and the admin space |
| IX. Accessible | Pass: `aria-current` period links, `aria-pressed` votes, `aria-expanded` disclosures, spoken badge counts |
| X. Through the API | Pass: contracts/api.md covers every What's new capability; admin moderation stays web only, as `admin/support` |
| XI. Short record | Pass: spec 120 lines, plan under 100. Re-checked after design: no change |

## Project Structure

```text
changelog/01KC….md                                            # the first entry: What's new itself
app/Helpers/ChangelogEntry.php, ChangelogEntries.php          # parse, cache, check (R2, R3)
app/Enums/ChangelogEntryTypeEnum.php, ChangelogCommentStatusEnum.php, ChangelogVoteDirectionEnum.php
app/Enums/AdministratorActionEnum.php                         # + 4 changelog comment cases
app/Models/Changelog{SeenEntry,Vote,Comment,CommentReply}.php + factories; four migrations, one table each (migrations skill)
app/Actions/MarkChangelogSeen, CastChangelogVote, WithdrawChangelogVote, PostChangelogComment
app/Actions/ReviewChangelogComment, HideChangelogComment, ShowChangelogComment, ReplyToChangelogComment
app/Notifications/ChangelogReplySent.php                      # private reply by email
app/Http/Controllers/App/Changelog{,Vote,Comment}Controller.php # index marks seen after reading state
app/Http/Controllers/App/Admin/Changelog{Comment,Vote,CommentReview,CommentHiding,CommentReply}Controller.php
app/Http/Controllers/Api/Changelog{,Seen,Vote,Comment}Controller.php; app/Http/Resources/Changelog{Entry,Comment}Resource.php
app/ViewModels/Changelog{,Comments}ViewModel.php; app/ViewModels/Admin/Changelog{Moderation,Votes}ViewModel.php
app/ViewModels/Admin/AdminNavigationViewModel.php             # + changelogToReviewCount()
app/Console/Commands/LocalizeCommand.php                      # + entries as a source (R1)
resources/views/app/changelog/index.blade.php, partials/{entry,fixes,comments}.blade.php
resources/views/app/admin/changelog/{index,votes}.blade.php
resources/views/components/bar-links.blade.php, layouts/admin.blade.php # links with counts
resources/docs/api/40-changelog.php                           # api-docs-writer; plus an API changelog entry
.claude/skills/changelog/SKILL.md; CLAUDE.md "When a change is done" names it
lang/*.json                                                   # monica:localize, Sonnet subagents
```

## Routes (web)

| Method | URI | Name |
| --- | --- | --- |
| GET | `changelog` | `changelog.index` (`?period=90`) |
| PUT, DELETE | `changelog/{entry}/vote` | `changelog.vote.update`, `.destroy` |
| GET, POST | `changelog/{entry}/comments` | `changelog.comments.index`, `.create` (`throttle:10,1`) |
| GET | `admin/changelog` | `admin.changelog.index` (`?tab=&entry=&search=&type=&visibility=&period=`) |
| GET | `admin/changelog/votes` | `admin.changelog.votes.index` (`?sort=down|up|new`) |
| POST | `admin/changelog/comments/{comment}/review` | `admin.changelog.comments.review.create` |
| POST, DELETE | `admin/changelog/comments/{comment}/hiding` | `admin.changelog.comments.hiding.create`, `.destroy` |
| POST | `admin/changelog/comments/{comment}/replies` | `admin.changelog.comments.replies.create` |

`{entry}` `whereUlid`, `{comment}` `whereUuid`. API routes in contracts/api.md.

## Screens

**What's new** (`x-shell`, from `Changelog v2.dc.html`): `x-page-header`, period links, four figures,
then per day a two column grid (`grid-cols-[180px_minmax(0,1fr)]`, one column below `md`); entries
with `x-badge` (New: success, Improved: info, Fix: neutral), `x-badge` "New" when unseen, votes as
`aria-pressed` forms, the Comment button loading the thread (R6); "N fixes" list with a disclosure per
row. "Seen on your last visit" before the first day holding no unseen entry. **Admin**
(`x-admin-layout`, `Admin Changelog.dc.html`): `x-tabs`, GET filter form, list and detail, Hide through
`x-delete-action`'s confirmation, Votes table.

## Tests

| Layer | Test |
| --- | --- |
| `ChangelogEntries` | parse; cache rebuilt on mtime change; `visible()` drops future and invalid; `problems()` names duplicate id, bad ULID, type, date, link; shipped folder has no problem |
| `ChangelogViewModel` | UTC grouping; fixes last in a day; ordering; 30/90 period; four figures; unseen count after account creation; divider position; New marks |
| Actions | seen inserts only missing rows; vote cast, switch, withdraw; comment rules (empty, private not anonymous); review, hide public only, show, reply public vs emailed and marks reviewed; admin log written; non administrator refused |
| `ChangelogCommentsViewModel` | others' private and hidden left out and uncounted; anonymous author never named; public replies only |
| Admin view models | tabs and filters; to review count; votes sort |
| Controllers (web, admin, API) | each route answers; validation; JSON structure; unknown entry 404; `AdminAccessTest` data provider gains the URLs |
| `ChangelogReplySent`, `LocalizeCommand` | subject, panel, body; entries become keys |

## Complexity Tracking

None.
