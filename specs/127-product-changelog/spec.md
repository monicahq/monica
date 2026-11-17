# Feature Specification: Product changelog ("What's new")

**Feature Branch**: `2026-11-17-product-changelog` | **Created**: 2026-11-17 | **Status**: Draft

**Input**: the "Changelog: Additional Requirements" brief given with `/speckit-specify` (daily grouping,
the `changelog` agent skill, UI, front matter, acceptance criteria). Designs: `Changelog v2.dc.html` and
`Admin Changelog.dc.html`, design project `670b2827-68ce-4595-8d08-43fbe6fcd282`.

**Decided**: three types only (`feature`, `improvement`, `fix`); the design's "Security" and "Removed"
badges are left out. Entries are written in English and translated into every configured locale (2026-11-17).
"Days with a release" reads "days with changes". Administrators moderate in the admin space (2026-11-17).

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Read what changed, day by day (Priority: P1)

A signed in person opens "What's new" from the top bar. Under the title and introduction, a period
switch (Last 30 days, Last 3 months) and four figures for that period: days with changes, new
features, improvements, bugs fixed. Below, one group per day, newest first: the day on the left
("Today", "Yesterday", or the date), its changes on the right. Features and improvements come first,
each with its badge, area, title, Markdown description, optional link and comment count. The day's
fixes close the group in a compact list under "N fixes".

**Why this priority**: the list is the changelog. **Independent Test**: entries on three days.

1. **Given** a feature at 09:00 and three fixes at 15:00 on one day, **Then** one group shows the feature, then "3 fixes".
2. **Given** an entry published at 23:30 UTC, **Then** it belongs to that UTC day for every viewer, whatever their timezone.
3. **Given** "Last 3 months", **Then** the figures and the days cover 90 days; with no entry, one sentence says so.
4. **Given** a narrow screen, **Then** the date stands above the day's changes.

### User Story 2 - Know what is new since my last visit (Priority: P1)

The top bar link reads "What's new" with the number of entries the person has not seen. Opening the
page marks every entry as seen; the entries unseen until then carry "New", and a divider "Seen on your
last visit" separates them from the rest.

1. **Given** two features and three fixes since the last visit, **Then** the badge reads 5; after opening the page, those five carry "New" once and the badge is gone.
2. **Given** an account created today, **Then** entries published before its creation are not counted as unseen.

### User Story 3 - Vote and comment on a change (Priority: P2)

On any entry, fixes included, the person votes up or down: pressing the chosen vote again withdraws
it, pressing the other switches it, and the text beside them reads "Thanks, noted". Opening comments
shows the public comments, any public administrator reply under them, and a form: the text, "Public,
everyone sees it" or "Private, only the team", and for a public one "Post as" their name or
"Anonymous". An anonymous comment reads "Anonymous" to everybody and "Only you can see this is yours"
to its author. A private comment is shown to its author only. The count counts visible public comments.

1. **Given** an up vote, **When** they press down, **Then** they hold one down vote only.
2. **Given** a fix in a compact list, **Then** the person reaches its own votes and comments.
3. **Given** an empty comment, **Then** it cannot be posted; another person's private or hidden comment is neither shown nor counted.

### User Story 4 - Administrators moderate and answer (Priority: P2)

"Changelog" in the admin space, with the number of comments to review on its link. Tabs: To review,
Private, Hidden, All comments, Votes. A filter by search (comment, change, name or email), type,
visibility and period. On the comment tabs, a list of changes holding matching comments, newest first;
choosing one shows its comments with the real author and account (also for anonymous ones), and beside
them its votes and its id, file and comment counts, with a link to it on What's new. On a comment:
Mark as reviewed, Reply, Hide from What's new (public comments) or Show it again. A reply to a public
comment shows under it on What's new, signed and badged "Administrator"; a reply to a private one is
emailed to its author. Replying marks the comment reviewed. The Votes tab is a table of changes with
likes, dislikes, the split and comments, sortable by most disliked, most liked, newest.

