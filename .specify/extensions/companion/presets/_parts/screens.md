## Screens: what a person sees

**Show the screen the change alters**, so a reviewer sees it before it is built. Write it only when the change alters what a person sees; otherwise skip it.

Add a `## Screens` section to `plan.md`: at most 2 screens, 14 parts a screen, 5 dots, one note per dot.

```screen <one-word name> <title>
title: Settings
row:
  field: Display name (changed) (1)
  button: Save (new) (2)
list: General | Account | Billing
```
1: **Bold lead.** Rest of the sentence.
2: **Bold lead.** Rest of the sentence.

One part per line, two spaces to nest inside a `row:`: `title:`, `row:`, `text:`, `chip:`, `button:`, `field:`, `list: a | b | c`. End a part with `(new)` or `(changed)` for what the change touches, and a dot `(1)` where a note applies, both at the very end of the line. Words only: no HTML, sizes or colours.
