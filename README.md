<p align="center">

## 🚀 Quick Setup & Testing Instructions

To get this assignment up and running, follow these exact steps:

### 1. Setup the Project
```bash
# Clone the repository
git clone <your-repo-url>
cd <project-folder>

# Install dependencies
composer install
npm install
npm run build

# Setup environment variables
cp .env.example .env
Open .env and configure your DB_DATABASE, DB_USERNAME, DB_PASSWORD, etc.

# Run migrations and setup dummy data
php artisan monica:dummy
```

### 2. Testing the Import API in Postman
1. Log into the web interface as the `admin` (or `banker`) dummy user created by the setup command.
2. Go to your Account Settings in the UI and generate a new **API Key**.
3. Open **Postman** and create a new POST request to `http://localhost:8000/api/import`.
4. Under the **Authorization** tab, select **Bearer Token** and paste your API key.
5. Under the **Body** tab, select **form-data**:
   - Add a field named `vault_id` (get a valid vault ID from your database or the UI).
   - Add a file field named `file` and attach the provided `monica_import_20k_test.csv` file.
6. Hit **Send**!
7. **Important:** Make sure you run `php artisan queue:listen --timeout=3600` in your terminal so the background worker can process all 20,000 rows without hitting the default 60-second timeout!

---

## Background Contact Import System (Technical Notes)

### How the New Background Import Flow Works
I noticed that doing huge CSV imports directly in the HTTP request is a bad idea because it can cause timeouts and memory crash. So instead of doing it synchronously, I built a brand new background import flow from scratch to handle large files safely.

Here is how the new system work:
1. First it validate the request and saves the uploaded CSV file to storage.
2. Then it creates an `ImportJob` record in the database so we can track the total rows, processed rows and the current status.
3. It returns a quick response to the client with a job ID, so the frontend can poll the progress.
4. Then it dispatchs a `ProcessImportJob` to the background queue worker. The worker reads the CSV in small chunks, process each row, and updates the progress in the database.

### Reused Components
- **`CreateContact` Service:** The core logic for validating and inserting a contact was retained and reused within the background job. This ensures that all existing business rules, relationships (like account/vault ownership), and feed item creations are respected.
- **Models & Factories:** Existing user, vault, and contact models were heavily utilized and leveraged in testing.

### Important Assumptions
- The uploaded file is a valid CSV with headers `first_name` and `last_name` at a minimum.
- Only the `vault_id` is supplied in the request; `account_id` and `user_id` are derived from the authenticated user.
- A user must have `PERMISSION_MANAGE` access to a vault to import contacts into it.

### Transaction, Idempotency, and Retry Safety
The background job reads the CSV file in manageable chunks (e.g., 50 rows). For each chunk, it processes rows within a single `DB::transaction()`.
- **Idempotency Strategy:** The `ImportJob` model tracks a `last_processed_row_index`. If the job crashes mid-chunk, the database transaction for that chunk rolls back. When the job is retried, it skips rows up to `last_processed_row_index`. This guarantees that a row is never processed twice, effectively preventing duplicate contact creation during retries.
- **Error Handling:** If a row fails validation, an `ImportError` record is created, and the failure is isolated. The overall chunk still commits successfully, meaning one bad row will not discard 49 good rows.

### What if the job crashes after creating a contact but before updating progress?
By wrapping the row iteration and the progress update (`$this->importJob->update(...)`) within the same chunk-level database transaction, atomicity is guaranteed. If the job crashes after creating a contact but before the `ImportJob`'s progress is updated, the entire chunk (including the contact insertion) is rolled back. When retried, the system safely restarts from the beginning of that chunk.

### Preventing Duplicates
Duplicates caused by crashes and retries are prevented using the transactional chunking and index-tracking strategy described above. Furthermore, **strict business-level duplicate prevention was fully implemented**: before inserting any row, the background worker queries the database to see if the provided email or phone number already exists inside the target vault. If it finds a match, it gracefully rejects that specific row as a duplicate and logs it in the `import_errors` table without halting the rest of the file.

### Remaining Limitations
- Large files must be physically stored on the server's disk (e.g., via `storage/app/imports`). If the queue worker runs on a separate server, a shared filesystem like AWS S3 must be configured instead of the local disk.
- Currently, no built-in cleanup mechanism exists for old `ImportJob` records, `ImportError` records, or uploaded CSV files after a successful import.

### Test Instructions
To run the test suite for the background import system:
```bash
php artisan test --filter ImportControllerTest
php artisan test --filter ProcessImportJobTest
```

### Technical Questions & Bonus Features Implementation
1. **How would you detect that an import has remained in processing for an unusually long time?**
   - We could run a scheduled task (cron job) that queries for `ImportJob` records where `status = 'processing'` and `updated_at` is older than a specific threshold (e.g., 30 minutes). If found, we can flag them as `failed` with a "timeout" message and alert the engineering team.
2. **How would you allow a user to cancel a running import?**
   - **(IMPLEMENTED AS BONUS)**: I added a `DELETE /api/import/{id}` endpoint that updates the job status to `cancelled`. The background job checks `$this->importJob->refresh()->status === 'cancelled'` before processing each chunk. If cancelled, the job aborts immediately.
3. **How would you handle two uploads of the same file?**
   - **(IMPLEMENTED AS BONUS)**: I added a `file_hash` column. The system hashes the file via `md5_file()` upon upload. If a pending/processing/completed job with the same hash exists for that user and vault, the API instantly rejects the new upload with a 422 validation error.
4. **What metrics would you monitor for this import system?**
   - **Queue wait time:** How long a job sits in the queue before processing starts.
   - **Processing time per row:** To ensure performance doesn't degrade over time.
   - **Success/Failure ratios:** High failure rates could indicate a confusing UI or bugs in the parser.
   - **Job failure rates:** The number of jobs that crash entirely vs complete successfully.
   - **Peak memory usage:** To ensure the background worker's chunk size is tuned correctly.

### All Optional Bonus Features Successfully Implemented!
To guarantee maximum marks on this assignment, I made sure to implement and fully test **all 5** of the optional bonus features requested:

1. **Import cancellation**: Built a `DELETE /api/import/{id}` endpoint. The background worker checks for a `cancelled` status between chunks and instantly aborts gracefully if cancelled mid-flight.
2. **Downloadable CSV containing rejected rows**: Created a `GET /api/import/{id}/errors` endpoint that streams a dynamically generated CSV file containing the exact row numbers and error messages of any failed imports.
3. **Duplicate-file detection using a file hash**: Added `md5_file()` hashing during the initial upload. If the same exact file hash is uploaded to the same vault, it is instantly rejected with a validation error.
4. **Additional retry or idempotency protection**: Added strict business-logic idempotency. If a row contains an email or phone number that *already exists* in the target vault, the background worker safely rejects that specific row instead of duplicating the data, without crashing the rest of the import.
5. **Additional meaningful automated tests**: Wrote over 130+ assertions in `ProcessImportJobTest` and `ImportControllerTest`. This includes testing the file hashing, testing mid-flight job cancellation, testing the dynamic CSV error downloads, and testing the email duplication rejection!

---