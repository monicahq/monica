# Import System Design & Analysis

## Architecture Decision Records

### ADR-001: Use Database for Progress Tracking (Not Redis)

**Status:** Accepted

**Context:** The import system needs to track per-import progress (processed, failed, skipped rows) so the frontend can display a progress bar via polling. The `ProcessImportBatch` jobs increment counters as they process each chunk, and the frontend polls `/progress` every 2 seconds.

**Considered options:**

| Option                                              | Description                                           |
| --------------------------------------------------- | ----------------------------------------------------- |
| **DB columns** (`import_jobs.processed_rows`, etc.) | Increment counters directly on the import row         |
| **Redis keys**                                      | Store progress in Redis, sync to DB on completion     |
| **Laravel batch progress**                          | Use `Bus::findBatch()->progress()` as the sole source |

**Decision:** Store progress directly in the `import_jobs` database table.

**Rationale:**

- **Durability:** Progress survives worker crashes, queue restarts, and deploys. Redis loss (even with persistence) would reset progress to zero.
- **Consistency with cancellation:** The mid-loop cancellation check (`$importJob->fresh()->status === 'cancelled'`) reads the same DB row that progress is written to — no cross-system coordination needed.
- **Error CSV generation:** The `errors` JSON column stores per-row error details inline. Keeping this in DB avoids dual-write complexity with Redis.
- **Polling frequency is low (2s):** The DB handles one read every 2 seconds per active import trivially. Redis would only help at sub-second polling (< 100ms), which isn't needed here.
- **Zero infrastructure:** No new service to deploy, monitor, or back up.

**Consequences:**

- Each `ProcessImportBatch` job writes to the DB per chunk (50 records). This is acceptable since chunks are coarse-grained.
- If sub-second real-time progress is ever needed, add Redis as a read cache in front of the DB (cache progress row with a short TTL, fall back to DB query on miss).

---

### ADR-002: Batch Size of 50 Records Per Chunk

**Status:** Accepted

**Context:** Imported files can contain thousands of records. Processing each record individually would create too many jobs, overwhelming the queue. Processing all records in a single job would exceed execution time limits and prevent cancellation granularity. A batch size must be chosen.

**Considered options:**

| Size            | Pros                                          | Cons                                                              |
| --------------- | --------------------------------------------- | ----------------------------------------------------------------- |
| **10**          | Fine-grained cancellation, low per-job memory | Too many jobs (1000 records = 100 jobs), queue overhead           |
| **50 (chosen)** | Good balance of job count vs. per-job runtime | —                                                                 |
| **100**         | Fewer jobs                                    | Higher per-job memory; a single slow record delays 99 others      |
| **500**         | Minimal jobs                                  | Jobs may hit execution time limits; poor cancellation granularity |

**Decision:** Chunk records into groups of 50, dispatching one `ProcessImportBatch` job per chunk.

**Rationale:**

- **Execution time:** Each record calls `ImportVCard::execute()`, which runs up to 7 sub-importers. At 50 records, a chunk completes in under 30 seconds in production — well within the default 60s queue timeout.
- **Cancellation granularity:** If a user cancels, at most 50 unprocessed records remain in the currently-executing job (plus any queued jobs that `Bus::cancel()` prevents from starting).
- **Queue pressure:** A file with 10,000 records produces 200 jobs — manageable for Laravel's database queue.
- **Laravel batch limits:** `Bus::batch()` has no hard limit on job count, but batches with > 500 jobs cause slow `findBatch()` queries due to the `job_batches` table scan. 200 jobs per batch is safe.
- **Memory:** Each chunk holds 50 serialized vCard strings or CSV arrays in memory — negligible (< 1 MB).

**Consequences:**

- If average per-record processing time increases significantly (e.g., more sub-importers added), the batch size may need to be reduced.
- The chunk size is hardcoded at 50. Future work could make it configurable per-queue or per-file-size.

---

## 1. Current State

Monica has **two distinct import pipelines**: a **CardDAV protocol pipeline** (legacy, vCard-only) and a new **browser-based upload pipeline** (vCard + CSV).

### Pipeline A: DAV Protocol (CardDAV Sync)

```
DAV Client PUT /dav/addressbooks/.../{uid}.vcf
  → CardDAVBackend::updateCard()
  → UpdateVCard job (queue: 'high')
  → ImportVCard::execute()
  → Ordered pipeline of 7 importers (ImportContact, ImportAddress, etc.)
  → Contact created/updated in Monica
```

### Pipeline B: Browser Upload (Web UI → Queue → ImportVCard)

```
User Uploads .vcf or .csv file
  → ContactImportController::store()
  → ImportFile service (extracted shared logic)
      → Parse file (vCard splitter or CSV fgetcsv streaming)
      → Create ImportJob (status: pending)
      → Chunk records into groups of 50
      → Bus::batch() of ProcessImportBatch jobs (queue: 'imports')
          → Each job calls ImportVCard::execute() per record
          → Tracks processed/failed/skipped on ImportJob
  → Frontend polls /progress every 2s
  → Batch then()/catch() marks ImportJob completed/failed
```

### Pipeline C: REST API (Sanctum-auth → Queue → ImportVCard)

```
Client POST /api/import (multipart: file + vault_id)
  → ContactImportController::store() (API)
  → ImportFile service (same shared service as Pipeline B)
      → Parse CSV file (fgetcsv streaming)
      → Create ImportJob (status: pending)
      → Chunk records into groups of 50
      → Bus::batch() of ProcessImportBatch jobs (queue: 'imports')
  → Returns ImportJobResource (201)

Client GET /api/import/{id}
  → ContactImportController::show()
  → Syncs batch status via Bus::findBatch()
  → Returns ImportJobResource with progress_pct, errors, estimated_remaining_sec
```

