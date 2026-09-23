# Contract: Edit relationship screen

`GET  vaults/{vault}/contacts/{contact}/relationships/{relationship}/edit`
`PUT  vaults/{vault}/contacts/{contact}/relationships/{relationship}`

Both routes answer 404 when the relationship is in another vault (as today) or does not involve
`{contact}` (new).

## What the form posts

| Field | Rule | Meaning |
| --- | --- | --- |
| `relationship` | required, `^\d+-[ab]$`, type in the account | Type id, and the side `{contact}` holds (`a` = `role_a`) |
| `has_ended` | nullable boolean | Ticked: ended |
| `started_on` | nullable date | As today |
| `ended_on` | nullable date, posted only while `has_ended` is ticked | Ignored and cleared when unticked |
| `mode` | nullable, `unknown` or `known`; only for a partial other side | `known` renames them |
| `first_name` | `required_if:mode,known`, max 255 | New first name |
| `last_name` | nullable, max 255 | New last name |
| `creates_record` | nullable boolean; only for a partial other side | Ticked: they get a record and join the list of people |

A named partial other side posts `mode=known` as a hidden field. A nameless one posts the chosen
radio. A side with a record posts no `mode`.

## Refusals and where they land

| Refusal | Field the error is shown on |
| --- | --- |
| Switched off type newly chosen, loop in parentage | `relationship` |
| End before start | `ended_on` |
| Empty first name with `mode=known` | `first_name` |

Every refusal comes back with the input, `withInput()`, and saves nothing.

## What the screen shows

| Other side | Block under the caption |
| --- | --- |
| `is_partial = false` | `x-contact-avatar` and the name |
| partial, named | `mode=known` hidden; First name, Last name; the explanatory line tied to First name by `aria-describedby` |
| partial, nameless | `<fieldset>` whose legend is the caption: two `x-option-row` radios (`unknown`, `known`); the fields shown and enabled only on `known` |

- The caption is the other side’s role in the chosen option, from its `data-role`.
- The checkbox is `x-checkbox id="has_ended"`, ticked when the status is `Ended`.
- The footer holds "Saved on both records at once.", Cancel (back to the Relationships tab) and
  "Save changes".
- Success redirects to the tab with "The relationship is changed.", as today.
