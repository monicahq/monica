# Research: Native quality translations for twenty two new locales

## Locale codes and regional variants

**Decision**: ISO 15897 codes, the variant with the largest readership. `bn_BD`, `ca_ES`, `da_DK`,
`el_GR`, `fi_FI`, `fil_PH`, `ga_IE`, `hi_IN`, `hu_HU`, `is_IS`, `ja_JP`, `ko_KR`, `lt_LT`, `nb_NO`,
`pl_PL`, `si_LK`, `sv_SE`, `ta_IN`, `th_TH`, `uz_UZ`, `vi_VN`, `zh_CN`.

**Rationale**: the `add-locale` skill requires ISO 15897. Uzbek is Latin script, which is the
official one and what Carbon's `uz_UZ` carries. Norwegian is Bokmål, the written standard the large
majority reads.

**Alternatives considered**: `tl_PH` for Filipino, rejected because Carbon ships neither `tl` nor
`tl_PH`. Cyrillic Uzbek, rejected as the minority script. `nn_NO`, rejected as the minority standard.

## How many wordings a counted phrase needs

**Decision**: whatever `Illuminate\Translation\MessageSelector::getPluralIndex` says, which is what
`LocalizeCommand::wordingsNeeded()` consults over the range 0 to 200.

| Wordings | Locales |
| --- | --- |
| 1 | `ja_JP`, `ko_KR`, `zh_CN`, `th_TH`, `vi_VN`, `si_LK`, `uz_UZ` |
| 2 | `bn_BD`, `ca_ES`, `da_DK`, `el_GR`, `fi_FI`, `fil_PH`, `hi_IN`, `hu_HU`, `is_IS`, `nb_NO`, `sv_SE`, `ta_IN` |
| 3 | `ga_IE`, `lt_LT`, `pl_PL` |

**Rationale**: the framework is what the check enforces, so the framework is the authority. Sinhala
and Uzbek are absent from Laravel's plural table and fall to the default of one wording, although
CLDR lists two for both. Following CLDR here would fail `monica:localize --check`.

## Carbon locale data

**Decision**: no work needed. All twenty two codes have a file under
`vendor/nesbot/carbon/src/Carbon/Lang/`, verified against the installed version.

**Rationale**: dates are not merely numeric. `DateFormatEnum::write()` calls `translatedFormat()`,
`MonthSpelled` is `j F Y`, and there are about 30 `isoFormat` and 25 `diffForHumans` call sites. A
code Carbon does not recognise would silently render English month names.

## The strings the framework supplies

**Decision**: publish `lang/en/{validation,auth,passwords,pagination}.php` with `php artisan
lang:publish`, then translate the 148 strings per locale by hand.

**Rationale**: `lang/` holds no PHP subdirectories today, so Laravel's own messages read as English
in all nine shipped locales. The application passes rules and no messages to every
`$request->validate()`, so a rejected form speaks English whatever the reader chose. `monica:localize`
manages JSON only, which is why the closing change adds a test asserting key parity with `lang/en/`.

**Alternatives considered**: `laravel-lang/common`, which ships these for about 80 locales under MIT.
Rejected against precedent: spec 006 decided to reproduce `asbiin/laravel-localizer` in this
repository rather than depend on it, and a dependency needs approval. Leaving the gap, rejected
because it defeats the feature's own success criterion.

## Whether a locale ships before a native reviewer reads it

**Decision**: it ships, marked as awaiting review in `.claude/rules/translations.md`.

**Rationale**: no reviewer is identified for any of the twenty two languages. Holding every locale
until one appears means the feature never finishes, and an unreviewed translation is still better
for its reader than English. The mark is internal, not shown in the product.

**Alternatives considered**: holding each locale until reviewed, rejected as unshippable. Asking
users to report wording in the product, rejected as a feature of its own with nowhere to receive
corrections yet.

## The locale code that does not fit

**Decision**: widen the five `locale` columns from `string(5)` to `string(12)` by editing the
migrations, and `StructureFileReader::LOCALE` from `/^[a-z]{2}(_[A-Z]{2})?$/` to
`/^[a-z]{2,3}(_[A-Z]{2})?$/` with its length cap raised to match.

**Rationale**: `fil_PH` is six characters with a three letter subtag and fails both. The house rule
edits migrations until production. It also unblocks any future script qualified code.

**Alternatives considered**: dropping Filipino, rejected because the request names it.