| Component                           | Status     | Location                                                                        |
| ----------------------------------- | ---------- | ------------------------------------------------------------------------------- |
| vCard parser (`ReadVObject`)        | ✅ Done    | `app/Domains/Contact/Dav/Services/ReadVObject.php`                              |
| Import orchestrator (`ImportVCard`) | ✅ Done    | `app/Domains/Contact/Dav/Services/ImportVCard.php`                              |
| 7 concrete vCard importers          | ✅ Done    | `app/Domains/Contact/*/Dav/Import*.php`                                         |
| CardDAV protocol backend            | ✅ Done    | `app/Domains/Contact/Dav/Web/Backend/CardDAV/`                                  |
| Web upload controller               | ✅ Done    | `app/Domains/Contact/ManageContact/Web/Controllers/ContactImportController.php` |
| API upload controller               | ✅ Done    | `app/Domains/Contact/ManageContact/Api/Controllers/ContactImportController.php` |
| Import file service (shared logic)  | ✅ Done    | `app/Domains/Contact/ManageContact/Services/ImportFile.php`                     |
| Upload import batch job             | ✅ Done    | `app/Domains/Contact/ManageContact/Jobs/ProcessImportBatch.php`                 |
| CSV → vCard converter               | ✅ Done    | `app/Domains/Contact/ManageContact/Services/CsvToVCard.php`                     |
| Import tracking model               | ✅ Done    | `app/Models/ImportJob.php` (table: `import_jobs`)                               |
| API resource                        | ✅ Done    | `app/Http/Resources/ImportJobResource.php`                                      |
| Import progress view                | ✅ Done    | `resources/js/Pages/Vault/Contact/Import.vue`                                   |
| Import view helper                  | ✅ Done    | `app/Domains/Contact/ManageContact/Web/ViewHelpers/ContactImportViewHelper.php` |
| Web routes                          | ✅ Done    | `routes/web.php` (6 import routes)                                              |
| API routes                          | ✅ Done    | `routes/api.php` (2 import routes)                                              |
| Error CSV download                  | ✅ Done    | `ContactImportController::errorsCsv()`                                          |
| Rollback/undo                       | ❌ Missing | Not implemented                                                                 |
| Import history page                 | ❌ Missing | Not implemented                                                                 |
| ZIP file support                    | ❌ Missing | Not implemented                                                                 |
| Duplicate file detection            | ✅ Done    | SHA-256 content hash per vault                                                  |

## 2. Actual Implementation vs. Original Design

### 2.1 Model: `ImportJob` instead of `Import`

**Designed:** `Import` model with `imports` table  
**Implemented:** `ImportJob` model with `import_jobs` table

The table schema closely follows the design, with these differences:

- `user_id` instead of `author_id`
- Additional `error_log` (text) and `errors` (JSON) columns for structured error tracking

### 2.2 Batch Job: `ProcessImportBatch` instead of `ImportContactFromUpload`

**Designed:** `ImportContactFromUpload` job wrapping `ImportVCard`  
**Implemented:** `ProcessImportBatch` (extends `QueuableService`, implements `Batchable`)

Processes chunks of 50 records at a time. Supports two chunk types:

- `vcard_chunk`: array of serialized vCard strings
- `csv_chunk`: array of `{number, data}` items (row number + associative array)

Handles batch cancellation checks, row-level error tracking, and structured error storage.

### 2.3 CSV Service: `CsvToVCard` instead of `ImportCSV`

**Designed:** `ImportCSV` service with column auto-detection, column mapping UI, and preview step  
**Implemented:** `CsvToVCard` static utility class

Converts CSV rows to vCard format with flexible column alias detection:

| CSV Column Aliases                                              | vCard Property |
| --------------------------------------------------------------- | -------------- |
| `first_name`, `firstname`, `given_name`, `givenname`            | `N;FN`         |
| `last_name`, `lastname`, `family_name`, `familyname`, `surname` | `N;FN`         |
| `middle_name`, `middlename`, `middle`                           | `N`            |
| `email`, `email_address`, `emailaddress`                        | `EMAIL`        |
| `phone`, `telephone`, `phone_number`, `phonenumber`, `tel`      | `TEL`          |
| `street`, `address`, `address1`, `address_line_1`               | `ADR`          |
| `city`                                                          | `ADR`          |
| `state`, `province`                                             | `ADR`          |
| `zip`, `postal_code`, `postalcode`, `postcode`                  | `ADR`          |
| `country`                                                       | `ADR`          |
| `birthday`, `birth_date`, `birthdate`, `dob`, `date_of_birth`   | `BDAY`         |
| `company`, `organization`, `org`                                | `ORG`          |
| `job_title`, `jobtitle`, `title`                                | `TITLE`        |
| `notes`, `note`                                                 | `NOTE`         |

Validation requires at least one of `first_name`/`last_name`, validates email format, and checks phone number has ≥5 digits.

**Note:** Column mapping UI and preview step were deferred. CSV columns are auto-detected by alias matching.

### 2.4 Routes

**Actual implemented routes** (in `routes/web.php`):

```php
Route::get('import', [ContactImportController::class, 'create'])->name('contact.import.create');
Route::post('import', [ContactImportController::class, 'store'])->name('contact.import.store');
Route::get('import/{importJob}/errors.csv', [ContactImportController::class, 'errorsCsv'])->name('contact.import.errors.csv');
Route::get('import/{importJob}', [ContactImportController::class, 'show'])->name('contact.import.show');
Route::get('import/{importJob}/progress', [ContactImportController::class, 'progress'])->name('contact.import.progress');
Route::delete('import/{importJob}', [ContactImportController::class, 'cancel'])->name('contact.import.cancel');
```

**Differences from design:**

- Added `errors.csv` (download error CSV) — not in original design
- Added `show` (view import details) — not in original design
- Removed `destroy` (delete import) — not implemented
- Removed `rollback` (undo import) — not implemented

### 2.5 Shared Service: `ImportFile`

**Extracted from:** Duplicate code in web and API controllers  
**Implemented:** `ImportFile` service (extends `BaseService`, implements `ServiceInterface`)

Consolidates CSV/vCard parsing, `ImportJob` creation, chunking, and batch dispatch into a single service:

| Logic                                                  | Previously Duplicated In              | Now Lives In                  |
| ------------------------------------------------------ | ------------------------------------- | ----------------------------- |
| CSV parsing (`fgetcsv` loop, header/row detection)     | Web + API controllers                 | `ImportFile::parseCsv()`      |
| vCard parsing (`Sabre\VObject\Splitter\VCard`)         | Web controller only                   | `ImportFile::parseVcf()`      |
| `ImportJob::create(...)`                               | Web `processVcf` + `processCsv` + API | `ImportFile::execute()`       |
| Chunking + batch dispatch (`Bus::batch`, `then/catch`) | `dispatchBatch` (web) + API inline    | `ImportFile::dispatchBatch()` |

