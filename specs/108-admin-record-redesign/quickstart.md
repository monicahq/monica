# Quickstart: Admin record screen redesign

The commands that gate the change are the ones in `CLAUDE.md`, under "When a change is done".

## By hand, at `https://erica.test/admin/logs`, signed in as an administrator

| Check | Expected |
| --- | --- |
| Switch a flag on an account, then open the record | Under "Today": "Switched on `flag` for Name", the name links to the account, both times shown |
| Open the record from an account screen | Only that account's entries, and a way to show everything |
| Change the page size and move to older entries | Footer count and paging as before |
| Set your time zone and a 12 hour clock in settings | Days and times follow them |
| Width 320 pixels, zoom 200%, dark theme | Nothing clipped, no horizontal page scroll |
| Keyboard and screen reader | Each day is announced as a section with its heading; entries as a list |
