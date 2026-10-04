---
name: telescope-debugging
description: Inspect what a Laravel application actually did using the telescope:list and telescope:show Artisan commands. Use when debugging a slow page, exception, 500 response, failing queued job, N+1 or slow query, or unexpected cache miss in a project with laravel/telescope installed, even when Telescope is not mentioned.
---

Telescope records every request, job, and command as a **batch**: the entry itself and every query, cache operation, log, event, exception, and view it produced. The two commands below read this data from the terminal. Prefer them to the web UI.

### Workflow

1. **Find the entry.** Run `php artisan telescope:list request` (or `exception`, `job`, `query`, or `cache`). Choose the row by URI, class, or duration, then copy its UUID. Skip this step when you want the most recent entry.

2. **Show the batch.** Run `php artisan telescope:show <uuid>`, or `php artisan telescope:show latest:request`. The output is the entry's own detail followed by Queries, Exceptions, Cache, and Logs sections, all from the same batch.

3. **Diagnose from the flags.** In the Queries table, `DUP` marks queries with the same SQL pattern, which usually indicates an N+1. The `Source` column shows the file and line that ran the query, shortened to a basename; use `--json` for the full path. `SLOW` marks queries over the configured threshold. The Cache header shows hits, misses, and hit rate. An exception entry shows the message, code context, and stack trace.

4. **Finish** when the finding identifies a file and line or a specific query, rather than only a symptom.

### JSON output

Both commands accept `--json`. Prefer it to table output when you need exact values, want to filter results, or expect long output. `telescope:list --json` prints an array of entries. `telescope:show --json` prints `{"entry": {...}, "batch": [...]}`. Its `batch` array is in chronological order and already filtered by `--type`. When no entries match, `telescope:list --json` prints `[]` rather than a message.

Every entry has `id`, `batch_id`, `type`, `content`, `family_hash`, and `created_at`. The `content` keys match the Telescope UI for that type. For example, a query has `sql`, `time`, `slow`, `file`, and `line`, while an exception has `class`, `message`, `file`, `line`, and `trace`. Two fields are not populated by these commands: `tags` is always `[]` (use `--tag` to filter rather than reading tags from the output), and `sequence` is `null` when the entry is addressed by UUID.

```bash
# Exception class, message, and location for the latest exception
php artisan telescope:show latest:exception --json | jq '.entry.content | {class, message, file, line}'

# Slow queries in the latest request, with the file that ran them
php artisan telescope:show latest:request --json --type=query | jq '.batch[] | select(.content.slow) | {sql: .content.sql, time: .content.time, file: .content.file, line: .content.line}'

# Repeated SQL patterns in a request (N+1 candidates), most repeated first
php artisan telescope:show latest:request --json --type=query | jq '[.batch[].content.sql] | group_by(.) | map({sql: .[0], count: length}) | sort_by(-.count) | .[] | select(.count > 1)'

# Recent 500 responses
php artisan telescope:list request --json --limit=50 | jq '.[] | select(.content.response_status >= 500) | {id, uri: .content.uri, status: .content.response_status}'

# Failed jobs and their exception messages
php artisan telescope:list job --json | jq '.[] | select(.content.status == "failed") | {id, name: .content.name, error: .content.exception.message}'
```

### Reference

- `latest` and `latest:{type}` resolve to the most recent entry without a UUID lookup. Any entry type works, including `latest:query` and `latest:job`.
- `--json` on either command disables truncation, so `--full` is only needed for table output.
- `telescope:show <id> --full` disables truncation of SQL, messages, and payloads.
- `telescope:show <id> --type=query,exception` limits batch context to those types when a request produced hundreds of entries.
- `telescope:list --tag=Auth:42` filters entries for one authenticated user, because Telescope tags them with `Auth:<user id>`. `--batch=<id>` lists every entry from one request. `--before=<sequence>` pages backward using the cursor printed in the footer. `--family=<hash>` groups recurrences of one exception; read the hash from the `family_hash` field in `--json` output.
- Run either command with `--help` for the full option list and valid entry types.
