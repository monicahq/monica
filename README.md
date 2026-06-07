# Monica Import Notes

This README explains the Monica import work in plain language.

If you are reviewing the project quickly, start with:
- what the feature does
- how it is built
- how to run it locally
- what tradeoffs were made

---

## Legacy Monica Import Notes

The rest of this README is the more technical background for the Monica import work.
It is kept here so reviewers can still see the architecture, API details, and testing notes.

---

## Problem Analysis

Before designing the solution, I searched the selected Monica branch for `csv`, `vcard`, `vcf`, `contact import`, and related import files.

I could not identify an active CSV/VCard contact import implementation, so this solution implements a new import subsystem following Monica’s existing Laravel/domain conventions.

### What the selected Monica branch showed

The selected branch did not appear to contain an active CSV/VCard importer that matched the assignment brief.

The import solution in this commit is therefore a new subsystem rather than a rewrite of an existing one.

### Current flow before the redesign

No active CSV/VCard contact-import flow was identified in the selected branch, so there was no existing end-to-end pipeline to extend.

This submission therefore adds a new import subsystem instead of patching an active importer.

### Problems with that flow

| Problem | Why it matters |
|---------|----------------|
| Synchronous processing during the request | Large files can time out |
| No progress tracking | Users cannot tell how far the import has gone |
| No row-level error isolation | One bad row can stop everything |
| No import history | Past imports are hard to review or retry |
| No duplicate detection | The same file can be imported twice by mistake |
| No observability | Failed or stuck imports are easy to miss |
| Memory usage grows with the file | Large CSV files become expensive to process |

---

## 🏗️ Redesigned Architecture

The new import system uses a **two-stage asynchronous pipeline** to decouple file uploads from heavy database writes:

```
Client Upload (POST /api/import)
        ↓
ImportController — validates file, stores to disk, creates ImportJob (status: pending)
        ↓                ↓
  HTTP 201 <500ms    Dispatches ProcessImportJob
                          ↓
              [Queue Worker] ProcessImportJob
              - Reads CSV headers, strips UTF-8 BOM
              - Maps column names to normalized fields
              - Counts total rows
              - Dispatches batches of 50 → ProcessImportBatch jobs
              - Deletes CSV file from disk (cleanup)
                          ↓
              [Queue Workers] ProcessImportBatch × N
              - Per-row validation and error isolation
              - Creates contacts via existing CreateContact service
              - Atomically updates processed_rows/failed_rows (lockForUpdate)
              - Marks import completed/failed when all rows accounted for
```

### Component Responsibilities

| Component | File | Role |
|-----------|------|------|
| `ImportController` | `app/Http/Controllers/Api/ImportController.php` | Thin HTTP layer, delegates to service |
| `StoreImportRequest` | `app/Http/Requests/StoreImportRequest.php` | Validates `vault_id` + `file` inputs |
| `ImportJobResource` | `app/Http/Resources/ImportJobResource.php` | Consistent JSON API output format |
| `CreateImportJob` | `app/Domains/Contact/Import/Services/CreateImportJob.php` | Creates DB record, checks duplicates, dispatches |
| `ProcessImportJob` | `app/Jobs/ProcessImportJob.php` | CSV orchestrator — parses, chunks, dispatches batches |
| `ProcessImportBatch` | `app/Jobs/ProcessImportBatch.php` | Worker — creates contacts, tracks per-row errors |
| `RecoverImports` | `app/Console/Commands/RecoverImports.php` | Artisan command to recover stuck imports |
| `ImportJob` | `app/Models/ImportJob.php` | Eloquent model with `progress_pct` accessor |

---

## 💾 Database Schema — `import_jobs` Table

```sql
CREATE TABLE import_jobs (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    account_id      CHAR(36) NOT NULL,   -- Scoping to account (multi-tenancy / sharding key)
    user_id         CHAR(36) NOT NULL,   -- Audit: who initiated the import
    vault_id        CHAR(36) NOT NULL,   -- Contacts belong to a vault
    filename        VARCHAR(255),        -- Original filename shown in the UI
    file_path       VARCHAR(255),        -- Temporary path on local disk (deleted after parsing)
    file_hash       VARCHAR(255),        -- SHA-256 hash for duplicate detection (indexed)
    total_rows      INT DEFAULT 0,       -- Set by orchestrator after CSV parsing
    processed_rows  INT DEFAULT 0,       -- Incremented atomically by batch workers
    failed_rows     INT DEFAULT 0,       -- Incremented atomically by batch workers
    status          VARCHAR(20),         -- pending | processing | cancelling | cancelled | completed | failed
    started_at      TIMESTAMP NULL,      -- When ProcessImportJob began
    cancelled_at    TIMESTAMP NULL,      -- When cancellation was requested or finalized
    last_heartbeat_at TIMESTAMP NULL,    -- Most recent activity from orchestrator or batch workers
    completed_at    TIMESTAMP NULL,      -- When all batches finished (or cancelled/failed)
    created_at      TIMESTAMP,
    updated_at      TIMESTAMP,
    INDEX (file_hash),
    INDEX (status)
);
```