Uses the standard service permissions (`author_must_belong_to_account`, `vault_must_belong_to_account`, `author_must_be_vault_editor`). Throws `\InvalidArgumentException` for user-facing errors (e.g., empty CSV, no vCards), which each controller catches and formats per its context.

### 2.6 Frontend (`Import.vue`)

**Implemented:** Single-file Vue 3 component with three states:

1. **Upload form** — file picker (`.vcf, .vcard, .csv`), upload button, error display
2. **Progress** — polling every 2s with progress bar, processed/failed/skipped counts
3. **Completion** — summary grid (Imported/Skipped/Failed), error log display, error CSV download link

Uses `lucide-vue-next` icons, Inertia.js for routing, and `laravel-vue-i18n` for translations.

## 3. Database Schema

### `import_jobs` table

```sql
CREATE TABLE import_jobs (
    id CHAR(36) PRIMARY KEY,                   -- UUID
    account_id CHAR(36) NOT NULL,
    vault_id CHAR(36) NOT NULL,
    user_id CHAR(36) NOT NULL,                 -- was author_id in design
    file_path VARCHAR(500) NOT NULL,
    original_filename VARCHAR(255),
    file_size INT UNSIGNED,
    file_type ENUM('vcard', 'csv') NOT NULL,   -- 'csv' added via migration 2023_08_01_000002
    status ENUM('pending','processing','completed','failed','cancelled') DEFAULT 'pending',
    total_rows INT UNSIGNED DEFAULT 0,
    processed_rows INT UNSIGNED DEFAULT 0,
    failed_rows INT UNSIGNED DEFAULT 0,
    skipped_rows INT UNSIGNED DEFAULT 0,
    batch_id VARCHAR(255) NULL,                 -- Laravel batch UUID
    contact_ids_created JSON NULL,             -- for potential rollback
    error_log TEXT NULL,                       -- accumulated error messages
    errors JSON NULL,                          -- structured errors: [{row, message}]  (added via 2025_08_09 migration)
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (account_id) REFERENCES accounts(id),
    FOREIGN KEY (vault_id) REFERENCES vaults(id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX (account_id, status),
    INDEX (vault_id, status)
);
```

### Migrations (in order)

| Migration           | Change                                                         |
| ------------------- | -------------------------------------------------------------- |
| `2023_08_01_000001` | Create `import_jobs` table (file_type initially only `vcard`)  |
| `2023_08_01_000002` | Add `csv` to file_type ENUM                                    |
| `2025_08_09_223221` | Add `errors` JSON column for structured per-row error tracking |

## 4. Import Flow Details

### 4.1 File Upload (Both Web and API Controllers)

1. Validates file: `mimes:vcf,vcard,csv,txt`, max `51200` KB
2. Computes SHA-256 hash for duplicate detection
3. Checks `import_jobs` for existing `completed` import with same hash in same vault
4. Stores file via `Storage::store('imports')`
5. Delegates to `ImportFile` service for all subsequent processing

### 4.2 ImportFile Service (`ImportFile::execute()`)

1. Validates input data via `BaseService::validateRules()` (account, author, vault, file metadata)
2. Routes to `parseCsv()` or `parseVcf()` based on `file_type`

### 4.3 vCard Parsing (`ImportFile::parseVcf()`)

1. Reads stored file content from storage
2. Uses `Sabre\VObject\Splitter\VCard` to split multi-vCard files
3. Serializes each vCard to string
4. Throws `\InvalidArgumentException` if no vCards found

### 4.4 CSV Parsing (`ImportFile::parseCsv()`)

1. Opens stored file as stream
2. Reads header row from first line
3. Reads data rows, combines with header via `array_combine()`
4. Indexes rows with line numbers `{number, data}`
5. Throws `\InvalidArgumentException` if no header or no data rows

### 4.5 Batch Dispatch (`ImportFile::dispatchBatch()`)

1. Creates `ImportJob` with status `pending` and `total_rows` set
2. Chunks records into groups of 50
3. Dispatches `ProcessImportBatch` jobs for each chunk via `Bus::batch()`

### 4.6 Batch Processing (`ProcessImportBatch::execute`)

1. Skips if `ImportJob` status is `cancelled`
2. Sets status to `processing` if not already
3. For `vcard_chunk`:
   - Calls `ImportVCard::execute()` with `BEHAVIOUR_ADD`
   - Increments `processed_rows` on success
   - On exception: increments `failed_rows`, appends to `error_log`
4. For `csv_chunk`:
   - First validates via `CsvToVCard::validate()` — skips with error if invalid
   - Converts via `CsvToVCard::convert()` → vCard string
   - Then same as vCard path

### 4.7 Progress Polling (Web: `ContactImportController::progress` / API: `ContactImportController::show`)

1. If status is `processing`, checks via `Bus::findBatch()`:
   - Batch cancelled → mark import `cancelled`
   - Batch finished without failures → mark import `completed` (or `failed` if 0 processed)
   - Batch has failures → mark import `failed`
2. Web returns JSON: `status, total_rows, processed_rows, failed_rows, skipped_rows, error_log, errors, completed_at, errors_csv_url`
3. API returns `ImportJobResource` with computed fields: `progress_pct`, `estimated_remaining_sec`, `errors`, `started_at`

### 4.8 Error CSV Download (`ContactImportController::errorsCsv`)

1. Re-reads original CSV file
2. Matches stored errors by row number
3. Returns CSV with original columns + `error` column appended

## 5. Actual File Structure

