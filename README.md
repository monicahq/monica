# Envobyte Technical Assignment — Background Contact Import System

**Candidate Submission for Backend Engineer Position**  
**Tech Stack**: PHP 8.2+ / Laravel 11 / MySQL 8.0+ / Laravel Queues  
**Branch**: `envobyte-assignment`  

---

## Table of Contents
1. [Overview](#overview)
2. [Existing Import Flow Analysis](#existing-import-flow-analysis)
3. [Implementation Approach & Architecture](#implementation-approach--architecture)
4. [Database Design](#database-design)
5. [API Endpoints](#api-endpoints)
6. [Validation & Per-Row Error Isolation](#validation--per-row-error-isolation)
7. [Retry Safety & Idempotency Strategy](#retry-safety--idempotency-strategy)
8. [Assumptions and Limitations](#assumptions-and-limitations)
9. [Technical Questions & Answers](#technical-questions--answers)
10. [Bonus Features Implemented](#bonus-features-implemented)
11. [Setup & Installation Instructions](#setup--installation-instructions)
12. [Running Automated Tests](#running-automated-tests)

---

## Overview
This submission redrafts and enhances the CSV contact import flow in Monica CRM to operate asynchronously in the background. Processing large CSV files synchronously during HTTP requests risks timeouts, high memory usage, and poor user experience. Our solution leverages Laravel Queues, memory-efficient chunk reading via `SplFileObject`, atomic progress tracking, per-row error isolation, and retry safety.

---

## Existing Import Flow Analysis
### How the Original Flow Worked
In Monica CRM, contact creation is governed by domain-level service classes (e.g., `App\Domains\Contact\ManageContact\Services\CreateContact`). Before this assignment, CSV imports were executed synchronously within HTTP requests or lacked comprehensive progress state tracking, error reporting, and retry handling.

### Existing Components Reused & Modified
- **`App\Domains\Contact\ManageContact\Services\CreateContact`**: Reused for creating contacts. It encapsulates model creation (`Contact`), feed item logging (`ContactFeedItem`), and domain permission validation (`author_must_be_vault_editor`).
- **`App\Http\Controllers\ApiController` & `JsonRespondController`**: Extended to follow Monica's API response structure and error handling standards.
- **`App\Models\Account` & `App\Models\Vault`**: Added relationship `importJobs()` for account scoping and ownership checks.
- **`routes/api.php`**: Registered `POST /api/import`, `GET /api/import/{id}`, `POST /api/import/{id}/cancel`, and `GET /api/import/{id}/errors`.

---

## Implementation Approach & Architecture
1. **Asynchronous HTTP Initiation**: `POST /api/import` validates the uploaded file (`mimes:csv,txt`), stores it securely in `storage/app/imports/{account_id}/`, creates an `ImportJob` record with status `pending`, dispatches `ProcessImportJob` to the `imports` queue, and immediately returns HTTP 201 with the job metadata without parsing contacts inside the request.
2. **Chunked Incremental Reading**: `ProcessImportJob` opens the CSV file using PHP's `SplFileObject` with `READ_CSV | SKIP_EMPTY` flags. Memory remains strictly $O(1)$ regardless of file size. Processing proceeds in 50-row chunks.
3. **Atomic Progress Updates**: `processed_rows` and `failed_rows` counters are updated atomically in the database via raw SQL increments (`DB::raw('processed_rows + 1')`) after each row attempt.
4. **Per-Row Error Isolation**: Each row is wrapped in its own try-catch block and database transaction. An invalid row does NOT stop the overall import. Errors are logged to `import_errors` with row numbers and row contents.

---

## Database Design
### 1. `import_jobs` Table
| Column | Type | Purpose |
|---|---|---|
| `id` | `UUID` (Primary) | Unique job identifier |
| `account_id` | `UUID` (Indexed) | Account ownership & scoping |
| `user_id` | `UUID` (Indexed) | User who initiated the import |
| `vault_id` | `UUID` (Indexed) | Target vault for contact creation |
| `filename` | `VARCHAR(255)` | Original uploaded filename |
| `file_path` | `VARCHAR(255)` | Non-public storage location |
| `file_hash` | `VARCHAR(64)` | SHA-256 hash for duplicate file detection |
| `has_header` | `BOOLEAN` | Whether first row contains headers |
| `header` | `JSON` | Detected CSV header names |
| `total_rows` | `BIGINT` | Total data rows (excluding header) |
| `processed_rows` | `BIGINT` | Total rows attempted |
| `failed_rows` | `BIGINT` | Total rejected rows |
| `status` | `ENUM` | `pending`, `processing`, `completed`, `failed`, `cancelled` |
| `failure_message`| `TEXT` | System-level failure message if unhandled exception occurs |
| `started_at` | `TIMESTAMP` | When processing started |
| `completed_at` | `TIMESTAMP` | When processing ended |
| `timestamps` | `TIMESTAMPS` | `created_at` and `updated_at` |

### 2. `import_errors` Table
| Column | Type | Purpose |
|---|---|---|
| `id` | `BIGINT AUTO_INCREMENT` | Primary key |
| `import_job_id` | `UUID` (Foreign Key) | References `import_jobs.id` (ON DELETE CASCADE) |
| `row_number` | `BIGINT` | Data row number (1-indexed for user) |
| `row_data` | `JSON` | Original raw row contents for error CSV export |
| `error` | `TEXT` | Detailed failure reason |
| `timestamps` | `TIMESTAMPS` | Created timestamp |

---

## API Endpoints

### 1. Upload CSV & Initiate Import
- **Method / Path**: `POST /api/import`
- **Headers**: `Authorization: Bearer <token>`, `Accept: application/json`
- **Body** (`multipart/form-data`):
  - `file`: CSV file (required)
  - `vault_id`: UUID of the vault (required)
- **Response**: `201 Created`
```json
{
  "data": {
    "id": "9b8a1c2d-3e4f-5a6b-7c8d-9e0f1a2b3c4d",
    "filename": "contacts.csv",
    "total_rows": 0,
    "processed_rows": 0,
    "failed_rows": 0,
    "status": "pending",
    "progress_pct": 0,
    "failure_message": null,
    "started_at": null,
    "completed_at": null,
    "created_at": "2026-08-09T18:00:00Z"
  }
}
```

### 2. Check Import Status & Progress
- **Method / Path**: `GET /api/import/{id}`
- **Headers**: `Authorization: Bearer <token>`, `Accept: application/json`
- **Response**: `200 OK`
```json
{
  "data": {
    "id": "9b8a1c2d-3e4f-5a6b-7c8d-9e0f1a2b3c4d",
    "filename": "contacts.csv",
    "total_rows": 500,
    "processed_rows": 320,
    "failed_rows": 2,
    "status": "processing",
    "progress_pct": 64,
    "failure_message": null,
    "started_at": "2026-08-09T18:00:05Z",
    "completed_at": null,
    "created_at": "2026-08-09T18:00:00Z"
  }
}
```

### 3. Cancel Running Import (Bonus)
- **Method / Path**: `POST /api/import/{id}/cancel`

### 4. Download Failed Rows CSV (Bonus)
- **Method / Path**: `GET /api/import/{id}/errors`

---

## Validation & Per-Row Error Isolation
Each row undergoes independent validation:
1. **Name presence**: `first_name` must be non-empty and $\le 255$ characters.
2. **Email format**: If provided, validated via Laravel's validator `email`.
3. **Vault & Account check**: User permissions in target vault.
4. **Service execution**: Calling `CreateContact::execute()`.

If validation or creation fails:
- The error is captured, DB transaction is rolled back.
- An `ImportError` record is created.
- `failed_rows` and `processed_rows` are incremented.
- The loop continues to the next row.
- Overall status is `completed` if $\ge 1$ contact succeeded; `failed` if 0 contacts succeeded.

---

## Retry Safety & Idempotency Strategy
Laravel queue workers may retry a job following a worker crash, timeout, or queue reset.
- **Checkpointing**: On job start, `processed_rows` is read from the database. The job skips the first `processed_rows` data rows and resumes processing at row `processed_rows + 1`.
- **Idempotency Guard**: Before calling `CreateContact`, the job checks if a contact with the same `first_name` and `last_name` already exists in the target `vault_id`. If found (e.g., created right before a crash before counter update), creation is skipped and counter is updated, preventing duplicate contact creation.
- **Atomic Counter Update**: DB transactions commit the contact first; counter increments run via atomic SQL updates (`processed_rows = processed_rows + 1`).

---

## Assumptions and Limitations
1. **Header Detection**: Headers are detected if the first row contains standard keywords (`first_name`, `last_name`, `email`, etc.). If no header matches, positional mapping (Col 0 = `first_name`, Col 1 = `last_name`, Col 2 = `email`) is used.
2. **Vault Selection**: If `vault_id` is passed, it must belong to the user's account; if omitted, it defaults to the account's first available vault.
3. **Idempotency Limitations**: Idempotency is based on `vault_id` + `first_name` + `last_name`. If legitimate duplicates exist in the CSV with identical names, only the first will create a contact upon retry.

---

## Technical Questions & Answers

### Q1: How would you detect that an import has remained in processing for an unusually long time?
**Answer**:
1. **Scheduled Monitoring Job**: A periodic Laravel command (`php artisan imports:check-stuck`) running every 5 minutes can query:
   ```php
   ImportJob::where('status', 'processing')
       ->where('started_at', '<', now()->subMinutes(15))
       ->get();
   ```
2. **Heartbeat / `updated_at` Check**: Update `updated_at` after every chunk. If `updated_at` is older than 10 minutes, mark status as `failed` with `failure_message = 'Import timed out or worker process terminated unexpectedly'`.
3. **Metrics Alerting**: Send an alert (Slack / Sentry / Prometheus) when stuck jobs are detected.

### Q2: How would you allow a user to cancel a running import?
**Answer**:
1. **Endpoint**: Provide `POST /api/import/{id}/cancel`.
2. **State Transition**: Set `status` to `cancelled` in the database.
3. **Job Polling**: Inside `ProcessImportJob`, check `$import->refresh()->isCancelled()` before starting each 50-row chunk. If `cancelled`, break the loop immediately, record `completed_at`, and exit clean.

### Q3: How would you handle two uploads of the same file?
**Answer**:
1. **File Hash Fingerprinting**: Compute a `SHA-256` hash of the uploaded file contents (`hash_file('sha256', $file)`).
2. **Duplicate Check**: Query existing imports for the same account with matching `file_hash` and status `pending` or `processing` or `completed` within the past 24 hours.
3. **Handling Options**:
   - Return the existing `ImportJob` object with HTTP 200/409, avoiding duplicate processing.
   - Or prompt user: *"This file was imported 2 hours ago. Do you want to re-import?"*

### Q4: What metrics would you monitor for this import system?
**Answer**:
1. **Queue Backlog & Latency**: Time spent by `ProcessImportJob` waiting in queue before execution.
2. **Job Failure Rate**: Percentage of jobs reaching `failed` state vs `completed`.
3. **Row Failure Ratio**: Ratio of `failed_rows` / `total_rows` across imports to detect formatting issues.
4. **Processing Throughput**: Rows processed per second / per chunk duration.
5. **Memory Peak Usage**: Memory consumed during `SplFileObject` chunk processing.

---

## Bonus Features Implemented
- [x] **Import Cancellation**: `POST /api/import/{id}/cancel` endpoint and in-job chunk cancellation check.
- [x] **Downloadable Error CSV**: `GET /api/import/{id}/errors` generates a downloadable CSV containing failed row numbers, error messages, and original row data.
- [x] **Duplicate File Detection**: SHA-256 `file_hash` stored on `import_jobs`.
- [x] **Comprehensive Test Suite**: Feature test suite in `tests/Feature/ImportJobTest.php`.

---

## Setup & Installation Instructions

1. **Clone & Branch**:
   ```bash
   git clone https://github.com/monicahq/monica.git
   cd monica
   git checkout envobyte-assignment
   ```

2. **Environment & Dependencies**:
   ```bash
   composer install
   cp .env.example .env
   php artisan key:generate
   ```

3. **Database & Migrations**:
   Configure MySQL in `.env`:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=monica
   DB_USERNAME=root
   DB_PASSWORD=
   QUEUE_CONNECTION=database
   ```
   Run migrations:
   ```bash
   php artisan migrate
   ```

4. **Queue Worker**:
   Start the queue worker for background job processing:
   ```bash
   php artisan queue:work --queue=imports
   ```

---

## Running Automated Tests

Run the complete feature test suite using PHPUnit / Artisan:

```bash
php artisan test --filter ImportJobTest
```

Or run all tests:

```bash
php artisan test
```

---
*Submitted by Candidate for Envobyte Ltd. Backend Developer Technical Assignment.*
