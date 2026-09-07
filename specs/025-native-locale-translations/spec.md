# Feature Specification: Native quality translations for twenty two new locales

**Feature Branch**: `2026-09-07-native-locale-translations`

**Created**: 2026-09-07

**Status**: Draft

**Input**: User description: "add translations for new locales: Bengali, Catalan, Danish, English, Filipino, Finnish, French, Greek, Hindi, Hungarian, Icelandic, Irish, Italian, Japanese, Korean, Lithuanian, Polish, Portuguese, Simplified Chinese, Sinhala, Spanish, Swedish, Tamil, Thai, Turkish, Uzbek, Vietnamese and norwegian. i know we already support some of these. make sure the translations for each one are accurate and as if it was spoken by a native"

## Overview

The product ships in nine locales today and holds 2,206 translatable strings, roughly 16,000 words, of which 451 carry a placeholder and 81 vary their wording by a number. The request names 28 languages. Six of them are already shipped (English, French, Italian, Portuguese in two variants, Spanish, Turkish), so the work is twenty two new locales and an accuracy pass over the ones already there. German and Dutch are shipped but not named in the request, and they are reviewed with the rest.

A locale is complete or it is not offered. A key missing from a locale falls back to the English sentence, so a half translated language reads as two languages at once on the same screen.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Read the product in my own language (Priority: P1)

Somebody whose language is one of the twenty two new ones picks it in the language picker, or arrives with their browser announcing it, and every screen, email and notification reads in that language.

**Why this priority**: it is the whole request. One completed locale is already a working product for the people who read it.

**Independent Test**: register one new locale, translate its file in full, sign in with it selected, and walk the signed in screens, the credentials screens and one email without seeing an English sentence.

**Acceptance Scenarios**:

1. **Given** a registered locale with a complete file, **When** the user selects it in preferences, **Then** every screen they open reads in that language and nothing falls back to English.
2. **Given** a browser announcing a language the product ships in exactly one variant of, **When** a signed out visitor arrives, **Then** the product answers in that locale.
3. **Given** a registered locale, **When** the language picker is opened, **Then** the locale appears with its name written in its own language, its region written in its own language, and its flag.
4. **Given** the shipped translation files, **When** `monica:localize --check` runs, **Then** no locale reports a missing or a stale string.

---

### User Story 2 - Recognise my own language, not a translation of English (Priority: P1)

A native speaker of a shipped language reads a screen and finds the wording a person from their country would use, in the plain, calm register the English source keeps, with the terminology consistent from one screen to the next.

**Why this priority**: the request asks for this explicitly, and a locale that reads as machine output is worse than English for a bilingual reader. It applies to the twenty two new locales and to all nine already shipped.

**Independent Test**: a native reviewer reads a sample of a hundred strings for one locale, including the log sentences and the counted phrases, and records how many they would change.

**Acceptance Scenarios**:

1. **Given** a translated locale, **When** a native reviewer reads the sample, **Then** at least 95 of the hundred strings are accepted without change.
2. **Given** a log sentence, **When** it is read in a locale, **Then** it reads as something somebody did, in the tense that language uses for a completed action.
3. **Given** one product term, **When** it is read across several screens of one locale, **Then** it is the same word every time.
4. **Given** any locale already shipped, **When** it is reviewed the same way, **Then** the strings that fail the review are corrected.

---

### User Story 3 - Numbers, dates and counts read correctly (Priority: P2)

A user sees a count, a date and a formatted number written the way their language writes them, at every number and not only at two.

**Why this priority**: a wrong plural is the mistake a reader notices first, and the plural systems in this list are wider than the two wordings the English source is written with.

**Independent Test**: render every counted phrase in one locale at 0, 1, 2, 5 and 21 and confirm each reads correctly.

**Acceptance Scenarios**:

1. **Given** a locale whose language asks for three wordings (Irish, Lithuanian, Polish), **When** a counted phrase is translated, **Then** it carries three wordings and each of them interpolates `:count`.
2. **Given** a locale whose language asks for one wording (Japanese, Korean, Simplified Chinese, Thai, Vietnamese, Sinhala, Uzbek), **When** a counted phrase is translated, **Then** it carries one wording.
3. **Given** a phrase with a wording pinned to exactly one number, **When** the count is zero, **Then** the reader never sees the pinned wording.
4. **Given** a locale, **When** a date and a number are shown, **Then** they are written in the order and with the separators that language uses.

---

### User Story 4 - The language's own punctuation is enforced (Priority: P3)

A language that has written punctuation rules has them declared, so the check refuses a value that breaks them rather than leaving it to a reviewer to notice.

**Why this priority**: it is what keeps a locale native after the first pass, when strings are added one at a time by somebody who does not read the language. It is worthless without a translated file, so it comes last.

**Independent Test**: declare the rules for one locale, break one string on purpose, and confirm the check names the value and the rule.

**Acceptance Scenarios**:

1. **Given** a locale that declares its punctuation, **When** a value uses the wrong quotation mark or the wrong apostrophe, **Then** the check fails and names the value.
2. **Given** a locale that declares nothing, **When** the check runs, **Then** it passes without checking that locale.
3. **Given** a locale with declared rules, **When** the rules are written, **Then** the language has a section in the translations rules file saying what they are and why.

### Edge Cases

