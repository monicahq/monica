# Research: Life events on the vault dashboard

## R1. Reuse the contact page's panel, not a copy of it

**Decision**: the dashboard includes `app.vaults.contacts._life-event-panel` and runs `lifeEventPanel()` from `resources/js/life-event-panel.js`, built on the Me contact. The panel gains a `who` step and a `subject`, both dormant on the contact page.

**Rationale**: the panel is 281 lines of markup carrying a keyboard and ARIA contract with 181 lines of Alpine state. `.claude/rules/components.md` extracts such markup at the second file; copying it is what that rule forbids.

**Rejected**: a dashboard copy of the panel (two contracts drift); design 1a and 1c (not chosen).

## R2. The dashboard section keeps the id `contact-timeline`

**Decision**: the dashboard section wrapping the row, the panel and the Me timeline carries `id="contact-timeline"`, and the timeline partial keeps `contact-timeline-rows` and `contact-timeline-pagination`.

**Rationale**: the panel's form, `show()` and `cancel()` target those ids. The section is in fact a contact's timeline, so the name stays true, and the panel needs no new target parameter. Alpine Ajax takes the targets from a full page response, so the dashboard needs no `panels` view.

## R3. A dashboard controller posting with `contact_id`, and a Form Request shared by three endpoints

**Decision**: `VaultLifeEventController` (`new`, `create`) posts to `vaults/{vault}/life-events` with a `contact_id` for the subject. The rules and the request reading now private to `ContactLifeEventController` (`rules`, `typeFrom`, `valuesFrom`, `participantsFrom`) move to a new `SaveLifeEventRequest`, which both controllers take. `sentenceParts` moves to `LifeEvent::confirmationParts(User)`, since `destroy()` uses it too and must not validate a life event's fields.

**Rationale**: the controllers skill reserves a Form Request for "the same rule set shared by several endpoints", which this is. A refused form comes back with `old('contact_id')`, so the subject is restored without a hidden field kept only for the screen. `getRedirectUrl()` sends a refused update to its edit URL, which the update's `try`/`catch` does today.

**Rejected**: posting to `contacts.lifeEvents.create` of the subject with an `origin` flag (the subject is lost on a refused form, and the client must build a slug URL); duplicating the 60 lines of helpers in a second controller.

## R4. A candidate search that excludes nobody by default

**Decision**: `VaultLifeEventCandidateController::index` at `vaults/{vault}/life-events/candidates` returns every contact of the vault except `except[]`, rendered by the existing `_life-event-candidates` partial. Both the `who` step and "Add someone" use it on the dashboard.

**Rationale**: the contact page's search always excludes the contact in the URL, which is the Me contact here. Michael could then never add himself to Pam's wedding (spec, story 3).

## R5. The panel's `choose()` serves both steps

**Decision**: the candidates partial keeps calling `choose(id, name)`. In the `who` step it sets `subject`, drops that person from `people`, and moves to `pick`; otherwise it adds a participant as today.

**Rationale**: the partial and its endpoint stay unchanged for the contact page.

## R6. The timeline block is read only on the dashboard

**Decision**: `ContactTimelineBlock` takes `isReadOnly`. When true, `_timeline` draws no Delete and no deletion modal, and Edit is an ordinary link to `contacts.lifeEvents.edit` of the Me contact, without `x-target`.

It shows ten entries (`perPage: 10`) and, instead of "Load more", a "View full timeline" link to `contacts.show` of the Me contact.

**Rationale**: the clarification of 2026-10-11 (spec FR-010) and the user's request for ten entries; ten keep "Needs your attention" within reach.

## R7. Where the life event was written

**Decision**: `LifeEventSourceEnum::Dashboard = 'dashboard'`; `CreateLifeEvent` takes `createdFrom`, defaulting to `Contact`. The column is a `string(16)`, so no migration is needed.

## R8. No Me contact, no block

**Decision**: a vault without a Me contact shows neither the row nor the timeline.

**Rationale**: the constitution makes one Me contact per vault an invariant; building a "choose who first" variant for data that breaks it is not worth its code. The spec's edge case is amended to match.

## R9. Life events have no API

`routes/api.php` and `app/Mcp/Tools` cover life event types and categories, not life events. The gap predates this feature, which adds no capability the contact page lacks. The spec's assumption saying otherwise is corrected; the gap needs its own specification under principle X.

## R10. Design 2a: a segmented switch written in the dashboard view

**Decision**: the switch is a `role="tablist"` of two `role="tab"` buttons in `show.blade.php`, Alpine state `view`, arrow keys moving between them, each controlling a `role="tabpanel"`. The attention panel moves to `_attention-dates`, which the tab includes without "All reminders" (the toolbar carries it) and `_attention` wraps in its section. A component tag opened and closed inside two `@unless` blocks rendered the panel as nothing, so the wrapper is never conditional.

**Rationale**: `x-tabs` switches by link and draws an underline, not 2a's segments; one screen uses this switch, below the bar the components rule sets for extraction.