```
app/Domains/Contact/ManageContact/
├── Api/
│   └── Controllers/
│       └── ContactImportController.php     # API import controller (store + show)
├── Jobs/
│   └── ProcessImportBatch.php              # Queued batch job
├── Services/
│   ├── CsvToVCard.php                      # CSV → vCard converter
│   └── ImportFile.php                      # Shared import service (parse + dispatch)
├── Web/
│   ├── Controllers/
│   │   └── ContactImportController.php     # Browser upload controller
│   └── ViewHelpers/
│       └── ContactImportViewHelper.php     # Import page data helper

app/Domains/Contact/Dav/
├── Services/
│   ├── ImportVCard.php                     # Core vCard orchestrator
│   └── ImportVCalendar.php                 # Core VCalendar orchestrator
├── Jobs/
│   ├── UpdateVCard.php                     # DAV CardDAV job
│   ├── UpdateVCalendar.php                 # DAV CalDAV job
│   └── CleanSyncToken.php                  # Sync token cleanup
├── ImportVCardResource.php                # vCard importer interface
├── ImportVCalendarResource.php            # VCalendar importer interface
├── ImportResource.php                     # Marker interface
├── Importer.php                           # Abstract base class
├── VCalendarImporter.php                  # Abstract base class
├── Order.php                              # PHP 8 Attribute for ordering
├── VCardResource.php                      # Abstract model
├── VCalendarResource.php                  # Abstract model
├── IDavResource.php                       # DAV resource interface
├── Exporter.php                           # Abstract exporter base
├── ExportVCardResource.php                # vCard exporter interface
└── ExportVCalendarResource.php            # VCalendar exporter interface

app/Http/Resources/
├── ImportJobResource.php                   # API resource for import responses
├── UserResource.php
└── VaultResource.php

resources/js/Pages/Vault/Contact/
└── Import.vue                              # Import page (upload + progress + results)

app/Models/
└── ImportJob.php                           # Import tracking model

database/migrations/
├── 2023_08_01_000001_create_import_jobs_table.php
├── 2023_08_01_000002_add_csv_to_import_jobs_file_type.php
└── 2025_08_09_223221_add_errors_to_import_jobs.php
```

## 6. Implementation Phases (Actual)

### Phase 1 (Completed): Browser vCard + CSV Upload

- `ContactImportController` with file upload + validation
- `ProcessImportBatch` job wrapping `ImportVCard` (handles both vCard and CSV chunks)
- `Import.vue` with file picker, progress bar, and completion summary
- `ImportJob` model + migration for tracking
- `CsvToVCard` service for CSV conversion
- Error CSV download endpoint
- Progress polling endpoint

### Phase 2 (Deferred): Column Mapping & Preview

- Column mapping UI in Vue — **not implemented**
- Preview step showing first 5 rows — **not implemented**
- Date format detection — **partially implemented** (uses `date_parse()` in `CsvToVCard`)

### Phase 3 (Completed): Production Hardening

- Duplicate detection — **implemented** (file content hash via SHA-256)
- Rollback/undo — **not implemented** (`contact_ids_created` column exists but no UI)
- Import history page — **not implemented**
- ZIP file support for Google Takeout — **not implemented**
- Rate limiting and worker scaling — **not implemented**

### Phase 4 (Completed): REST API + Service Refactoring

- API `POST /api/import` endpoint — **implemented** (CSV upload + batch dispatch)
- API `GET /api/import/{id}` endpoint — **implemented** (progress polling with computed fields)
- `ImportFile` shared service — **implemented** (extracted duplicate logic from web + API controllers)
- `ImportJobResource` API resource — **implemented** (consistent JSON shape for both endpoints)
- Sanctum token abilities — **implemented** (`abilities:read` for show, `abilities:write` for store)
- Route registration — **implemented** (`routes/api.php`)

## 7. Key Architectural Patterns Used

1. **CSV-to-vCard bridge**: CSV rows are converted to vCard format via `CsvToVCard` before being passed to `ImportVCard`. This avoids duplicating the import logic.

2. **Laravel Batches**: The browser upload flow uses `Bus::batch()` to group all chunk jobs, with `.then()` and `.catch()` callbacks for completion/failure handling. Dispatched on the `imports` queue (separate from `high` used by DAV sync).

3. **Polling-based progress**: Frontend polls every 2 seconds. The controller checks the batch status via `Bus::findBatch()` and updates `ImportJob` status accordingly, handling cancellation and partial failure edge cases.

4. **Plugin pattern for importers**: `ImportVCard` uses `subClasses(ImportVCardResource::class)` to discover all implementations via reflection, sorted by `#[Order]` attribute.

## 8. Cancellation Mechanism

### 8.1 Overview

Users can cancel an active import (pending or processing) via a dedicated endpoint. Cancellation works at three levels:

1. **HTTP endpoint** — `DELETE /vaults/{vault}/contacts/import/{importJob}` triggers immediate DB status update and batch cancellation
2. **Laravel batch cancellation** — `Bus::findBatch($batchId)->cancel()` prevents queued/pending jobs from dispatching
3. **Mid-loop DB check** — each `ProcessImportBatch` job checks `$importJob->fresh()->status === 'cancelled'` between records within a chunk, catching cancellations that occur after the batch cancel signal is processed

### 8.2 Cancel Endpoint

```
DELETE /vaults/{vault}/contacts/import/{importJob}
```

- `Gate::authorize('vault-editor', $vault)` required
- Returns **409 Conflict** if import is already in a terminal state (`completed`, `failed`, `cancelled`)
- Sets `ImportJob.status = 'cancelled'` and `completed_at = now()`
- Calls `Bus::findBatch($importJob->batch_id)->cancel()` if `batch_id` is set
- Returns `{ status: 'cancelled' }` on success

### 8.3 Frontend Cancel Button

- Appears in the active import progress card when `importJob.status` is `pending` or `processing`
- Shows confirmation dialog before sending the DELETE request
- On success: immediately stops the polling interval and sets local status to `cancelled`

### 8.4 `dispatchBatch` Callback Protection

The `then()` and `catch()` callbacks in `Bus::batch()` check `$importJob->status === 'cancelled'` before applying their status updates, preventing the batch completion callbacks from overwriting a manually-cancelled status.

```php
$batch->then(function () use ($importJob) {
    $importJob->refresh();
    if ($importJob->status === 'cancelled') {
        return;  // Don't overwrite cancellation
    }
    // ... normal completion logic
});
```

### 8.5 Route

```php
Route::delete('import/{importJob}', [ContactImportController::class, 'cancel'])->name('contact.import.cancel');
```

### 8.6 Test Coverage

