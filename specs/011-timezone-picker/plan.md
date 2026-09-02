# Implementation Plan: Time zone picker

**Branch**: `2026-09-02-timezone` | **Date**: 2026-09-02 | **Spec**: [spec.md](spec.md)

## Summary

A time zone section on the preferences screen. One catalogue of the 419 canonical IANA zones PHP
already carries, handed to the browser as a search index. A committed SVG world outline, already
projected, so a click becomes a latitude and a longitude by arithmetic. All three ways of choosing,
a search result, a click, the device button, are real submit buttons posting to one endpoint and one
action, so the nearest zone computation is tested in PHP and the API adds transport only.

## Technical Context

| | |
| --- | --- |
| **Language** | PHP 8.4, Laravel 13 |
| **Front end** | Blade, Tailwind 4, Alpine, Alpine Ajax. No new dependency |
| **Storage** | `users.timezone`, already there. No migration |
| **Testing** | PHPUnit, on SQLite, MySQL and PostgreSQL |
| **Zone data** | `timezone_identifiers_list(DateTimeZone::ALL)`, 419 zones, each with a country code and a position from `DateTimeZone::getLocation()` |
| **Country names** | `Locale::getDisplayRegion`, in the reader's language |
| **Constraints** | Offline capable, no third party host, WCAG 2.2 AA, PHPStan level 5, Pint clean |
| **Scope** | One screen, one section. 8 new files, 13 touched |

Details behind these choices, and what was rejected, are in [research.md](research.md).

## Constitution Check

| Principle | How this satisfies it |
| --- | --- |
| I, actions hold the logic | `UpdateTimezone` holds it. The web controller and the API controller both call it and add nothing |
| II, simplicity | No d3 and no geometry library. The projection is two divisions each way, and the map's whole client side is a click handler |
| III, tested before merged | Catalogue, action, view model block, both controllers and the map component all get tests |
| IV, Laravel conventions | Existing `timezone:all` rule, camelCase route names, no `down()` because there is no migration |
| V, localized and safe | Every string through `__()`, country names in the reader's language, `monica:localize` run. Nothing here is destructive |
| VI, nothing changes underfoot | The action writes one column and touches no computed value. Covered by a test that asserts the four formats are unchanged |
| VII, stable core | No new personalisation mechanism. One existing column becomes editable |
| VIII, nothing disappears | Nothing is deactivated or removed |
| IX, accessible | The search field and its results are the keyboard path and work with the map absent. The map is an addition, its guess is stated in words, and the outline is drawn in palette tokens so it holds in both themes |
| X, everything through the API | `PUT /api/v1/me/timezone`, over the same action, tested |
| XI, the record is short | `plan.md` 94 lines. `data-model.md` and `contracts/` omitted: there is no schema change, and the one route is in the table below |

No violations, so Complexity Tracking is omitted.

## Project Structure

```text
app/Helpers/TimezoneCatalogue.php                      # new: the 419 zones, the search index, nearest()
app/Actions/UpdateTimezone.php                         # new
app/Enums/UserActionEnum.php                           # touched: UserTimezoneUpdate
app/Http/Controllers/App/Settings/Account/Preferences/
    TimezoneController.php                             # new: update()
app/Http/Controllers/Api/MeTimezoneController.php      # new: update()
app/ViewModels/Settings/Account/Preferences/
    PreferencesViewModel.php                           # touched: clocks read the zone
    TimezonePanel.php                                  # new: the block beside it
resources/views/components/world-map.blade.php         # new: the projected outline
resources/views/app/settings/account/preferences/
    index.blade.php                                    # touched: the section, and the target list
    _timezone.blade.php                                # new
resources/css/app.css                                  # touched: map water, land, coast, band, both themes
resources/docs/api/04-profile.php                      # touched: the new endpoint, which Principle X and the portal's test both require
routes/web.php, routes/api.php                          # touched: one route each
spec/models/002-user-done.md                           # touched: lines 57 and 63, the zone is editable now
lang/*.json                                            # touched by monica:localize, nine locales

tests/Unit/Helpers/TimezoneCatalogueTest.php
tests/Unit/Actions/UpdateTimezoneTest.php
tests/Unit/ViewModels/Settings/Account/Preferences/TimezonePanelTest.php
tests/Unit/ViewModels/Settings/Account/Preferences/PreferencesViewModelTest.php   # touched
tests/Feature/Controllers/App/Settings/Account/Preferences/TimezoneControllerTest.php
tests/Feature/Controllers/Api/MeTimezoneControllerTest.php
tests/Feature/Views/Components/WorldMapTest.php
```

**Structure Decision**: the existing tree, no new folder. The catalogue is a helper because the
action, the view model and the API all read it. `TimezonePanel` sits beside `PreferencesViewModel`
the way `MarketplaceStatistics` sits beside its own, because the section carries a search index, a
marker position and three clocks that the main view model has no business holding.

### Routes

| Verb | Path | Name | Controller |
| --- | --- | --- | --- |
| PUT | `settings/account/preferences/timezone` | `settings.timezone.update` | `App\...\Preferences\TimezoneController` |
| PUT | `api/v1/me/timezone` | `api.v1.me.timezone.update` | `Api\MeTimezoneController` |

Both take either `timezone`, validated `['required_without:latitude', 'string', 'timezone:all']`,
the rule registration already uses and which accepts exactly the 419 the catalogue offers, or a
bounded `latitude` and `longitude` pair that the controller resolves to the nearest known zone
before calling the action. Which of the two arrived is what decides FR-006's sentence, so no client
supplied flag decides it. [research.md](research.md) section 4 says why the map resolves here
rather than in the browser.
