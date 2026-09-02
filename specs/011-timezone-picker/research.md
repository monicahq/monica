# Research: Time zone picker

## 1. Where the zones come from

**Decision**: `timezone_identifiers_list(DateTimeZone::ALL)`, and `DateTimeZone::getLocation()` for
each one's country code and position.

**Rationale**: 419 identifiers, every one with a latitude, a longitude and a country code, and it
excludes the deprecated aliases (`US/Pacific`) and the `Etc/*` offsets nobody should be offered.
`UTC` is in it, the column's default. The searchable city name is the identifier's last segment, so
`America/Argentina/Buenos_Aires` searches as `Buenos Aires`, and country names come from
`Locale::getDisplayRegion`, already translated. Rejected: the design's hand written 110 cities, a
quarter of the zones and one more thing to maintain; a data package, for data PHP ships.

## 2. Validation

**Decision**: `timezone` is `['required_without:latitude', 'string', 'timezone:all']`, and the pair
is `['required_without:timezone', 'numeric']` bounded to -90..90 and -180..180.

**Rationale**: the framework's `validateTimezone` is `in_array($value, timezone_identifiers_list(DateTimeZone::ALL))`,
so `timezone:all`, which registration already uses, accepts exactly the catalogue's set, with
nothing to keep in step and no custom rule for FR-010.

## 3. The map outline

**Decision**: convert Natural Earth 110m once, offline, and commit the result as an SVG path in
`resources/views/components/world-map.blade.php`, its header comment recording source, projection
and clip. The throwaway converter is not committed.

**Measured**: `countries` from `world-atlas@2.0.2/countries-110m.json`, projected equirectangular to
a 1000 wide box, one decimal place, relative segments, is 91 KB of path and 27 KB compressed. The
merged `land` object is 44 KB and 14 KB, and loses the outlines the design draws in the water colour.

**Clip and aspect**: latitude 84 to -58, the design's own window, giving a 1000 by 394 box. The
design fits it inside a 0.46 ratio box, which letterboxes it; the true ratio removes that and makes
pixels to degrees a straight line with no offset to carry:

```text
lon = x / 1000 * 360 - 180        x = (lon + 180) / 360 * 1000
lat = 84 - y / 394.44 * 142       y = (84 - lat) / 142 * 394.44
```

Geometry below -58 stays in the path and is clipped by the viewBox, the same edge d3 drew.

**Alternatives rejected**: d3-geo with topojson-client, three npm dependencies and, as the design
has it, two CDN requests and a runtime fetch a self hosted install cannot make. Projecting GeoJSON
in PHP on every render, which re-derives a constant.

## 4. Resolving a click

**Decision**: the map submits the latitude and longitude of the click to the same endpoint, and PHP
resolves the nearest known zone.

**Rationale**: the suite has no JavaScript harness, and nearest neighbour over 419 points with an
antimeridian wrap is logic Principle III wants a test for; in PHP the catalogue's test covers it.
The browser stays thin: the handler is two divisions, no positions ship to it, and the marker comes
from the zone the server stored. Which shape arrived tells the screen the zone was a guess, so
nothing the client claims decides that. Nearest is the design's cosine corrected planar distance.

**Cost**: the endpoint takes two shapes. A bounded coordinate can only resolve to a member of the
known set, so an invalid zone still cannot be stored.

**Alternatives rejected**: resolving in the browser and submitting an identifier, one field and one
rule, but the nearest computation goes untested and 12 KB of positions ship with the page. The map
needs JavaScript either way, which is why FR-004 puts the keyboard path in the search field.

## 5. How a choice saves

**Decision**: real submit buttons in an `<x-form method="put">` carrying `x-target`, as `x-dropdown`
and the four format rows already do. A map click writes the latitude and longitude into hidden
fields and calls `requestSubmit()`. The confirmation is the existing `x-toaster`, fed by
`->with('status', ...)` on the redirect Alpine Ajax follows.

**Rationale**: nothing new. The dropdown's comment records why its choices are submit buttons, and
the toaster's records that any controller redirecting with a `status` is confirmed for free.

**Two consequences for `index.blade.php`**: the zone panel shows a clock, so it belongs in the four
format forms' target list, and the format footer now reads in the chosen zone, so it belongs in the
zone form's. FR-006's sentence rides on the redirect as a second flashed value, the way
`status_description` already does.
