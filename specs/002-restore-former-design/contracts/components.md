# Contract: The Component Inventory

**Feature**: [../spec.md](../spec.md) | **Location**: `resources/views/components`

Fifty one components today, counted with
`find resources/views/components -name '*.blade.php' | wc -l`. Every one of them is in exactly one
of the four states below when the feature is done. FR-020 says every shared element must exist in the restored language, and this
table is how that is checked off rather than felt.

The five layout components in `app/View/Components` are covered by
[contracts/shell.md](./shell.md) instead.

## Restyled and kept (43)

The component keeps its name, its props and its behaviour. What changes is the utility string, from
the warm palette, the display face and the shadow to the neutral palette, the interface face and the
hairline.

| Component | Note on the restyle |
|---|---|
| `avatar` | Neutral ring, no shadow |
| `avatar-initials` | The generated background is content colour and stays |
| `badge` | Hairline and a tint per tone, never colour alone. Tones become `neutral`, `success`, `warning`, `danger` |
| `breadcrumb` | Now lives in the shell's `breadcrumb` slot rather than a `bar` slot |
| `button/index` | The filled button, which is ink and inverts with the theme |
| `button/secondary` | Hairline border, canvas fill |
| `button/danger` | Fixed error colour, kept |
| `button/ghost` | No border until hovered |
| `button/bar` | The bar's own button, 20 callers. The bar survives, so this does |
| `checkbox` | Neutral, visible focus ring |
| `chip` | A filter chip. Fills with ink when it is the one in force, which is how the former direction said every toggle |
| `contact-avatar` | Content colour behind the initials, neutral frame |
| `dropdown` | Keeps `x-anchor`. Menu on canvas, hairline, no warm shadow |
| `empty-state` | Muted text, no illustration added |
| `entity-icon` | Content colour, kept |
| `error` | Fixed error colour plus the text, never the colour alone |
| `field-input` | The label, control, hint and error as one definition. Kept whole |
| `field-repetitions` | Each repetition stays labelled so a screen reader says which one |
| `field-value` | The read side of a field. Machine values in mono |
| `file-input` | Hairline drop zone |
| `form` | Unchanged apart from spacing |
| `help` | The "?" beside a title |
| `icon` | The named glyph set stays. Stroke weight settles at the former direction's 1.4 |
| `image` | Neutral frame |
| `input` | White in light, near black in dark, hairline, visible focus |
| `label` | The label step of the type scale |
| `language-picker` | Unchanged apart from the palette |
| `link` | The one accent, underlined on hover |
| `logo` | The mark itself does not change |
| `modal/form` | Keeps `x-trap.inert` |
| `modal/warning` | Keeps `x-trap.inert`. Every destructive action goes through it |
| `notice` | Keeps the glyph carrying its tone beside the tint, which is what stops it being colour alone |
| `option-row` | A row that is its own form, where picking is the save |
| `page-header` | Page title in the interface family at the `page-title` step |
| `panel` | The one container. Hairline border, hairline-soft between rows, no shadow |
| `row` | A row inside a panel |
| `select` | Native control, `color-scheme` makes it match the theme |
| `status` | Text plus colour, never colour alone |
| `textarea` | As the input |
| `theme-toggle` | Kept as it is. The theme mechanism does not change |
| `toaster` | Keeps its five keyframes and the 4500ms that pairs with the timer |
| `turnstile` | Third party widget, theme parameter only |
| `vault/search` | Hairline field, results on canvas |

## Newly authored in the restored language (1)

| Component | Why it is needed |
|---|---|
| `tabs` | The page strip on a contact and in the template builder. Pull request 50 added real tabs because the system was missing them, and FR-006 needs the current tab marked by more than colour. Arrow key traversal, `aria-selected`, one tab in the tab order. Neither tree has this component: today the strip is markup repeated on two screens |

## Restored from `11f266e` and extended (2)

| Component | What it needs that the former tree did not have |
|---|---|
| `vault/sidebar` | An entry for the share links under a contact, and whatever moves off the bar |
| `settings/sidebar` | Entries for the template share, export and import screens, which did not exist then |

## Retired (5)

Deleted once nothing references them, and once every destination they carried is reachable from the
restored navigation.

| Component | Replaced by |
|---|---|
| `dock` | The two sidebars |
| `launcher/index` | The two sidebars |
| `launcher/tile` | Nothing. Only the launcher used it |
| `launcher/tile-mark` | Nothing. Only the tile used it |
| `vault/bar` | The shell's bar and its breadcrumb slot |

## Consolidated (3)

| Component | Outcome |
|---|---|
| `box/index` | Retired. Its eleven callers move to `panel` |
| `box/row` | Retired. Its callers move to `row` |
| `bar-links` | Undecided until the shell commit. Kept and restyled if the three links stay in the bar, retired if they move into the column. See [contracts/shell.md](./shell.md) |

## Rules that apply to every one of them

- A component may not carry a meaning in colour alone. If a tone means something, a word or a glyph
  carries it too (FR-006).
- A component that opens over the page traps the keyboard inside it and marks the rest of the
  document inert (FR-023).
- A component that opens out of a container that clips is anchored against the window, not
  positioned inside the container.
- A component announces its state. `aria-current` for a position, `aria-selected` for a tab,
  `aria-expanded` for a disclosure, `aria-invalid` and `aria-describedby` for a field in error.
- A component's test asserts the copy and the markup that carries the meaning, and asserts a class
  only where the class is the meaning (`.claude/rules/testing.md`).
- A settled value is shown as a value. No component may render something unusable as a disabled
  control (FR-023).