| Test                                                            | What's Verified                                                       |
| --------------------------------------------------------------- | --------------------------------------------------------------------- |
| `it_cancels_a_pending_import`                                   | Pending import → 200 + status=cancelled + completed_at set            |
| `it_cancels_a_processing_import`                                | Processing import → 200 + status=cancelled                            |
| `it_returns_409_when_import_already_completed`                  | Completed import → 409                                                |
| `it_returns_409_when_import_already_cancelled`                  | Already cancelled import → 409                                        |
| `it_denies_cancel_without_vault_editor_permission`              | vault-viewer → 403                                                    |
| `it_skips_processing_when_import_is_cancelled`                  | cancelled status in job → no processing                               |
| `it_stops_processing_chunk_when_batch_is_cancelled`             | Laravel batch cancellation stops mid-chunk                            |
| `it_respects_db_cancelled_status_during_vcard_chunk_processing` | vCard chunk: processes when active, skips when cancelled via DB check |
| `it_respects_db_cancelled_status_during_csv_chunk_processing`   | CSV chunk: processes when active, skips when cancelled via DB check   |

## 9. Deviations from Original Design

| Design Proposal                    | Actual Implementation                                              | Impact                                                         |
| ---------------------------------- | ------------------------------------------------------------------ | -------------------------------------------------------------- |
| `Import` model                     | `ImportJob` model                                                  | Different table name, slightly different column names          |
| `imports` table                    | `import_jobs` table                                                | Schema preserved but naming differs                            |
| `ImportContactFromUpload` job      | `ProcessImportBatch` job                                           | Handles chunks of 50 records instead of 1                      |
| `ImportCSV` service                | `CsvToVCard` utility                                               | Stateless, static methods, no UI mapping                       |
| Column mapping UI                  | Auto-detected aliases                                              | Simpler UX, less flexible                                      |
| Preview step                       | Not implemented                                                    | Users cannot preview before import                             |
| Rollback API                       | Not implemented                                                    | No undo capability                                             |
| Import history page                | Not implemented                                                    | No import audit trail                                          |
| 7 routes (incl. destroy, rollback) | 6 routes (added errors.csv, show, cancel)                          | Restful routes trimmed                                         |
| No API design                      | `ImportFile` shared service + API controller + `ImportJobResource` | REST API added for programmatic access                         |
| Duplicate code in web + API        | `ImportFile` service extracted                                     | DRY — shared CSV/vCard parsing, batch dispatch, error handling |

## 10. Security (Implemented)

- File type validated by extension (`.vcf, .vcard, .csv, .txt`)
- Max file size: 51200 KB (50 MB), capped by PHP `upload_max_filesize` and `post_max_size`
- Authorization via `Gate::authorize('vault-editor', $vault)` on all endpoints
- CSRF protection via Laravel
- File stored in `storage/app/imports/` (not publicly accessible)
- CSV injection not specifically mitigated
- MIME type validation not implemented (extension-only)

## 11. Test Coverage

### 11.1 Test Files

| Test                    | Type    | Location                                                                   | Tests | Assertions |
| ----------------------- | ------- | -------------------------------------------------------------------------- | ----- | ---------- |
| CsvToVCard              | Unit    | `tests/Unit/Domains/Contact/ManageContact/Services/CsvToVCardTest.php`     | 33    | 65         |
| ProcessImportBatch      | Unit    | `tests/Unit/Domains/Contact/ManageContact/Jobs/ProcessImportBatchTest.php` | 13    | 25         |
| ContactImportController | Feature | `tests/Feature/Controllers/ContactImportControllerTest.php`                | 34    | 79         |
| ImportFile              | Unit    | `tests/Unit/Domains/Contact/ManageContact/Services/ImportFileTest.php`     | 14    | 29         |

**Total: 94 tests, 198 assertions** — all passing.

### 11.2 CsvToVCard Test Scenarios

| Category         | Tests | What's Covered                                                                                    |
| ---------------- | ----- | ------------------------------------------------------------------------------------------------- |
| Basic conversion | 4     | Minimal row, all column aliases, case-insensitivity, unknown columns                              |
| Contact fields   | 5     | Email, phone, address (full & partial), company, job title                                        |
| Personal fields  | 4     | Names, middle name, birthday (multiple formats), notes                                            |
| Edge cases       | 4     | Missing both names (→ "Unknown"), empty row, whitespace trimming, no address                      |
| Validation       | 7     | Valid name, missing name, invalid email, valid email, invalid phone, valid phone, multiple errors |
| Alias coverage   | 9     | Each CSV column alias is tested for every supported field                                         |

### 11.3 ProcessImportBatch Test Scenarios

| Test                                                            | What's Verified                                          |
| --------------------------------------------------------------- | -------------------------------------------------------- |
| `it_processes_a_vcard_chunk`                                    | Single vCard → 1 processed_row, status=processing        |
| `it_processes_multiple_vcards_in_a_chunk`                       | 2 vCards → 2 processed_rows                              |
| `it_processes_a_csv_chunk`                                      | CSV row → converted to vCard → 1 processed_row           |
| `it_skips_csv_rows_with_validation_errors`                      | Invalid CSV row → 1 skipped_row, structured error stored |
| `it_increments_failed_rows_on_invalid_vcard`                    | Unparseable vCard → 1 failed_row                         |
| `it_skips_processing_when_import_is_cancelled`                  | cancelled status → no processing                         |
| `it_tracks_created_contact_ids_on_successful_import`            | Successful import stores contact UUID in JSON            |
| `it_handles_mixed_success_and_failure_in_same_chunk`            | 1 valid + 1 invalid = 1 processed + 1 failed             |
| `it_fails_validation_with_invalid_account`                      | Invalid account_id → ValidationException                 |
| `it_dispatches_via_bus_and_updates_status`                      | Bus::assertBatched on dispatch                           |
| `it_stops_processing_chunk_when_batch_is_cancelled`             | Batch cancellation check within chunk loop               |
| `it_respects_db_cancelled_status_during_vcard_chunk_processing` | vCard: DB cancel check mid-loop stops processing         |
| `it_respects_db_cancelled_status_during_csv_chunk_processing`   | CSV: DB cancel check mid-loop stops processing           |

### 11.4 ContactImportController Test Scenarios