**Additional columns beyond the assignment minimum — Justification:**

| Column | Reason Added |
|--------|-------------|
| `vault_id` | Contacts in Monica are scoped to vaults; required for authorization and contact creation |
| `file_path` | Required to read the file in the orchestrator job |
| `file_hash` | SHA-256 hash enables O(1) duplicate detection without re-reading the file |
| `cancelled_at` | Records when cancellation was requested or finalized |
| `last_heartbeat_at` | Lets recovery jobs distinguish active work from stuck work |

### `import_errors` Table

```sql
CREATE TABLE import_errors (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    import_job_id   BIGINT UNSIGNED NOT NULL,
    row_number      INT UNSIGNED NOT NULL,
    row_data        JSON NULL,
    error_message   TEXT NOT NULL,
    created_at      TIMESTAMP,
    updated_at      TIMESTAMP,
    INDEX (import_job_id),
    INDEX (row_number)
);
```

This table stores each failed row separately so pagination, recovery, and CSV export can all read from the same source of truth.

---

## 🔑 API Endpoints

| Method | Path | Description |
|--------|------|-------------|
| `POST` | `/api/import` | Upload a CSV file for import |
| `GET` | `/api/import` | List recent imports (paginated, `?page=1&per_page=10`) |
| `GET` | `/api/import/:id` | Get detailed status: progress %, ETA, errors so far |
| `POST` | `/api/import/:id/cancel` | Cancel a running import |
| `GET` | `/api/import/:id/errors` | Paginated list of per-row errors |
| `GET` | `/api/import/:id/errors.csv` | Download error CSV reconstructed from `import_errors.row_data` plus `error_message` |

All endpoints require `Authorization: Bearer <sanctum-token>`.

### Sample Responses

**`POST /api/import` → 201**
```json
{
  "data": {
    "id": 1,
    "filename": "contacts.csv",
    "total_rows": 0,
    "processed_rows": 0,
    "failed_rows": 0,
    "status": "pending",
    "progress_pct": 0,
    "created_at": "2026-06-03T12:00:00+00:00"
  }
}
```
> **Note:** `total_rows` is `0` immediately after upload because CSV row counting happens asynchronously in the orchestrator job. It is updated within seconds. This is an intentional architectural trade-off documented in ADR 3.

**`GET /api/import/1` → 200**
```json
{
  "data": {
    "id": 1,
    "filename": "contacts.csv",
    "total_rows": 500,
    "processed_rows": 320,
    "failed_rows": 2,
    "status": "processing",
    "progress_pct": 64.4,
    "errors": [
      { "row": 42, "message": "Invalid email: 'not-an-email'" },
      { "row": 87, "message": "Missing required field: name" }
    ],
    "started_at": "2026-06-03T12:00:05+00:00",
    "estimated_remaining_sec": 45,
    "completed_at": null,
    "created_at": "2026-06-03T12:00:00+00:00"
  }
}
```

**`GET /api/import?page=1&per_page=10` → 200**
```json
{
  "data": [
    {
      "id": 1,
      "filename": "contacts.csv",
      "total_rows": 500,
      "processed_rows": 500,
      "failed_rows": 2,
      "status": "completed",
      "progress_pct": 100,
      "created_at": "2026-06-03T12:00:00+00:00"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 10,
    "total": 1,
    "last_page": 1
  }
}
```

---

## 💾 Architecture Decision Records (ADRs)

### ADR 1: Database vs Redis for Progress Tracking

- **Context:** Progress counters (`processed_rows`, `failed_rows`) are updated concurrently by multiple queue workers.
- **Options Considered:**
  1. **Redis Counters:** `INCR` is atomic and extremely fast. Use `HSET import:<id> processed N`.
  2. **Database with row locks:** `SELECT ... FOR UPDATE` inside a transaction ensures ACID guarantees.
  3. **Append-only event log:** Each batch appends a row; a query aggregates totals.
- **Decision: Database (MySQL) with `lockForUpdate()`.**
- **Rationale:**
  - Redis can lose data under memory pressure (eviction) or before an AOF fsync — causing phantom progress.
  - Since an `import_jobs` record must exist in MySQL for audit history anyway, using a double-write to Redis adds complexity with no clear benefit at this scale.
  - `lockForUpdate()` prevents the TOCTOU race when two batches finish simultaneously and both try to set `status = completed`.
  - At 10x scale (hundreds of concurrent imports), switching to Redis + periodic DB sync or an event-sourced approach would be justified — documented as a known scaling path.

