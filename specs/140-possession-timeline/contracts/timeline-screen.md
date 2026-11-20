# Contract: the Timeline screen

`GET vaults/{vault}/possessions/{possession}/timeline/{section?}`, `section` one of the seven keys.

- Answers 404 for a possession of another vault or account, and for an unknown section key.
- The section column marks Timeline current and shows its count; Timeline is a link on every possession page.
- Under the heading: the help bubble (title "How the timeline works") and the lead line.
- Chips: **All**, then one per section holding entries, in the column's order, each a link with its count; the chosen one carries `aria-current="true"`.
- Groups: one `h3` per year, newest first, with "1 entry" / ":count entries"; then **Date unknown** when needed.
- Entries: an `ol` per group; each `li` is named by its title (`aria-labelledby`); the title is a link to the entry's section page; the rail and icon are decorative.
- Empty: "Nothing recorded in any section yet." for All, "Nothing recorded in :section yet." for a chip (a chip only exists for a section with entries, so this reads only when the possession holds nothing).