| Test                                                           | What's Verified                                           |
| -------------------------------------------------------------- | --------------------------------------------------------- |
| `it_shows_the_import_form`                                     | GET import page returns 200                               |
| `it_denies_access_without_vault_editor_permission`             | vault-viewer gets 403                                     |
| `it_rejects_missing_file`                                      | No file → 422 validation error                            |
| `it_rejects_invalid_file_type`                                 | .pdf file → 422                                           |
| `it_rejects_oversized_file`                                    | >50MB → 422                                               |
| `it_imports_a_vcf_file`                                        | vCard file → 201 + ImportJob created + Bus::assertBatched |
| `it_imports_a_csv_file`                                        | CSV file → 201 + ImportJob created + Bus::assertBatched   |
| `it_shows_import_progress`                                     | processing status → JSON with progress                    |
| `it_shows_completed_import_status`                             | completed status → JSON with final state                  |
| `it_shows_import_details`                                      | GET show route returns 200                                |
| `it_rejects_csv_with_no_header`                                | CSV without header → redirect with error                  |
| `it_rejects_csv_with_no_data_rows`                             | CSV with header but no data → redirect with error         |
| `it_cancels_a_pending_import`                                  | Pending import → 200 + status=cancelled                   |
| `it_cancels_a_processing_import`                               | Processing import → 200 + status=cancelled                |
| `it_returns_409_when_import_already_completed`                 | Completed import → 409                                    |
| `it_returns_409_when_import_already_cancelled`                 | Already cancelled import → 409                            |
| `it_denies_cancel_without_vault_editor_permission`             | vault-viewer → 403                                        |
| `it_rejects_duplicate_vcf_file_in_same_vault`                  | Same vCard content twice → error on second                |
| `it_rejects_duplicate_csv_file_in_same_vault`                  | Same CSV content twice → error on second                  |
| `it_allows_same_file_in_different_vault`                       | Same file in different vault → ok                         |
| `it_allows_different_content_with_same_filename`               | Same filename, different content → ok                     |
| `it_stores_content_hash_on_successful_import`                  | content_hash written on import creation                   |
| `it_returns_404_for_errors_csv_when_no_errors`                 | No errors → 404                                           |
| `it_returns_404_for_errors_csv_when_file_missing`              | Missing original file → 404                               |
| `it_generates_errors_csv_with_content`                         | Errors present → CSV with error column                    |
| `it_handles_errors_csv_with_special_characters`                | CSV with commas/quotes in fields → properly escaped       |
| `it_handles_errors_csv_with_missing_rows`                      | Missing row number → handled gracefully                   |
| `it_shows_progress_with_failed_status`                         | failed status → JSON with final state                     |
| `it_shows_progress_with_cancelled_status`                      | cancelled status → JSON with final state                  |
| `it_shows_progress_with_skipped_and_error_details`             | skipped rows + errors → JSON with error details           |
| `it_allows_reimport_after_failed_import`                       | Same file after failed → allowed                          |
| `it_allows_reimport_after_cancelled_import`                    | Same file after cancelled → allowed                       |
| `it_allows_reimport_after_pending_import`                      | Same file while pending → allowed                         |
| `it_shows_import_details_with_error_csv_url_when_errors_exist` | Show with errors → error CSV URL included                 |

### 11.5 ImportFile Test Scenarios

| Test                                          | What's Verified                                        |
| --------------------------------------------- | ------------------------------------------------------ |
| `it_fails_validation_with_invalid_data`       | Invalid account_id → ValidationException               |
| `it_fails_validation_without_required_fields` | Missing required fields → ValidationException          |
| `it_parses_csv_correctly`                     | CSV file → parsed rows with correct column mapping     |
| `it_parses_vcf_correctly`                     | VCF file → parsed vCard strings                        |
| `it_throws_for_empty_csv`                     | Empty CSV → InvalidArgumentException                   |
| `it_throws_for_header_only_csv`               | CSV with header but no data → InvalidArgumentException |
| `it_throws_for_empty_vcf`                     | VCF with no vCards → InvalidArgumentException          |
| `it_chunks_rows_into_groups_of_fifty`         | 150 rows → 3 chunks of 50                              |
| `it_dispatches_batch_and_updates_status`      | Valid file → ImportJob created, Bus::batch dispatched  |
| `it_creates_import_job_with_correct_data`     | ImportJob fields correctly populated                   |
| `it_dispatches_vcard_chunks_with_correct_key` | vCard chunks use 'vcard_chunk' key                     |
| `it_dispatches_csv_chunks_with_correct_key`   | CSV chunks use 'csv_chunk' key                         |
| `it_deletes_csv_file_when_parsing_fails`      | CSV parse error → stored file deleted                  |
| `it_deletes_vcf_file_when_parsing_fails`      | VCF parse error → stored file deleted                  |

### 11.6 How to Run

#### Prerequisite: MySQL Test Database

Create a dedicated MySQL database for tests:

```sql
CREATE DATABASE IF NOT EXISTS monica_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

The application uses the `testing` database connection defined in `config/database.php`, which reads `DB_TEST_*` environment variables. Defaults: `DB_TEST_DATABASE=monica_test`, `DB_TEST_USERNAME=root`, `DB_TEST_PASSWORD=""`, `DB_TEST_HOST=127.0.0.1`, `DB_TEST_PORT=3306`.

#### Artisan Command (Recommended)

Run all 4 import test files with a single command:

```bash
# With defaults (database=monica_test, username=root, no password)
php artisan monica:test-imports

# Custom credentials
php artisan monica:test-imports --database=monica_test --username=root --password=secret

