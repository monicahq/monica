# Quickstart: validating the dashboard timeline

The tests are the validation. This page lists what proves each part, and how to run the two checks
that are not in the ordinary suite run.

## The suite

```bash
PAO_DISABLE=true php artisan test --compact --parallel
```

The whole suite, since the dashboard, the contact page's timeline partial and the API list change.

| Proves | Test |
| --- | --- |
| R2: counts per slice, filtered and unfiltered, a year kept at zero, today's split, soft deletes left out, another vault left out | `tests/Unit/Helpers/LifeEventTimelineTest.php` |
| R3: slice bounds and order, a year cut at today, combined filters | same file |
| FR-014: the timeline area takes the same number of reads, at most six, for 3 life events over 1 year and for 60 over 20 years with 30 people | `tests/Unit/ViewModels/Vaults/DashboardTimelineBlockTest.php` |
| FR-004, FR-005, FR-011, FR-012, FR-013, the edge cases on dates and partial contacts | same file |
| R5: a fragment request renders the timeline alone and no other block of the dashboard | `tests/Feature/Controllers/App/Vaults/VaultControllerTest.php` |
| R7: the person search lists matching people who take part in a life event, ten at most, of this vault only | `tests/Feature/Controllers/App/Vaults/VaultTimelinePersonControllerTest.php` |
| R10: the redirect after adding names the slice | `tests/Feature/Controllers/App/Vaults/VaultLifeEventControllerTest.php` |
| US4: the list with `slice` and `life_event_type`, the counts endpoint, 422 and 404 | `tests/Feature/Controllers/Api/LifeEventControllerTest.php`, `LifeEventSliceControllerTest.php` |

## The benchmark (FR-016, SC-002)

```bash
php artisan test --compact --group=benchmark
```

`tests/Unit/Helpers/LifeEventTimelineBenchmarkTest.php` inserts 20,000 life events over 30 years with
2,000 contacts and 40,000 participations in bulk, then draws the timeline area for the default slice, a
year, a type, a person, and both, and asserts each under 100 ms. It is in the `benchmark` group, which
`phpunit.xml` excludes from the ordinary run because the inserts take seconds. Run it when the reader's
queries change. It uses the testing database, never the local one.

If the type or person cases miss the target, add the index of research R8 in a new migration and run it
again.

## By hand

On `https://monica.test`, in a vault with life events in several years:

1. The dashboard opens on "Upcoming", or on the newest year when nothing is upcoming.
2. Pick a year, a type, then search and pick a person: only the timeline changes, the address follows,
   and the browser's back button walks back through each step.
3. Tab through the index, the filters, a month heading and "Older: …" with the keyboard alone.
4. Narrow the window: the index sits above the slice.
5. Switch the account to a right to left locale and repeat step 2.
