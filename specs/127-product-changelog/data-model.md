# Data model: Product changelog

## Entry file: `changelog/<date>-<ulid>-<slug>.md`

```markdown
---
id: 01KB1J7XY8M4NPQ2RST6VW9ABC
title: Birthdays show the age somebody turns
type: feature
area: Contacts
published_at: 2026-11-17T10:00:00Z
link: /vaults
---
Next to a birthday on a contact page, Monica now writes how old the person will be.
```

| Key | Rule |
| --- | --- |
| `id` | required, 26 character Crockford ULID, unique across the folder, never changed |
| `title` | required, one line |
| `type` | required, `feature`, `improvement` or `fix` (`ChangelogEntryTypeEnum`) |
| `published_at` | required, ISO 8601 in UTC (`Z`) |
| `area` | optional, one line, English, translated like the title |
| `link` | optional, internal path (research R8) |
| body | required, Markdown, rendered with `KnowledgeMarkdown::toHtml()` |

The file name carries no meaning. In memory it is `App\Helpers\ChangelogEntry` (readonly properties)
read by `App\Helpers\ChangelogEntries` (`all()`, `visible()`, `find(string $id)`, `problems()`).
Visible means valid and `published_at <= now()`.

## Tables (one migration each, never one per entry)

| Table | Columns | Keys |
| --- | --- | --- |
| `changelog_seen_entries` | `user_id`, `entry_id` (char 26), `created_at` | unique (`user_id`, `entry_id`) |
| `changelog_votes` | `user_id`, `entry_id`, `direction` (`up`/`down`), timestamps | unique (`user_id`, `entry_id`) |
| `changelog_comments` | `uuid`, `user_id`, `entry_id`, `body` (text), `is_private`, `is_anonymous`, `status`, `reviewed_at`, timestamps | index (`entry_id`, `status`) |
| `changelog_comment_replies` | `changelog_comment_id`, `author_user_id` (nullable, null on delete), `author_name`, `body`, `is_public`, timestamps | |

`user_id` is `foreignIdFor(User::class)->constrained()->cascadeOnDelete()`: closing an account removes
its seen marks, votes and comments. `entry_id` is a plain string, never a foreign key: the entry is a file.

## Comment states (`ChangelogCommentStatusEnum`)

```text
new ──Mark as reviewed / Reply──▶ reviewed ──Hide──▶ hidden ──Show it again──▶ reviewed
new ──Hide──▶ hidden
```

Only a public comment can be hidden. `is_anonymous` is false whenever `is_private` is true. What's new
shows a comment when it is public and not hidden, or when it is the reader's own.

## Models

`ChangelogSeenEntry`, `ChangelogVote`, `ChangelogComment` (`hasMany` replies, `belongsTo` user),
`ChangelogCommentReply`, each with a factory. None uses `ResolvesModelInAccount`: a comment is reached
by its `uuid` in the admin space only, behind the `administrator` middleware.