# Custom host/port
php artisan monica:test-imports --host=192.168.1.100 --port=3307
```

Runs these test files in order:

1. `tests/Unit/Domains/Contact/ManageContact/Services/CsvToVCardTest.php`
2. `tests/Unit/Domains/Contact/ManageContact/Jobs/ProcessImportBatchTest.php`
3. `tests/Feature/Controllers/ContactImportControllerTest.php`
4. `tests/Unit/Domains/Contact/ManageContact/Services/ImportFileTest.php`

#### Direct PHPUnit (Alternative)

```bash
# All import tests
DB_TEST_DRIVER=mysql DB_TEST_DATABASE=monica_test \
  DB_TEST_USERNAME=root DB_TEST_PASSWORD="yourpassword" \
  DB_TEST_HOST=127.0.0.1 DB_TEST_PORT=3306 \
  php vendor/bin/phpunit \
    tests/Unit/Domains/Contact/ManageContact/Services/CsvToVCardTest.php \
    tests/Unit/Domains/Contact/ManageContact/Jobs/ProcessImportBatchTest.php \
    tests/Feature/Controllers/ContactImportControllerTest.php \
    tests/Unit/Domains/Contact/ManageContact/Services/ImportFileTest.php

# Single test file
DB_TEST_DRIVER=mysql DB_TEST_DATABASE=monica_test \
  DB_TEST_USERNAME=root DB_TEST_PASSWORD="yourpassword" \
  php vendor/bin/phpunit tests/Unit/Domains/Contact/ManageContact/Services/CsvToVCardTest.php
```

> **Note:** Tests require a MySQL database configured via `DB_TEST_*` env vars (see `config/database.php` `testing` connection). The default SQLite driver (`pdo_sqlite`) is not installed in this environment.

## 12. Duplicate File Detection

### 12.1 Mechanism

Each uploaded file is hashed with SHA-256 before storage. The hash is stored in the `content_hash` column on `import_jobs`. Before creating a new import, the system checks for a `completed` import with the same hash in the same vault.

```
User uploads file → hash_file('sha256') → query import_jobs
  WHERE vault_id = ? AND content_hash = ? AND status = 'completed'
  → EXISTS → reject with "This file has already been imported."
  → NOT EXISTS → proceed with import, store content_hash
