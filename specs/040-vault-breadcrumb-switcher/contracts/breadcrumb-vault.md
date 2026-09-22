# Contract: `x-breadcrumb.vault`

The one interface this change exposes. It is a Blade component consumed by 19 screens, so its
signature is the contract and it does not change.

## Signature

```blade
<x-breadcrumb.vault :vault="$vault" :items="[['label' => 'People', 'url' => '/…'], ['label' => 'Michael']]" />
```

| Prop | Type | Required | Meaning |
| --- | --- | --- | --- |
| `vault` | `App\Models\Vault` | yes | the vault the reader is in |
| `items` | `array<int, array{label: string, url?: string\|null}>` | no, defaults to `[]` | the crumbs after the vault, in order |

Unchanged from today. No call site is edited.

Resolution moves from the anonymous component alone to `App\View\Components\Breadcrumb\Vault`
rendering the same view. Laravel resolves the class first, so the tag keeps working.

## What it renders

A `<nav aria-label="Breadcrumb">` holding, in order:

1. **The vault crumb.** A colour tile carrying the first character of the vault's name, hidden from
   assistive technology, then the vault's name.
2. **One crumb per entry of `items`**, each preceded by a separator that is decorative and hidden
   from assistive technology.

## When the vault crumb is itself the last crumb

On the vault dashboard `items` is empty, so the vault crumb is the screen the reader is on. It then
carries `aria-current="page"`, and in the form that is not a control it is a `<span>` rather than a
link, because linking to the page you are already on says nothing. The form that is a control stays
a button and carries `aria-current="page"` as well: the switcher is still useful there.

This case is not in the spec's FR-010, which only describes the crumbs after the first. It was found
while implementing, and the existing test `it_always_starts_at_the_vault` already required it.

## The vault crumb has two forms

### More than one vault in the account

A `<button type="button">` carrying:

- an accessible name that says it opens the list of vaults,
- `aria-expanded`, reflecting whether the panel is open,
- `aria-controls`, naming the panel,
- a chevron, decorative and hidden from assistive technology, present only in this form.

### Exactly one vault in the account

An `<a>` to `route('vaults.show', $vault)`. No chevron, no panel, no hover fill, nothing to open.
A `<span>` instead, when it is also the last crumb, as described above.

## The panel

Present only in the first form. Hidden until opened.

- A heading naming the list.
- A `<ul>` with one `<li>` per vault of the account, in the order given in
  [`../data-model.md`](../data-model.md). Each item is an `<a>` to `route('vaults.show', $option)`
  holding the vault's colour tile, its name, and the number of people it holds, phrased through
  `trans_choice()`.
- The item for the vault the reader is in carries `aria-current="true"` and a check glyph. The mark
  is therefore carried by the markup and by a shape, never by colour alone.
- A hairline, then a single `<a>` to `route('vaults.new')` labelled "New vault".

## Behaviour

| Trigger | Result |
| --- | --- |
| Activating the crumb | The panel opens. `aria-expanded` becomes `true`. |
| Activating the crumb again | The panel closes. |
| `Escape` | The panel closes. Focus returns to the crumb. |
| Activating anything outside the panel | The panel closes. |
| An Alpine Ajax request starting | The panel closes. |
| Activating a vault other than the current one | Plain navigation to that vault's dashboard. |
| Activating the current vault | Navigation to the dashboard of the vault already open. Nothing is warned, nothing is saved. |
| `Tab` inside the open panel | Moves to the next link. The panel does not trap focus. |

No `role="menu"`, `role="menuitem"` or `aria-haspopup`. The reasoning is decision 3 in
[`../research.md`](../research.md).

## What the component does not do

- It does not take a `$user`. The account is reached through `$vault->account_id`.
- It does not warn about unsaved input. Switching is a link, like every other crumb.
- It does not render outside a vault. `x-breadcrumb` remains the component for the settings and
  account screens, unchanged.

## The contract this change removes

`x-vault.sidebar` loses the vault selector at its top. Its own signature is unchanged: it already
takes `user`, `vault`, `current` and `taskCount`, and `vaults` was never a prop, only a value
`App\View\Components\Vault\Sidebar::render()` passed to the view. That value and the private method
behind it are both deleted.
