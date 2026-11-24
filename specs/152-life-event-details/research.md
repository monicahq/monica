# Research: Details of a life event on the vault timeline

## R1. Loading on demand

- **Decision**: one GET route returns the panel as a Blade fragment. Each entry holds `x-data="{ open: false, loaded: false }"`; **Details** toggles `open` and, on the first open only, calls `$ajax(detailsUrl, { target: 'life-event-details-{uuid}', focus: false })`. The target holds the loading placeholder until the fragment replaces it. Closing hides the target with `x-show`, so reopening fetches nothing (FR-003).
- **Rationale**: the changelog comments (`resources/views/app/changelog/_feedback.blade.php`) do exactly this, and it adds no JavaScript file.
- **Alternatives**: a `life-event-details.js` component (more code for no gain); loading every panel with the page (breaks FR-004).

## R2. The dashboard stays at its query count

- **Decision**: the entry gains only `detailsUrl`, built with `route()` from the life event already loaded. `DashboardTimelineBlockTest` already counts the dashboard's queries (around line 311) and must pass unchanged.
- **Rationale**: SC-001 is guarded by an existing test; nothing new is read per entry.

## R3. A fixed number of queries per panel (SC-003)

- **Decision**: `LifeEventDetailsViewModel` loads the event with `lifeEventType.lifeEventCategory`, `group`, `participants.participant`, `fieldValues.field.sectionVersion`, `fieldValues.file`, `fieldValues.valueListItem`, `fieldValues.relatedContact`, `fieldValues.relatedEntity`, then one query for the relationships between the vault's `is_me` contact and the contact participants. Its test counts queries for an event with 1 and with 5 participants and asserts the same number.
- **Alternatives**: lazy relations in Blade (a query per participant and per field).

## R4. What each line shows and where it comes from

| Line | Source | Written with |
| --- | --- | --- |
| Type badge, category | `lifeEventType`, its category | type icon and colour, as the entry |
| Date | `happened_on`, `date_precision`, `ended_on` | `GroupPeriod::date()` and `between()`, with the year |
| Time, time zone | `happened_at_time`, `timezone` | time zone shown only when it differs from `user->timezone` |
| Duration | `duration_in_minutes` | `trans_choice(':count minute\|:count minutes')`, as `ContactTimelineBlock` |
| Place | `location` | as typed |
| Group | `group` | link to the group unless trashed |
| Fields | `fieldValues` not detached | `FieldValueFormatter::format()`, ordered by section position then field position; file and image fields go to **Files** instead |
| Files | file and image field values with a `file` | name, size, link to `files.show` |
| Who was there | `participants`, by `position` | contact or entity name, role, notes; contact link unless partial |
| Relationship | `Relationship` between `is_me` and the participant | the participant's side: `role_b` when the `is_me` contact is A, else `role_a`, as `RelationshipsBlock` |
| Footer | `created_from`, `created_at`, `updated_at` | "Added from :source on :date", "Last changed :relative" |

- An account holds one user, so the footer names no author.
- Sensitive field values are shown, as on every screen of the account's own vault.

## R5. A deleted event and a failed request

- **Decision**: the route binds `{lifeEvent}` through `ResolvesModelInVault`, which already refuses a trashed or foreign event with 404 (FR-009, FR-010). The entry listens to `@ajax:error` and sets `failed`, showing "This life event no longer exists." on a 404 and "The details could not be loaded." with **Try again** otherwise.
- **Check during implementation**: the status available in the `ajax:error` event's detail in the installed Alpine Ajax version.

## R6. The API carries every detail (FR-014)

- **Decision**: `LifeEventResource` gains `date_precision`, `ended_on`, `happened_at_time`, `timezone`, `role` and `notes` on each contact participant, and a new `entity_participants` list (`id`, `name`, `role`, `notes`, `position`; no entity type, which a list could only add with a query per entity). `participants` stays contacts only, so no client breaks. `get-life-event` answers through the API and follows without change.
- **Alternatives**: putting entities in `participants` (changes what an existing key holds).

## R7. Where the code goes

- `VaultLifeEventController::show` (the controller already serves `vaults/{vault}/life-events`), returning `app.vaults._life-event-details`.
- No action: the screen only reads. No policy: no rule is written twice.
- The panel uses `x-panel`, `x-badge`, `x-detail-list` with `x-detail-list.row`, and `x-contact-avatar`.