```

### 12.2 Why SHA-256 Over Alternatives

| Approach                               | Weakness                                                                           |
| -------------------------------------- | ---------------------------------------------------------------------------------- |
| Filename + size                        | Renamed files bypass detection, false positives on size collision                  |
| No detection                           | Creates duplicate contacts silently                                                |
| Content-level dedup (contact matching) | More robust but requires comparing all contacts per file — deferred to future work |
| **SHA-256 hash**                       | Fast, deterministic, negligible collision risk, no false negatives                 |

### 12.3 Scope

- Detection is **per-vault**: the same file can be imported into different vaults
- Only checks against **completed** imports (re-importing a failed/cancelled file is allowed)
- Same filename with different content: allowed (hash differs)
- Same content with different filename: blocked (hash matches)

### 12.4 Migration

```php
Schema::table('import_jobs', function (Blueprint $table) {
    $table->string('content_hash', 64)->nullable()->after('file_size');
    $table->index(['vault_id', 'content_hash', 'status'], 'import_jobs_dedup_index');
});
```

The composite index on `(vault_id, content_hash, status)` enables fast lookups for the duplicate check query.

### 12.5 Implementation Location

- Check added in `ContactImportController::store()` (both web and API), before file storage and routing
- Hash computed from the uploaded file's real path via `hash_file('sha256', $file->getRealPath())`
- Hash is passed to `ImportFile` service which stores it on `ImportJob` creation

### 12.6 Test Coverage

| Test                                             | What's Verified                            |
| ------------------------------------------------ | ------------------------------------------ |
| `it_rejects_duplicate_vcf_file_in_same_vault`    | Same vCard content → redirect with error   |
| `it_rejects_duplicate_csv_file_in_same_vault`    | Same CSV content → redirect with error     |
| `it_allows_same_file_in_different_vault`         | Same file → ok in different vault          |
| `it_allows_different_content_with_same_filename` | Same filename, diff content → ok           |
| `it_stores_content_hash_on_successful_import`    | content_hash column populated correctly    |
| `it_allows_reimport_after_failed_import`         | Same file after failed import → allowed    |
| `it_allows_reimport_after_cancelled_import`      | Same file after cancelled import → allowed |
| `it_allows_reimport_after_pending_import`        | Same file while pending import → allowed   |

## 13. REST API Endpoints

### 13.1 GET `/api/import` — List Recent Imports

Returns a paginated list of import jobs across all vaults in the user's account, ordered by creation date descending.

**Authentication:** Bearer token (Sanctum) with `read` ability

| Query Param | Type | Default | Description              |
| ----------- | ---- | ------- | ------------------------ |
| `page`      | int  | 1       | Page number              |
| `per_page`  | int  | 10      | Items per page (max 100) |

**Response `200`:**

```json
{
  "data": [
    {
      "id": "uuid",
      "filename": "contacts.csv",
      "total_rows": 500,
      "processed_rows": 500,
      "failed_rows": 2,
      "status": "completed",
      "progress_pct": 100,
      "errors": [],
      "started_at": null,
      "estimated_remaining_sec": null,
      "created_at": "2026-06-03T12:00:00Z"
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

### 13.2 POST `/api/import` — Upload CSV for Import

Creates a new import job from a CSV file and dispatches batch processing jobs.

**Authentication:** Bearer token (Sanctum) with `write` ability  
**Content-Type:** `multipart/form-data`

| Field      | Type   | Required | Description                            |
| ---------- | ------ | -------- | -------------------------------------- |
| `file`     | file   | ✅       | CSV file (`.csv` or `.txt`, max 50 MB) |
| `vault_id` | string | ✅       | UUID of the target vault               |

**Response `201`:**

```json
{
  "data": {
    "id": "uuid",
    "filename": "contacts.csv",
    "total_rows": 500,
    "processed_rows": 0,
    "failed_rows": 0,
    "status": "pending",
    "progress_pct": 0,
    "errors": [],
    "started_at": null,
    "estimated_remaining_sec": null,
    "created_at": "2026-06-03T12:00:00Z"
  }
}
```

**Errors:**

| Status | Condition                                                                           |
| ------ | ----------------------------------------------------------------------------------- |
| 422    | Missing/invalid file, no header row, no data rows, duplicate file, invalid vault_id |
| 404    | Vault not found or not in user's account                                            |

### 13.2 GET `/api/import/{id}` — Get Import Progress

Returns the current state of an import job with computed progress metrics.

**Authentication:** Bearer token (Sanctum) with `read` ability

**Response `200`:**

```json
{
  "data": {
    "id": "uuid",
    "filename": "contacts.csv",
    "total_rows": 500,
    "processed_rows": 320,
    "failed_rows": 2,
    "status": "processing",
    "progress_pct": 64,
    "errors": [
      { "row": 42, "message": "Invalid email: 'not-an-email'" },
      { "row": 87, "message": "Missing required field: name" }
    ],
    "started_at": "2026-06-03T12:00:05Z",
    "estimated_remaining_sec": 45,
    "created_at": "2026-06-03T12:00:00Z"
  }
}
```

**Computed fields:**

| Field                     | Calculation                                                                         |
| ------------------------- | ----------------------------------------------------------------------------------- |
| `progress_pct`            | `round(processed_rows / total_rows * 100)`                                          |
| `estimated_remaining_sec` | `ceil(remaining / (processed / elapsed))` — `null` if not processing or no progress |
| `errors`                  | Stored `[{row, message}]` from `import_jobs.errors` JSON column                     |

**Batch status sync:** If the import is `processing`, the endpoint checks `Bus::findBatch()` and automatically transitions the status to `completed`, `failed`, or `cancelled` based on the batch state, matching the web controller's polling behavior.

**Errors:**

| Status | Condition                                           |
| ------ | --------------------------------------------------- |
| 404    | Import job not found or vault not in user's account |

### 13.3 GET `/api/import/{id}/errors.csv` — Download Error CSV

Returns the original CSV rows with an appended `error` column for rows that failed during import.

**Authentication:** Bearer token (Sanctum) with `read` ability

**Response `200` (CSV content):**

```
name,email,phone,error
"John Doe",not-an-email,,Invalid email: 'not-an-email'
,Jane,,Missing required field: name
```

**Errors:**

| Status | Condition                                                                        |
| ------ | -------------------------------------------------------------------------------- |
| 404    | No errors recorded, original file not found, or import job not in user's account |

### 13.4 Route Registration

```php
// routes/api.php
Route::middleware('auth:sanctum')->name('api.')->group(function () {
    Route::get('import', [ContactImportController::class, 'index']);               // abilities:read
    Route::post('import', [ContactImportController::class, 'store']);              // abilities:write
    Route::get('import/{importJob}/errors.csv', [ContactImportController::class, 'errorsCsv']);  // abilities:read
    Route::get('import/{importJob}', [ContactImportController::class, 'show']);    // abilities:read
});
```

Note: `errors.csv` route is registered before `{importJob}` to prevent Laravel from matching `errors.csv` as a UUID wildcard.

### 13.5 API Controller

**File:** `app/Domains/Contact/ManageContact/Api/Controllers/ContactImportController.php`

| Method        | Middleware        | Description                                                                                                                                      |
| ------------- | ----------------- | ------------------------------------------------------------------------------------------------------------------------------------------------ |
| `index()`     | `abilities:read`  | Lists all imports in the user's account, paginated, ordered by `created_at` desc                                                                 |
| `store()`     | `abilities:write` | Validates file + vault_id, computes SHA-256 hash, checks for duplicates, delegates to `ImportFile` service, returns `ImportJobResource` with 201 |
| `show()`      | `abilities:read`  | Finds `ImportJob` by UUID, verifies vault access, syncs batch status, returns `ImportJobResource`                                                |
| `errorsCsv()` | `abilities:read`  | Finds `ImportJob` by UUID, verifies vault access, re-reads original CSV, appends error column, returns CSV download                              |

All methods share the vault ownership check: `$request->user()->account_id !== $vault->account_id` → 404.

### 13.5 ImportFile Service

**File:** `app/Domains/Contact/ManageContact/Services/ImportFile.php`

Extends `BaseService`, implements `ServiceInterface`. Handles all shared import logic:

- **Validation rules:** `account_id`, `author_id`, `vault_id`, `file_path`, `original_filename`, `file_size`, `content_hash`, `file_type` (`vcard` or `csv`)
- **Permissions:** `author_must_belong_to_account`, `vault_must_belong_to_account`, `author_must_be_vault_editor`
- **Parsing:** `parseCsv()` streams the file via `fgetcsv`, detects header/data rows; `parseVcf()` uses `Sabre\VObject\Splitter\VCard`
- **Dispatch:** Creates `ImportJob`, chunks rows into groups of 50, dispatches `ProcessImportBatch` via `Bus::batch()` with `then()`/`catch()` callbacks

**Error handling:** Throws `\InvalidArgumentException` for user-facing errors (empty CSV, no header, no vCards). Each controller catches and formats appropriately.

### 13.6 ImportJobResource

**File:** `app/Http/Resources/ImportJobResource.php`

```php
return [
    'id' => $this->id,
    'filename' => $this->original_filename,
    'total_rows' => $this->total_rows,
    'processed_rows' => $this->processed_rows,
    'failed_rows' => $this->failed_rows,
    'status' => $this->status,
    'progress_pct' => $this->progressPct(),
    'errors' => $this->errors ?? [],
    'started_at' => $this->started_at ? DateHelper::getTimestamp($this->started_at) : null,
    'estimated_remaining_sec' => $this->estimatedRemainingSec(),
    'created_at' => DateHelper::getTimestamp($this->created_at),
];
```

Computed fields are lazy-evaluated private methods on the resource:

- `progressPct()` — avoids division by zero when `total_rows === 0`
- `estimatedRemainingSec()` — returns `null` unless status is `processing` with progress

### 13.7 Key Architectural Decisions

| Decision                               | Rationale                                                                                                            |
| -------------------------------------- | -------------------------------------------------------------------------------------------------------------------- |
| Shared `ImportFile` service            | Eliminates duplicate CSV/vCard parsing, ImportJob creation, and batch dispatch logic between web and API controllers |
| Vault ownership via `account_id` check | Simplifies auth — no need for route model binding on vault; works with just the import job ID                        |
| API uses Sanctum token abilities       | Matches existing API pattern (`abilities:read` for reads, `abilities:write` for writes)                              |
| Computed fields on the resource        | `progress_pct` and `estimated_remaining_sec` are derived from model state, not stored — keeps the schema clean       |
| Batch status sync in `show()`          | Ensures the API always reflects the real batch state without requiring a separate progress endpoint                  |
