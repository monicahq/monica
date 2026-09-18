# Components

## Look in `resources/views/components` before you write markup
A screen is assembled from components. Writing the markup by hand is how the application ends up with
two of everything, and the two then drift: the settings screens and the vault screens each grew their
own page heading, the line under the title set at a different size in each, and nobody noticed until
somebody counted.

Listing that folder is one command and it settles the question. Do it before every new screen and
before every block you are about to paste from another one.

These are the ones most often written out by hand instead, because they look like page markup rather
than like a component:

| Component | What it draws |
| --- | --- |
| `x-settings.page` | The whole shell of a settings screen: the way back, the list of settings, the body |
| `x-auth-page` | The whole shell of a sign-in screen: the card on one side, the quote on the other |
| `x-page-header` | The title of a screen, the line under it, and an `actions` slot for what you may do there |
| `x-page-section` | One band of a screen: a heading, a line saying what it is for, and the thing itself |
| `x-list-panel` | A panel holding a list, what stands there when it holds nothing, and the way to ask for more |
| `x-detail-list` + `x-detail-list.row` | A run of labelled values, one to a line |
| `x-sortable-row` + `x-position-form` | A row somebody reorders by dragging, and the form recording where it landed |
| `x-delete-action` | A quiet button that destroys something, the card that asks first, and the warning that it is final |
| `x-actions-menu` | The button that unfolds the rest of what may be done here, and the menu it unfolds |
| `x-copy-button` | The button that puts a value on the clipboard and says whether it got there |
| `x-code-value` | A value meant to be copied exactly, in a fixed-width face |
| `x-caption` | The small word in capitals that names what sits under it |
| `x-timestamp` | A moment, machine readable in the markup and spelled out on hover |
| `x-back-link` | The way back out of a screen, in the top bar |
| `x-auth-footnote` | The line under a sign-in card offering the other way in |
| `x-breadcrumb.vault` | A trail inside a vault, which always starts at the vault |

Read the component before you call it. Every one opens with a line saying what it is and a `@var`
block naming its props, and that is the whole documentation.

## Three class recipes have names, and the long form is wrong
`focus-ring`, `focus-ring-inset` and `dim-when-busy` are declared with `@utility` in
`resources/css/app.css`. Write the name. Never write out
`focus-visible:ring-[3px] focus-visible:ring-focus-ring/45 focus-visible:outline-none` again, nor the
inset variant of it, nor `transition-opacity [&[aria-busy]]:opacity-60`.

The long forms were spelled out 68 times across 46 files. At that length a missing piece is invisible
in review, which is the whole argument for giving the recipe a name.

## Three files is the bar, unless the markup carries a contract
Extract when the same markup stands in **three or more files**. Below that, leave it where it is.
Twice in one file is not a component, it is two lines, and `CLAUDE.md` already says not to hide those
behind a name.

Count files, not occurrences. Eight copies inside one screen are that screen's business.

**One exception.** Markup carrying a behavioural or accessibility contract is extracted at the
**second** file, not the third. A contract means the parts that must all be present and must agree:
the ARIA naming a widget and reporting its state, the keyboard route out of it, the dismissal, and
the Alpine state the three of them share. A tint or a gutter copied badly is visible in review.
`aria-expanded` copied without its `@keydown.escape.window` is invisible in review and broken for
somebody using a screen reader, and the constitution makes WCAG 2.2 AA a delivery condition rather
than an audit at the end.

The actions menu is the case that fixed this. Its trigger and its floating panel stood in two files,
thirty lines of state and wiring between them. Counting files said to leave it, which meant waiting
for a third screen before protecting the part most likely to rot. It is `x-actions-menu` now.

The exception covers the contract and stops there. The items inside that menu are links on one screen
and forms and modal triggers on the other, sharing a class string and nothing else, so they stayed
where they were and the three file bar still governs them.

## Count the markup, not the idea
Two blocks that do the same job are not the same markup, and the difference is what decides whether a
component can hold both.

A tile showing a figure looked like four files and was one: the other three put the number before the
label, or were a `<dl>`, or had no border. A filter field looked like three and was two, the third
being the API docs portal, which is its own small design system down to the English strings written
in the markup. Both were left alone, and both were right to leave.

So read every call site in full before you write the component. If the sites disagree about a
property, that is a prop with a `match`, not a reason to force one look on all of them and quietly
redesign two screens.