### ADR 2: Batch Size — 50 Contacts per Batch

- **Context:** Choosing batch size controls the memory/throughput/latency tradeoff.
- **Options Considered:**
  1. **1 contact per job:** Maximum parallelism, but extreme queue overhead (10k jobs for 10k contacts).
  2. **50 contacts per job:** Balanced. Each job runs in ~1–2s, low memory, moderate parallelism.
  3. **500 contacts per job:** Fewer jobs, but high memory usage, long transaction locks, risk of timeout.
- **Decision: 50 contacts per batch.**
- **Rationale:** Keeps individual job memory under ~10MB, stays well within default `max_execution_time`, and produces granular enough progress updates that the UI feels responsive. Batch size can be made configurable via `config/import.php` in a future iteration.

### ADR 3: total_rows is 0 on POST /api/import Response

- **Context:** The assignment sample output shows `total_rows: 500` immediately in the upload response. But to count rows, we must read and parse the CSV — which can take seconds for large files.
- **Decision:** Return `total_rows: 0` on the upload response. The orchestrator updates it within seconds.
- **Rationale:** The HTTP endpoint must respond in `<500ms`. Reading a 50k-row CSV synchronously to count rows would violate this. Clients should poll `GET /api/import/:id` to get the live count.
- **Alternative Considered:** Read only the first N bytes to estimate row count — rejected because it would be inaccurate and still adds latency.

### ADR 4: import_errors Table vs JSON Error Blob

- **Context:** The import workflow needs paginated errors, error CSV downloads, and recovery records that are easy to query.
- **Options Considered:**
  1. **Single JSON column on `import_jobs`:** Simple at first, but pagination, indexing, and CSV export all become awkward as the failure list grows.
  2. **Separate `import_errors` table:** Each failed row is persisted independently, so filtering, paging, and exports all read from one normalized source.
  3. **Hybrid approach:** Keep both JSON and a table.
- **Decision: Separate `import_errors` table.**
- **Rationale:** This keeps the error path queryable and avoids rewriting a large JSON blob on every failure. It also makes the error CSV a direct projection of stored row data plus the error message.

---

## 🔒 Concurrency, Idempotency & Recovery

### 1. Duplicate Upload Prevention

- **Mechanism:** SHA-256 hash of the uploaded file content is computed before storage.
- **Check:** If a file with the same hash exists for the same vault in the last 24 hours → `409 Conflict`.
- **Why SHA-256:** Content-addressable deduplication is immune to filename or filesize spoofing. Two differently-named files with the same content are correctly identified as duplicates.
- **Trade-off:** Hashing is O(file size) — for a 100MB CSV this adds ~50ms. Acceptable since upload response time is still well under 500ms.

### 2. Stuck Import Recovery

- **Problem:** A queue worker can crash mid-job (OOM, server restart, DB disconnect). The job can remain `processing` indefinitely with no signal.
- **Solution:** `php artisan monica:recover-imports` — scheduled hourly via `routes/console.php`:
  - Queries for imports where `status = 'processing' AND last_heartbeat_at < NOW() - INTERVAL 30 MINUTE`
  - Falls back to `started_at` when `last_heartbeat_at` is null
  - Marks them `failed`, stores a row in `import_errors`, and sets `completed_at`
- **Alerting:** Recovered imports can trigger notifications (see Observability section).

### 3. Graceful Cancellation

When `POST /api/import/:id/cancel` is called:
1. If the job has not started yet, it is marked `cancelled` immediately.
2. If the job is already processing, the status moves to `cancelling`.
3. Running batches check the status regularly, stop early, and finalize the job as `cancelled` once they are done unwinding.

This keeps the cancellation flow honest: `processing → cancelling → cancelled`.

---

## 📊 Observability & Alerting

### Metrics to Track

| Metric | How to Collect |
|--------|---------------|
| Imports started / hour | Count `import_jobs` records created |
| Imports completed / failed / cancelled | Count by status |
| Avg processing time (sec/row) | `(completed_at - started_at) / total_rows` |
| Batch failure rate | `failed_rows / total_rows` across recent imports |
| Stuck imports count | Query below |
| File upload size distribution | Log at upload time |

### SQL: Detect Stuck Imports (> 30 minutes)

```sql
SELECT
    id,
    account_id,
    user_id,
    filename,
    total_rows,
    processed_rows,
    failed_rows,
    status,
    started_at,
    TIMESTAMPDIFF(MINUTE, started_at, NOW()) AS minutes_stuck
FROM
    import_jobs
WHERE
    status = 'processing'
    AND started_at <= NOW() - INTERVAL 30 MINUTE
ORDER BY
    started_at ASC;
```

