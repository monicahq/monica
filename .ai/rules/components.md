---
paths:
  - 'resources/views/components/**'
---

# Components

## Never name one CSS property in both the base string and a variant
Components build a class string as a shared `$base` plus a per-variant `match`. If both name the same property, the two utilities have equal specificity and the browser draws whichever Tailwind emitted last, which Tailwind decides on its own and the string order does not affect.

`border-transparent` sat in the button component's `$base` and beat `border-hairline` in the secondary variant, so a button whose class list said it had an edge rendered without one, unnoticed. Fixed by moving `border-transparent` into the variants that want no edge.

A property a variant chooses belongs to the variant, in every branch of the `match`. `ButtonTest::no_variant_carries_two_border_colours` guards the button.

To check a suspected clash, search the built CSS for the two selectors: the later byte offset wins.