## Never name one CSS property in both the base string and a variant
Components build a class string as a shared `$base` plus a per-variant `match`. If both name the same
property, the two utilities have equal specificity and the browser draws whichever Tailwind emitted
last, which Tailwind decides on its own and the string order does not affect.

`border-transparent` sat in the button component's `$base` and beat `border-hairline` in the secondary
variant, so a button whose class list said it had an edge rendered without one, unnoticed. Fixed by
moving `border-transparent` into the variants that want no edge.

A property a variant chooses belongs to the variant, in every branch of the `match`. Nothing guards
this: a test that counts how many times a component names `border-transparent` is a test of how the
component looks, and `.claude/rules/testing.md` forbids those. Read the `$base` and every branch of
the `match` side by side instead, and check a suspected clash in the built CSS by searching for the
two selectors: the later byte offset wins.

The same clash arrives from outside, through `$attributes`. One call site passed `px-3.5 py-3` to a
component whose base already said `px-3 py-2`, which is the same coin toss. A property the base names
is not the caller's to set: either the component grows a prop for it, or the call site accepts the
base.

## A component cannot hand a slot to its parent
`<x-slot:footer>` binds to whichever component tag lexically encloses it. A component that renders
`<x-slot:footer>` in its own file fills the slot of a component **it** renders, never the slot of the
component that called it.

This is why there is no separate `x-load-more`: the "Load more" link lives in the panel's footer, and
only something rendering the panel can put it there. It is part of `x-list-panel` instead.

When a block you want to extract has to reach into an enclosing component, extract the enclosing
component too.

## A wrapper must forward every slot the thing it wraps reads
`x-shell` reads `breadcrumb`, `sidebar`, `actions` and `footer`. `x-settings.page` wraps it and at
first forwarded only the first two, so the field type screen lost the bar across its foot. Nothing
failed loudly: an unforwarded slot renders as nothing.

Before you ship a wrapper, read what it wraps, list the slots, and forward all of them.

## Never put an Alpine attribute on `x-dynamic-component`
`x-dynamic-component` passes its attributes as component **data**, and Blade turns a data key into a
PHP variable. `@click` becomes `$@click`, which is a parse error in the compiled view, and the screen
returns a 500.

Reach for the real tag instead. `x-button` already takes `variant`, so
`<x-button :variant="$variant" @click="...">` covers what a dynamic component was wanted for.

## In an Alpine handler, `$el` is what was clicked and `$root` is the nearest component

Neither reaches the element a component is mounted on. `$el` is whatever the handler sits on, and
`$root` is the closest `x-data` above it, which is often a shared component rather than the screen's:
`x-icon-button` declares `x-data="{ shown: false }"`, so every Move and Remove button is its own
component. Both point at the mounted element only during `init()`.

A component that has to find its own markup takes the element that was acted on and walks up to a
marker of its own. `resources/js/activity-type-fields.js` does this, and the editor's controls pass it
in: `@click="addField($el)"`, then `el.closest('[data-field-editor]')`.

This fails silently. `querySelector` returns null, the handler throws inside Alpine, and the button
simply does nothing.

## `@js()` is not compiled inside a component tag

`<x-select ::disabled="type !== @js($kind)">` passes the raw string `type !== @js($kind)` through as a
data key, and the surrounding `@if`/`@else` can be swallowed with it, which returns a 500 from a
template that looks right. The same line on a plain `<span>` or `<fieldset>` compiles.

So put the Alpine expression on real markup. A run of controls that all switch together belongs in a
`<fieldset>` carrying the `x-show` and the `:disabled`, which also stops the hidden ones being
submitted.

A Blade comment inside `@php` breaks the block just as quietly: the whole block is printed to the
page as text. Use `//` there.

## Take a collection or an array, not one of them
View models return both. `x-list-panel` called `isEmpty()` and fell over on the admin account list,
which returns a plain array. A component receiving a list checks for emptiness in a way that holds for
either:

```php
$holdsNothing = is_array($items) ? $items === [] : $items->isEmpty();
```

## Changing a shared component changes every screen that uses it
Run the whole suite, not the component's own test. A component under twenty screens is a change to
twenty screens.

Expect a handful of feature tests to fail on a literal class string or a literal piece of Alpine.
Those assertions were describing the old markup. Rewrite them to assert the behaviour instead, which
survives the next refactoring: assert that the value is announced, not that `$refs.key.textContent`
appears.
