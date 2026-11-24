# Quickstart: Validating Favourite a Contact

How to prove the feature works, end to end, once it is built. Every step is runnable. Nothing here
is implementation.

## Prerequisites

- The local database matches the migrations. If a contact cannot be created and the error names a
  missing column, the local sqlite predates a migration edit and needs `php artisan migrate:fresh
  --seed`. Signing in afterwards uses `michael.scott@dundermifflin.com` and `password`.
- `bun run dev` running, or `bun run build` after the Blade and Tailwind changes, or the star's new
  classes will not exist in the stylesheet.
- At least two contacts in a vault, one of them not a favourite.

## Automated checks

Run narrowest first, then the whole suite.

```bash
# the new controller, both directions and both compartment refusals
php artisan test --compact tests/Feature/Controllers/App/Vaults/ContactFavouriteControllerTest.php

# the view model accessors the star reads
php artisan test --compact tests/Unit/ViewModels/Vaults/ContactViewModelTest.php

# the two actions, which this feature reuses and must not have altered
php artisan test --compact tests/Unit/Actions/FavouriteContactTest.php tests/Unit/Actions/UnfavouriteContactTest.php

# the whole suite, which is the gate
php artisan test --compact
```

Then the three gates that are not tests:

```bash
vendor/bin/pint --dirty --format agent
php artisan monica:localize --check     # must report nothing missing in all nine locales
vendor/bin/phpstan analyse --memory-limit=2G
```

`monica:localize --check` is the one most easily forgotten. Two strings are new, and the check fails
if any locale is short of them. It also enforces the French typography rules on whatever is written
there.

## Manual validation

Open a contact who is not a favourite:

```text
http://monica.test/vaults/{vault-uuid}/contacts/{contact-uuid}
```

| # | Do this | Expect |
|---|---------|--------|
| 1 | Look beside the avatar | An outline star is present. Today it is absent for a contact who is not a favourite, so its presence is the first thing this feature changes. |
| 2 | Press it | It fills. The page does not reload and the scroll position does not move. |
| 3 | Reload the page | Still filled. |
| 4 | Open the people list, favourites filter | The contact is listed, and is above the contacts who are not favourites. |
| 5 | Open Settings, then Logs | The newest entry reads "Pinned the contact {name}". |
| 6 | Press the star again | It empties, and the contact leaves the favourites filter. A second entry reads "Unpinned the contact {name}". No confirmation is asked for, and nothing is deleted. |

### Accessibility, which is a delivery condition and not a final pass

| # | Do this | Expect |
|---|---------|--------|
| 7 | Reach the star with <kbd>Tab</kbd> alone | It receives focus and the ring is visible. |
| 8 | Press <kbd>Enter</kbd> and <kbd>Space</kbd> | Both toggle the mark. |
| 9 | Read the page with VoiceOver | The control is announced as a toggle button, named "Add {name} to favourites" or "Remove {name} from favourites", with its pressed state, and the state is announced again after it changes. |
| 10 | Render the page in greyscale, or set the display to greyscale | The two states are still distinguishable, because the difference is fill and not tint. |
| 11 | Zoom to 200% | The star stays on screen, stays pressable, and does not overlap the name. |
| 12 | Narrow the window to a phone width | Same. The chip stays at least 24 by 24 CSS pixels. |

### Without JavaScript

| # | Do this | Expect |
|---|---------|--------|
| 13 | Disable JavaScript and press the star | The mark still changes. The page reloads rather than swapping the region, which is the acceptable degradation. |

### Compartments, which must be proven by hand as well as by test

| # | Do this | Expect |
|---|---------|--------|
| 14 | Take a contact uuid from one vault and put it in another vault's contact URL, then press the star | 404. |
| 15 | Sign in as a second account and submit the first account's contact uuid | 404, indistinguishable from a contact that does not exist. |

Steps 14 and 15 are covered by the controller test. Doing them once by hand is still worth it,
because a compartment leak is the one defect in this feature that would matter.

## What is deliberately not validated here

The JSON API. This feature ships no endpoint, by the decision recorded in
[research.md](./research.md) decision 1. The contract the later work should implement is written in
[contracts/favourite-a-contact.md](./contracts/favourite-a-contact.md).
