# Contract: `x-full-access-offer`

The strip offering full access to a free account, drawn under the top bar.

## Props

| Prop | Type |
| --- | --- |
| `viewModel` | `App\ViewModels\FullAccessOfferViewModel` |

The layouts render it only when they hold a view model, so the component never decides whether to
show itself.

## Markup the tests may assert

- A `<section>` with `aria-label="Free plan usage"` (translated), hidden in print.
- The text "Free plan".
- Per meter, in order People then Vaults: its visible label with `id="full-access-offer-meter-N"`,
  an element with `role="progressbar"`, `aria-labelledby` pointing at that label,
  `aria-valuemin="0"`, `aria-valuemax` equal to the limit, `aria-valuenow` equal to the count capped
  at the limit, and `aria-valuetext` saying the count in words and, near or at the limit, which
  ("25 of 30, close to the limit", "1 of 1, the limit is reached"); then the text `used / limit`.
  The visible label and count are `aria-hidden`, so a screen reader hears each meter once.
- A link to `route('settings.billing.index')` reading "Upgrade your account".
- A form with `action` equal to `route('fullAccessOffer.update')`, method spoofed to `put`, holding a
  button with `aria-label="Hide this offer for good"`.

Tone classes, sizes and order on the line are the look and are not asserted.

## Unchanged

- `PUT full-access-offer` (`fullAccessOffer.update`) and `PUT api/me/full-access-offer`
  (`me.fullAccessOffer.update`) keep their request, response and effect.
- `full_access_offer_dismissed_at` in the user resource of the API keeps its meaning.
