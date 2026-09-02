# Research: Field Type Picker

Eleven decisions. Everything else follows an existing pattern in the repository.

## Filtering the catalogue

**Decision**: filter in the browser with Alpine, following `resources/views/components/api-docs/sidebar.blade.php`: each card carries `x-show="!query || <haystack>.includes(query.toLowerCase())"` with the haystack written by `Js::from`, and each family shows when any of its types match.

**Rationale**: the catalogue is a static list of thirty one items rendered with the page. There is nothing to fetch.

**Alternatives**: an Alpine Ajax round trip per keystroke, rejected as a request for data already on the page.

## The count of types showing

**Decision**: the view model renders one phrase per possible count, from zero to the total, each through `trans_choice()`. Alpine reads the phrase by index.

**Rationale**: the count changes in the browser and the wording depends on the count, so the only way to keep every locale's plural rules is to have the server write every wording it might need. The array is thirty two short strings from a single source phrase.

**Alternatives**: pluralizing in JavaScript, rejected because `.claude/rules/code-style.md` forbids choosing a wording by comparing a count, and no translator can reach a rule written in JavaScript.

## Drawing the preview

**Decision**: render every type's illustration server-side into the panel, show one at a time with `x-show`. A partial beside the screen draws one illustration from the shape the catalogue gives it.

**Rationale**: every word in the panel stays in Blade, so it stays translated and the localize check keeps seeing it. Thirty one small blocks of markup is a cost the screen can carry.

**Alternatives**: passing the preview as JSON and templating it in Alpine, rejected because the copy leaves Blade and stops being localizable.

## Peeking without selecting

**Decision**: pointer enter and focus set `peek`, pointer leave and blur clear it, and the panel shows `peek || selected`.

**Rationale**: it is what the mockup does, and focus driving it too is what makes the preview reachable without a pointer.

## Keeping the radio group

**Decision**: the cards stay labels wrapping a real radio input, as today.

**Rationale**: arrow key movement, the required state, the announcement of the group and `old('type')` all come free and all have to exist anyway.

**Alternatives**: the mockup's `aria-pressed` buttons, rejected: they lose the group semantics, the browser's keyboard handling and the required validation, and would have to reimplement all three.

## Announcing the preview

**Decision**: one screen reader only region with `aria-live="polite"`, reading the previewed type's name and description from a key to sentence map.

**Rationale**: a panel swapped by `x-show` is not announced reliably; a live region says one sentence and says it once.

## The mark on a card

**Decision**: nineteen types reuse a glyph the icon set already has, twelve new glyphs are drawn in `resources/views/components/icon.blade.php` (`text`, `text-lines`, `hash`, `radio`, `tag`, `calendar-repeat`, `clock`, `layers`, `coin`, `divider`, `heading`, `swatch`). A mark may repeat across families, since the label carries the meaning and the mark only helps somebody find a card again.

**Rationale**: the design system says a glyph that does not exist has to be drawn in that file. There is no icon font to reach for.

**Alternatives**: the mockup's character tiles (`@`, `://`, `$`, `12`), rejected: a currency sign is not localizable and a mix of characters and glyphs reads as two icon languages.

## Reduced motion

**Decision**: nothing on this screen. `resources/css/app.css` turns the duration tokens off under `prefers-reduced-motion`, so a transition written with a token is already answered.

## Layout

**Decision**: `lg:grid-cols-[minmax(0,1.35fr)_minmax(0,1fr)]` as the builder uses, the panel `lg:sticky lg:top-0` as `_inspector` does, one column below that breakpoint.

**Rationale**: this pairing of a list and a panel about the selected thing already exists on the template builder, one screen away.

## Nothing preselected

**Decision**: the panel shows a resting state naming what it is for, and the submit stays disabled, until a type is chosen.

**Rationale**: carried from the specification. The type cannot be changed once the field exists.

## Artifacts not written

`data-model.md`, because nothing about storage changes. `contracts/`, because the route, the payload and the action are untouched. `quickstart.md`, because the commands that prove the feature are three lines in `plan.md`.
