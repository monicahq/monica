# Contract: the life event panel and the timeline

All routes sit in the vault contact group of `routes/web.php`, before `contacts.show`, and bind
`{vault}` and `{contact}` as their siblings do. Each controller checks
`$contact->isReachableThrough($user, $vault)` and answers 404 otherwise.

| Route name | Method, URL under `vaults/{vault}/contacts/{contact}/` | Controller | Answers |
| --- | --- | --- | --- |
| `contacts.lifeEvents.create` | POST `life-events` | `ContactLifeEventController@create` | redirect to `contacts.show` |
| `contacts.lifeEventFields.index` | GET `life-events/types/{lifeEventType}/fields` | `ContactLifeEventFieldController@index` | fragment `#life-event-custom-fields` |
| `contacts.lifeEventCandidates.index` | GET `life-events/candidates?query=&except[]=` | `ContactLifeEventCandidateController@index` | fragment `#life-event-candidates` |

The timeline’s next page is `contacts.show` with `?timelinePage=N`, targeting `contact-timeline-rows
contact-timeline-pagination` and appending.

## POST fields

| Field | Rule |
| --- | --- |
| `life_event_type_id` | required, integer; the action refuses another account or a deactivated type |
| `happened_on` | required, `date` |
| `participants` | array of contact ids, each distinct, never the page’s contact |
| `title`, `location` | nullable, string, max 255 |
| `narrative` | nullable, string, max 65535 |
| `duration_in_minutes` | nullable, integer, 1 to 100000 |
| `fields.*` | `FieldValueRules::for()` over the published version’s fields |

The form is `multipart` whenever the type asks for a file. Errors come back as field errors under
the same names; a refusal from the action comes back on `life_event_type_id` or `participants`.

## Markup contract

- The opener is a `<button aria-expanded aria-controls="life-event-panel">`, disabled while open.
- Step 1: the categories are a list of buttons with `aria-current="true"` on the current one; the
  types are buttons; the search is `type="search"` with an accessible name; the result heading is
  announced through `aria-live="polite"`.
- Step 2: `happened_on` has a `<label>`; each person chip’s remove button is named "Remove :name";
  the candidate list sits under `aria-live="polite"`; each closed field is a button naming it.
- The timeline is an `<ol>` per year under a heading with the year; each entry names its type in
  text, not only through its icon.
- Alpine hooks: `x-data="lifeEventPanel(`, `x-target="contact-timeline life-event-panel"`,
  `id="contact-timeline"` with `x-merge="replace"`.
