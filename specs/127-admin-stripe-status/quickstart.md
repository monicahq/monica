# Quickstart: validating the Stripe status screen

## Automated

```bash
php artisan test --compact tests/Unit/Actions/CheckStripeConnectionTest.php tests/Unit/Actions/ReloadStripeCatalogueTest.php tests/Unit/ViewModels/Admin tests/Unit/Helpers/StripeCatalogueTest.php tests/Feature/Controllers/App/Admin
```

The suite must prove, each in its own layer (`.claude/rules/testing.md`):

| Scenario | Spec | Layer |
| --- | --- | --- |
| No secret key: not configured, no HTTP request sent | Edge case, R14 | `CheckStripeConnectionTest` |
| Each key's mode from its prefix, the modes disagreeing, both environment warnings | US1.2, US1.3, FR-004 | `CheckStripeConnectionTest` |
| 200 reads the endpoints, 401 is refused, 403 is restricted, a timeout is unreachable with a reason | US1.5, edge cases | `CheckStripeConnectionTest`, `Http::fake()` |
| A kind missing from every enabled endpoint of this address is named; `*` covers all; another address does not count | US2.1 to US2.3 | `CheckStripeConnectionTest` |
| No key value in the action's result, for success and for a failure quoting the key | FR-003, SC-004 | `CheckStripeConnectionTest` |
| `readAt()` is set when the catalogue is cached and forgotten with it | US3.1 | `StripeCatalogueTest` |
| Reloading forgets and reads the catalogue, pushes the log entry, and reports a failure | US3.2, US3.3 | `ReloadStripeCatalogueTest` |
| Access counts follow the order of `accessKind()`, over every account | US4.1 | `BillingFiguresTest` |
| Subscription states and kinds from the latest payment, "kind not known" otherwise | US4.2 | `BillingFiguresTest` |
| Two months of payments per currency and kind, nothing converted; refunds and disputes from confirmations | US4.3 to US4.5, SC-006 | `BillingFiguresTest` |
| Confirmations by kind for 7 and 30 days, the newest, and the empty sentence | US2.4, US2.5 | `StripeConfirmationsTest` |
| Self hosted: one sentence, no request, no figure. Hosted without a secret key: keys block and figures shown, no request. Catalogue not read when Stripe is unreachable | FR-002, R5, R14 | `BillingStatusViewModelTest`, `BillingStatusControllerTest` |
| The screen renders with its heading and pushes no log job | FR-001, FR-011 | `BillingStatusControllerTest` |
| The POST redirects with each flash | FR-012 | `StripeCatalogueReloadControllerTest` |
| A visitor, a regular user and a deactivated administrator get not found | US1.9 | `AdminAccessTest` provider |

## By hand

1. With Stripe test keys in `.env` and `stripe listen` running, open `/admin/billing` as an administrator. It reads test mode, every key set, Stripe reachable, and lists the offers.
2. In the Stripe test dashboard, create an endpoint at the instance's `/stripe/webhook` address that leaves out `invoice.paid`. Reload: that kind is named as missing.
3. Replace the secret key with a restricted key without webhook permission. Reload: reachable, endpoints not readable.
4. Block `api.stripe.com` (for example a wrong `HTTPS_PROXY`). Reload: unreachable within 6 seconds, the catalogue not read, the figures still shown (SC-002).
5. Change a price amount in Stripe and press "Read the catalogue again": the new amount shows, and the Record screen names the administrator.
6. Set `MONICA_SELF_HOSTED=true`: the screen holds one sentence. View the page source in every step: no key appears.
