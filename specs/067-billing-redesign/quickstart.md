# Quickstart: Billing screen redesign

## Checks that must pass

```bash
php artisan test --compact tests/Unit/ViewModels/Settings/Account/Billing
php artisan test --compact tests/Feature/Controllers/App/Settings/Account/Billing
php artisan test --compact tests/Feature/Views/Components/OfferListTest.php
php artisan monica:localize --check
vendor/bin/phpstan analyse --no-progress
vendor/bin/pint --dirty --format agent
```

After the rename, run the whole suite: the old copy is asserted in 13 files outside billing.

```bash
grep -rniE "free offer|the offers\b|this offer\b|that offer\b" lang/en.json app resources docs/portal/en
```

It prints nothing once the rename is complete (the verb sense, "offers nothing", does not match).

## Screen walkthrough at `http://erica.test/settings/account/billing`

Set each state with factories in a throwaway test, never against the local database (memory: never
migrate the local database). Render with the `take-screenshot` skill, in light and dark.

| State | Expect |
| --- | --- |
| Free, never paid | `h1` Billing, plans headline, three prices, currency toggle, "What changes", questions, about |
| Yearly subscriber | Summary with Active badge, renewal date, "Cancel renewal", usage; lifetime plan still offered |
| Monthly subscriber | As above; yearly offered as a switch with its dialog |
| Subscription ending | Summary with end date and "Keep the subscription" |
| Lifetime | Summary, usage, on record; no plans, no "What changes" |
| Granted | Summary says granted; no invoices, no Stripe link, no plans |
| Refunded or charged back | Summary says which and when; plans shown again |
| Stripe not answering | Warning in place of the plans; summary and questions stay |
| Self hosted | Only the notice that there is nothing to pay for |

## Accessibility pass (SC-003)

- Tab through the page: every button, currency toggle and question is reached in reading order,
  with a visible ring.
- VoiceOver: the chosen currency is announced as pressed; each question as a collapsed or expanded
  disclosure; each usage figure once.
- Zoom to 200% and a 375px window: cards and two column sections stack, and nothing scrolls sideways.