1. **Given** a new comment, **Then** it stays under To review until marked reviewed or answered; a hidden one leaves What's new until shown again.
2. **Given** a reply to a private comment, **Then** its author receives an email and nobody else sees it.

### User Story 5 - Coding agents write the entries (Priority: P2)

A repository skill named `changelog` tells a coding agent when a change earns an entry, which kinds of
change never do, and how to write one: a new Markdown file in `changelog/`, a fresh ULID never used
before, `published_at` at the current UTC time, one of the three types, an English title and
description saying what changed for the reader and why it matters, translated into every configured
locale by Sonnet subagents, in the same pull request as the code. One entry per user facing change, several when a pull request carries several.

**Acceptance**: an agent given a user facing fix adds one entry; given a typo fix, adds none.

### Edge Cases

- A duplicate id, an invalid ULID, an unknown type, a missing or malformed date: the automated check fails, and a running instance leaves that entry out rather than failing the page.
- An entry missing a locale: readers of that locale see the English text, and the check fails.
- A `link` that is not an internal path (a scheme, `//host`, a backslash, not starting with `/`): the check fails, and the entry shows without a link.
- A renamed or edited file keeps its comments, votes and seen marks; a deleted file's are kept but not shown; a future dated entry waits for its time.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: Each entry MUST be one Markdown file in `changelog/` with front matter `id` (ULID), `title`, `type`, `published_at` (UTC), and optional `area` and `link`, the body being the description.
- **FR-002**: Publishing an entry MUST need nothing but adding its file; no database change per entry.
- **FR-003**: Days MUST be UTC calendar days, newest first; within a day features and improvements by `published_at` descending, then fixes by `published_at` descending.
- **FR-004**: The period MUST default to the last 30 days, and the figures and the days listed MUST both follow it.
- **FR-005**: Seen marks, votes and comments MUST be held per person and per entry `id`; a person holds at most one vote per entry.
- **FR-006**: The unseen count MUST count entries published after the account was created and not yet seen.
- **FR-007**: The author of an anonymous comment MUST NOT be revealed to anybody but its author and administrators.
- **FR-008**: A `link` MUST be accepted only as a path inside the application.
- **FR-009**: An automated check in the test suite MUST refuse an invalid or duplicate entry file.
- **FR-010**: Every string of both screens and every entry's title and description MUST be translated into every configured locale; a missing translation falls back to English at runtime, and the automated check fails on it.
- **FR-011**: Both screens MUST meet WCAG 2.2 AA: switches and votes report their pressed state, counts on links have a spoken label ("5 changes you have not seen").
- **FR-012**: Every administrator moderation action MUST be written in the administrator log.
- **FR-013**: The `changelog` skill MUST follow the existing layout of `.claude/skills/` and list the brief's qualifying and excluded changes.
- **FR-014**: Everything What's new does MUST be doable through the API: list by period with unseen state, mark seen, vote, read and post comments (principle X).

### Key Entities

- **Changelog entry**: a file, not a row, identified by its `id`, never its file name. **Seen mark**, **Vote** (up or down), **Comment** (public or private, named or anonymous; new, reviewed or hidden), **Reply** (an administrator's, public or emailed): rows held against an entry `id`.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: A new entry appears on the page after deployment with no migration and no command run.
- **SC-002**: In every day group, no fix appears above a feature or improvement.
- **SC-003**: Renaming every file in `changelog/` and editing its text loses no comment, vote or seen mark.
- **SC-004**: Tests cover grouping, sorting, period filtering, the four figures, the unseen count and the entry check.

## Assumptions

- Every signed in person sees the same changelog, hosted and self hosted alike, each instance storing its own comments and votes; "the team" is the instance administrators.
- Ships with the entries written for this feature only; `feature-documentator` stays. Off by default on every instance: `MONICA_CHANGELOG_ENABLED=true` switches it on (2026-11-17).