### SQL: Import Failure Rate Alert (Last 1 Hour)

```sql
SELECT
    COUNT(*) AS total_imports,
    SUM(total_rows) AS total_rows_processed,
    SUM(failed_rows) AS total_failed_rows,
    ROUND(SUM(failed_rows) / NULLIF(SUM(total_rows), 0) * 100, 2) AS failure_rate_pct
FROM
    import_jobs
WHERE
    completed_at >= NOW() - INTERVAL 1 HOUR
    AND status IN ('completed', 'failed');
```

**Alert Rule:** If `failure_rate_pct > 20.0`, trigger a PagerDuty/Slack alert:
> ⚠️ Import failure rate is **{failure_rate_pct}%** in the last hour — exceeds 20% threshold. Investigate `import_jobs` table.

### Prometheus Metrics (Proposed)

If the team adopts Prometheus, emit these counters from queue job callbacks:

```
monica_import_jobs_total{status="completed"} 42
monica_import_jobs_total{status="failed"} 3
monica_import_rows_processed_total 18500
monica_import_rows_failed_total 130
monica_import_batch_duration_seconds_histogram
```

---

## 🔄 Production Readiness Notes

### Rolling Back the Feature

The entire import feature is additive:
- New table: `import_jobs` (rollback via `down()` in the migration)
- New queue jobs registered via `ShouldQueue` — removing the dispatch call in `CreateImportJob` instantly disables the feature
- New routes under `/api/import` — can be feature-flagged via a middleware gate

### Debugging a Stuck Import

1. Run `php artisan monica:recover-imports` to immediately recover.
2. Inspect the `import_errors` table for the stuck job — the last logged error shows where processing stopped.
3. Check Laravel queue logs: `storage/logs/laravel.log` for `Import row N failed:` entries.
4. Verify queue workers are running: `php artisan queue:status` or check Horizon dashboard.

### What Happens at 10x Scale (Millions of Imports)

| Challenge | Mitigation |
|-----------|-----------|
| `import_jobs` table grows large | Add `created_at` index; archive/prune records > 90 days |
| `lockForUpdate()` contention | Shard progress updates via Redis INCR; batch-write to DB every N rows |
| File storage fills up | Move CSV storage to S3/GCS with lifecycle policies (presigned upload URLs) |
| Single queue overwhelmed | Dedicated `imports` queue with priority; Horizon auto-scaling |
| `import_errors` table grows huge | Cap stored errors at 1000 per import; archive or truncate older rows |

---

## 🧪 Running the Test Suite

The test suite uses an in-memory SQLite database and covers the complete import lifecycle.

### Prerequisites

Ensure your Docker/Sail environment is running:
```bash
./vendor/bin/sail up -d
```

### Run All Tests

```bash
./vendor/bin/sail test
```

### Run Only Import Tests (Faster)

```bash
./vendor/bin/sail test --filter=ImportTest
```

### Without Sail (direct PHP)

```bash
php artisan test --filter=ImportTest
```

### Included Import Tests (16 total)

| Test | What It Covers |
|------|---------------|
| `test_submitting_import_requires_authenticated_user` | Auth middleware blocks unauthenticated requests |
| `test_submitting_import_requires_valid_vault` | 404 when vault doesn't belong to user |
| `test_submitting_import_validates_file` | 422 when file is missing or wrong mime type |
| `test_submitting_valid_import_succeeds_and_dispatches_job` | 201 response, DB record created, job dispatched |
| `test_duplicate_upload_is_prevented` | 409 on second upload of same file content (SHA-256 check) |
| `test_process_import_job_counts_rows_and_dispatches_batches` | Orchestrator counts rows, dispatches batch jobs, deletes CSV |
| `test_process_import_batch_creates_contacts_and_isolates_errors` | Batch creates valid contacts, skips invalid, records errors |
| `test_get_import_progress` | Progress endpoint returns correct progress_pct and estimated_remaining_sec |
| `test_cancel_import_job` | Cancel sets status, subsequent batch jobs exit without creating contacts |
| `test_download_error_csv` | Streamed CSV response with original row data and error column |
| `test_stuck_import_recovery_command` | Artisan command marks 30min+ stuck imports as failed; ignores active ones |
| `test_index_filters_imports_by_vault_id` | Index ?vault_id= filter correctly scopes results |
| `test_process_import_job_handles_utf8_bom` | UTF-8 BOM stripped from CSV header before parsing |
| `test_submitting_import_deletes_file_on_exception` | File cleaned up from disk on 409 duplicate rejection |
| `test_errors_endpoint_returns_paginated_errors` | GET /api/import/:id/errors returns paginated errors with meta |
| `test_index_returns_pagination_metadata` | GET /api/import returns correct meta.current_page, per_page, total, last_page |
