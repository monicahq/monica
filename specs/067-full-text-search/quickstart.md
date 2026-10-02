# Quickstart: Full text search

Validation for what the suite cannot reach. The suite itself: `php artisan test --compact
--filter=Search`, with the reference engine running (the devcontainer starts it; CI runs it as a
service).

## 1. A normal instance

1. Keep `SCOUT_DRIVER=database`, or run an outside engine, install its client, set `SCOUT_DRIVER` and
   its credentials in `.env`. Run a queue worker for `high,low`.
2. `php artisan monica:rebuild-search-index`, with `--from=<engine>` after changing engines. Expect
   the indexes emptied, one line per vault and a final count.
3. Give Dwight Schrute an "Employer" field reading "Schrute Farms". Within 10 seconds, type
   "farms dwight" in the top bar and press Enter: Dwight is listed under People with
   "Employer: Schrute Farms", the words marked.
4. Search "Scrute" and "Dwigth": Dwight is found. Search "elodie": Élodie is found.
5. Choose "Every vault": results of the other vaults appear, each naming its vault.
6. Mark a field sensitive, fill it, search its value: the record is found, the label shows, the
   value does not. Over the API with a key lacking `sensitive-fields`: not found.
7. Rename Jan Levinson to Jan Gould, search "gould": Jan, then Michael ("Partner of Jan Gould")
   among People, and the Chili's dinner among Life events.
8. Delete Dwight: he leaves the results; restore him and he returns.
9. Keyboard only: reach the box, submit, move through groups and "Load more"; a screen reader
   announces the result count.

## 2. The engine stops, or none is configured (User Story 4)

1. Stop the engine. Save a change to Dwight: it saves. Open the search page: it says search is
   unavailable. The API answers 503.
2. Start the engine again: once the queue retries, the change is found.
3. With `SCOUT_DRIVER=null`, the instance boots, saves, and the search page says unavailable.

## 3. Timing (SC-001, SC-002, SC-005)

Seed a vault with 10,000 contacts of 20 fields through the models, let the queue drain, and time 50
searches of one, two and three words: 95% under 500 ms. Save a contact under normal queue load and
time until it is found: under 10 seconds. Search 50 names with one typo each: the intended record
is in the first five results at least 45 times.

## 4. Every supported engine, not in CI

For Algolia, Meilisearch and Typesense in turn: install its client, set `SCOUT_DRIVER`, run
`php artisan monica:rebuild-search-index`, then repeat steps 3 to 8 of section 1. "farms dwight"
must require both words, a name match must rank above a value match, and a sensitive value must
stay out of reach of a key lacking `sensitive-fields`.
