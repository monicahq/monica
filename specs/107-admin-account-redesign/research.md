# Research: Admin account screen redesign

Source: Claude Design project `b78dc791-8645-424e-b24c-635ab7aea4f3`, `Admin Account.dc.html`. The
shell, its tokens and its focus recipes are [106 research](../106-admin-redesign/research.md).

## R1. Reading column

- **Decision**: the screen's content sits in `mx-auto w-full max-w-240` (960px), as the design draws it,
  inside the layout's `max-w-280` `<main>`. Other admin screens keep their width.

## R2. Header

- **Decision**: `x-breadcrumb` (items: Accounts with its URL, then `title()`), `x-avatar-initials`
  at 48, `h1`, the address in `font-mono text-sm`, the badges, and `x-button.secondary` with a
  `timeline` icon to `recordUrl()`. "Back to accounts" and the bottom record panel go.
- **Alternatives**: `x-back-link` (it belongs in the top bar, which this layout does not have).

## R3. Badges

| Order | When | Label | Tone |
| --- | --- | --- | --- |
| 1 | `isTheViewer()` | Your own account (`key` icon) | warning |
| 2 | always | Can sign in, or Deactivated / Person deleted / Nobody on this account | success, or danger |
| 3 | billable | `accessKindLabel()` (Free plan, Lifetime access, Granted by us, Subscription) | neutral |
| 3 | self hosted | Self-hosted | neutral |

- The separate "Lifetime access" badge goes: the plan badge already names it. `badges()` returns
  `icon` too. The spec's FR-003 is corrected to match.

## R4. Own account note

- **Decision**: `x-notice tone="info"` with one new sentence joining today's footer line and the
  panel subtitle it replaces.

## R5. Fact panels

- **Decision**: two `x-panel :flush="true"` (icons `vault`, `profile`) in
  `grid-cols-[repeat(auto-fit,minmax(min(100%,360px),1fr))]`, each holding `x-detail-list` with
  `label-style="caption"` and a new `layout="stacked"` prop (label above value, as drawn). The
  default `layout` keeps every existing call site unchanged. "Address confirmed" joins the address
  through a new `userEmailLine()`; "Can sign in" leaves the panel for the badge; "Storage" leaves for R6.
- **Alternatives**: hand written `<dl>` (what the screen does today, and what `components.md` asks
  us to stop doing).

## R6. Limits and access

- **Decision**: `x-page-section` ("Limits and access", with its line) around one `x-panel
  :flush="true"` of three `x-row`s. A new `stack` prop on `x-row` puts the button under the text
  below `xs`, since a button on the right leaves the label no room at 320 pixels.
  - Storage: `storageLine()` unchanged, the over limit sentence when over, and when a limit exists a
    `role="img"` bar labelled by a new `storageShareLine()` and sized by `storageSharePercent()`
    (capped at 100). The existing `x-modal.form` keeps its fields; only its trigger reads "Change limit".
  - Plan (billable only): `accessKindLabel()`, no action.
  - Lifetime access (always, as today): `accessForGoodLabel()` when held; a new line "No. The account
    follows its plan." otherwise (the billing panel keeps its "No"). The grant and withdraw modals stay.

## R7. Flags

- **Decision**: `x-page-section` with its line and an `actions` slot holding `x-link` to
  `admin.flags.index`. Rows as today in a flush panel; buttons read "Switch on" or "Switch off",
  keeping the `sr-only` flag name.

## R8. Deactivation

- **Decision**: `x-panel tone="danger"`, title "Deactivate this account", subtitle ":name would no
  longer be able to sign in. Their vaults and contacts stay where they are."
  - Another active person: the existing `x-modal.warning`, trigger "Deactivate account".
  - The viewer: `x-button.danger type="button" aria-disabled="true"` with `aria-describedby` on the
    existing sentence (`.claude/rules/policies.md`, a refused control stays on the screen).
  - A deactivated person: a default tone panel "Reactivate this account" with the existing modal.

## R9. What stays out

- The self deactivation rule is written twice today (the view model's `isTheViewer()` and
  `DeactivateUser`'s exception). `policies.md` would make that a `UserPolicy`. That is a change of
  behaviour code, not of design, and is left for its own change.
- The billing panel keeps its facts, now in `x-detail-list`, under the fact panels.