- A phrase written in English with two wordings meeting a language that asks for one, or for three. The framework decides the count, and it disagrees with CLDR for Sinhala and Uzbek, which it treats as asking for one wording.
- A script with no apostrophe and no quotation convention of the European kind: Japanese, Simplified Chinese and Thai. Declaring European punctuation for them would be wrong.
- A locale registered before its file is complete, which shows English inside an otherwise translated screen.
- A browser announcing a bare language code. Portuguese is the only shipped language with two variants, and no locale added here creates a second one.
- Text that expands or contracts: Finnish, Greek and Hungarian run long, Chinese, Japanese and Korean run short. Both have to hold at 200% zoom.
- A string carrying HTML or Markdown, which has to survive identically in all 31 files.
- An English word used in two senses, which most of these languages will need two words for.
- Uzbek, which is written in both a Latin and a Cyrillic script.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The product MUST register twenty two new locales, identified by ISO 15897 codes: `bn_BD`, `ca_ES`, `da_DK`, `el_GR`, `fi_FI`, `fil_PH`, `ga_IE`, `hi_IN`, `hu_HU`, `is_IS`, `ja_JP`, `ko_KR`, `lt_LT`, `nb_NO`, `pl_PL`, `si_LK`, `sv_SE`, `ta_IN`, `th_TH`, `uz_UZ`, `vi_VN`, `zh_CN`.
- **FR-002**: Each registered locale MUST carry the language written in its own language, the region written in its own language, the date order that language reads, a number format the product already supports, and a flag drawn with gradients rather than an image.
- **FR-003**: Each registered locale MUST hold a translation for every string the product asks for, with nothing missing and nothing stale.
- **FR-004**: A translation MUST preserve every placeholder and every piece of markup exactly as the English source carries them.
- **FR-005**: A counted phrase MUST carry the number of wordings its language asks for, and every wording that is not pinned to a single number MUST interpolate the count.
- **FR-006**: A phrase whose wording does not change with its number MUST say so explicitly rather than be left to the plural rules.
- **FR-007**: The product MUST NOT offer a locale whose file is incomplete.
- **FR-008**: Translation files MUST be produced by the localization command, never written or reordered by hand.
- **FR-009**: The language pickers, the preference validation and the locale resolution MUST pick up a new locale from its registration alone, with no change of their own.
- **FR-010**: The product MUST record, per locale, whether somebody who reads the language natively has verified it. A locale nobody has verified still ships, marked as awaiting review, and the record MUST name the locales still waiting.
- **FR-011**: Every locale already shipped MUST be reviewed against the same standard and corrected where it fails, German and Dutch included, so that no shipped language is left unreviewed.
- **FR-012**: A locale MUST declare its written punctuation only once a reviewer who reads the language has confirmed the rules. A locale that declares nothing is not checked, and that is an acceptable state to ship in.
- **FR-013**: A locale that declares its punctuation MUST have a section in the translations rules file stating the rule and the reason for it.
- **FR-014**: The localization check MUST pass over every configured locale.
- **FR-015**: Each locale MUST arrive in its own change, registering that locale and its complete files and nothing else, so that one reviewer reads one language and a finished locale ships without waiting for the others. A review correcting a locale already shipped is its own change on the same terms.
- **FR-016**: The strings the framework supplies, meaning the validation, authentication, password reset and pagination messages, MUST be translated in every configured locale as well. They read as English today in all nine.

### Key Entities

- **Locale registration**: what the product knows about a language before any string is translated. Its code, its name and region in its own language, its date order, its number format, its flag, and optionally its punctuation rules.
- **Translation file**: one file per locale, holding the translated value for each English string, complete or absent, never partly filled.
- **Counted phrase**: a string that reads differently depending on a number. It carries one wording per plural category of its language.
- **Punctuation rules**: what a language declares about its apostrophe, its quotation marks, the spaces it puts before a mark, and the words it refuses.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: All 31 configured locales report zero missing and zero stale strings.
- **SC-002**: Where a native reviewer has read a locale, they accept at least 95 strings out of a sample of 100 without change. A locale awaiting a reviewer is named as such rather than counted against this.
- **SC-003**: A walk through the signed in screens, the credentials screens and the emails in each locale shows no English sentence, and neither does a form rejected for a validation error.
- **SC-004**: Every counted phrase renders correctly at 0, 1, 2, 5 and 21 in every locale.
- **SC-005**: The language picker lists all 31 locales, each with its own name, its region and its flag.
- **SC-006**: No screen overflows or truncates at 200% zoom in the longest and the shortest locale.
- **SC-007**: A user arriving with a browser announcing one of the 31 languages is answered in it without choosing anything.
- **SC-008**: Each locale is delivered in a change that touches that locale alone, so a reviewer reads one language at a time.

## Assumptions

- Where a language is spoken in several places, the locale is the one with the largest readership: Bengali as spoken in Bangladesh, Tamil as written in India, Norwegian as Bokmål, Chinese as simplified script for mainland China, Filipino for the Philippines.
- Uzbek is registered in the Latin script, which is the official one. Cyrillic is not offered.
- Catalan is registered against Spain, and its flag is the Senyera rather than a state flag.
- German and Dutch stay registered and are not removed by this work, since the request adds languages rather than replacing the list.
- No language in the list is written right to left, so no layout work is needed for reading direction.
- The order of given name and family name stays a setting the user chooses, and a locale does not change its default. Languages that write the family name first are affected, and that is existing behaviour rather than part of this work.
- A first pass may be produced with machine assistance. A locale ships on it, marked as awaiting a native reviewer, rather than waiting for one who may never be found. The feature is finished per locale, not all at once.
- Text a user wrote is never translated. Only the strings the product ships are in scope.
