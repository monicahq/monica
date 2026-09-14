# Quickstart: validating Authorization Policies and Gates

## Prerequisites

The constitution amendment admitting `app/Policies` merged as version 2.3.0. Then `composer install`,
`php artisan migrate` and `bun run build` as usual. No seeder or fixture is specific to this feature.

## The three capabilities

| Ability | Asked by the action | Asked by the screen |
| --- | --- | --- |
| `create` on `Vault` | `CreateVault` | `VaultFormViewModel::ceilingMessage()` |
| `create` on `Contact` | `CreateContact` | `ContactFormViewModel::ceilingMessage()` |
| `delete` on `Contact` | `DestroyContact` | `ContactEditViewModel::mayBeDeleted()` |

## Automated checks

```bash
php artisan test --compact tests/Unit/Policies
php artisan test --compact --filter=CreateVault
php artisan test --compact --filter=CreateContact
php artisan test --compact --filter=DestroyContact
php artisan monica:localize --check
vendor/bin/pint --dirty --format agent
```

The localize check must pass with the unified ceiling wording translated in all 31 locales. It enforces
the counted phrase rules, so each ceiling string keeps `trans_choice()` and spells `:count` in every
segment.

## Proving SC-001, that the rule has one home

This is the criterion the feature exists for, and it cannot be proven by reading. Change the returned
message inside `VaultPolicy::create()`, then confirm the new wording appears in **both** places without
any other edit:

1. Sign in as an account on the free offer that already holds its vault ceiling.
2. Open the vault creation form. The paywall prompt shows the new wording.
3. Submit it anyway. The refusal carries the same new wording.

If either surface still shows the old wording, a second copy of the rule survives somewhere.

## Proving the Me contact refusal

1. Open the edit screen of the contact representing you. The delete control is present, marked
   unavailable, and the panel beside it explains that this contact stays.
2. Open the edit screen of any other contact. The delete control is active and works.

## Proving the API refuses identically

```bash
curl -X POST "$APP_URL/api/vaults" -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" -d '{"name":"Scranton"}'
```

For an account at the ceiling this returns the same explanation the screen shows. It does not return any
report of what is permitted, which is deliberate and recorded in the specification.
