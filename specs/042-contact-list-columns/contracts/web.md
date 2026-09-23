# Web contract

| Method | Address | Name | Answers |
| --- | --- | --- | --- |
| GET | `vaults/{vault}/contacts/{filter}` | `contacts.index` | unchanged address. Column headings in FR-006 link to it with `sort` and `direction` |
| GET | `vaults/{vault}/contact-list-columns/edit` | `contactListColumns.edit` | the customise screen |
| PUT | `vaults/{vault}/contact-list-columns` | `contactListColumns.update` | redirects to `contacts.index` with the flash `status` "The list now shows your columns." |

## `PUT` payload

```text
columns[n][source]      required, a ContactListColumnSourceEnum value
columns[n][section]     required when source is field, the section uuid
columns[n][field_key]   required when source is field
columns[n][heading]     nullable, max 255
columns[n][is_visible]  boolean
```

The order of `columns` is the order of the list. An empty `columns` puts the defaults back, since a
vault with no rows shows the defaults. Name alone is every column sent with `is_visible` false. A refusal from the action goes back to the form with `withErrors()`.

## Accessibility contract (what the view tests may assert)

- People: a `<table>` with a `<caption>` that only screen readers see, `th scope="col"`, and
  `aria-sort` on the heading that is sorted. The "+N" control is a `button` with `aria-expanded` and
  an `aria-label` of "Show :count more", which goes through `trans_choice`.
- The customise button is `x-icon-button`, named by `aria-label="Customise view"`, which its tooltip
  repeats.
- Customise: `role="list"` on the columns. On each row, the grip is a `button` whose `aria-label`
  names the column and the arrow keys. The checkbox `aria-label` is "Show :heading". Name's checkbox
  is `disabled`. One `aria-live="polite"` region announces moves.
- The add menu is `x-actions-menu`, with a labelled trigger. A column already in the list is hidden
  from it rather than disabled.
