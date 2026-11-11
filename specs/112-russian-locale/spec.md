# Feature Specification: Russian locale

**Feature Branch**: `2026-10-12-russian-locale`

**Created**: 2026-10-12

**Status**: Draft

**Input**: User description: "add russian language support."

## Overview

The product ships in 31 locales and holds 3,588 translatable strings. Russian is not one of them. This feature adds Russian as spoken in Russia, registered as `ru_RU`, translated in full. Russian differs from most shipped locales in two ways that matter here. It writes in Cyrillic, which affects how lists of names are sorted. It has three plural wordings (one, few, many: 1 контакт, 2 контакта, 5 контактов, 21 контакт), where English has two.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Read the product in Russian (Priority: P1)

A Russian speaker picks Russian in the language picker, or arrives with a browser announcing Russian, and every screen, email and notification reads in Russian.

**Why this priority**: it is the whole request. Without it nothing else in this feature has a reader.

**Independent Test**: select Russian in preferences, walk the signed in screens, the sign in screens and one email, and find no English sentence.

**Acceptance Scenarios**:

1. **Given** the Russian locale is registered, **When** the language picker is opened, **Then** Russian appears with its name and its region written in Russian, and its flag.
2. **Given** a user who selected Russian, **When** they open any screen, **Then** every string reads in Russian and none falls back to English.
3. **Given** a signed out visitor whose browser announces Russian, **When** they arrive, **Then** the product answers in Russian.
4. **Given** a user who selected Russian, **When** the product sends them an email, **Then** the subject and the body read in Russian.
5. **Given** the shipped translation files, **When** the localization check runs, **Then** Russian reports no missing and no stale string.

---

### User Story 2 - Counts, dates and numbers read correctly in Russian (Priority: P1)

A Russian reader sees counts with the right wording at every number, and dates and numbers written the way Russian writes them.

**Why this priority**: a wrong plural is the mistake a Russian reader notices first, and Russian needs a third wording that English never supplies. It ships with story 1 or the locale reads as broken.

**Independent Test**: render every counted phrase in Russian at 0, 1, 2, 5, 11, 21 and 22, and confirm each uses the correct wording. Show a date and a number with the Russian defaults.

**Acceptance Scenarios**:

1. **Given** a counted phrase, **When** it is shown at 1, 21 or 101, **Then** it uses the singular wording.
2. **Given** a counted phrase, **When** it is shown at 2, 3, 4, 22 or 24, **Then** it uses the "few" wording.
3. **Given** a counted phrase, **When** it is shown at 0, 5, 11, 12, 14 or 25, **Then** it uses the "many" wording.
4. **Given** a user with Russian selected and no date preference of their own, **When** a date is shown, **Then** it reads day, month, year separated by dots (12.10.2026).
5. **Given** a user with Russian selected and no number preference of their own, **When** a number is shown, **Then** thousands are separated by a space and decimals by a comma (1 234,56).

---

### User Story 3 - Lists sort in Russian alphabetical order (Priority: P2)

A user whose contacts carry Cyrillic names sees them sorted in Russian alphabetical order, with Ё placed after Е and before Ж, rather than in the order of their character codes.

**Why this priority**: the screens work without it, but a list where Ё sorts after Я looks broken to a Russian reader. It follows the locale aware sorting already in place for other languages.

**Independent Test**: create contacts named Анна, Ёлкин, Евгений, Жанна and Яна, view the list with Russian selected, and confirm the order Анна, Евгений, Ёлкин, Жанна, Яна.

**Acceptance Scenarios**:

1. **Given** contacts with Cyrillic names, **When** a list sorted by name is shown in Russian, **Then** the names follow Russian alphabetical order.
2. **Given** a list mixing Latin and Cyrillic names, **When** it is shown in Russian, **Then** the order is stable and every name appears once.

---

### Edge Cases

- A Russian string longer than its English source (Russian runs around 20 to 30 percent longer) must not break a button, a menu or a table header. Screens that truncate must still show enough to be understood.
- A counted phrase whose English source declares one wording for every number (`[0,*]`) still has to read correctly in Russian at 1, 2 and 5. Where it does not, the Russian value carries the three wordings.
- A phrase pinned to one number (`{1}`) keeps its pin in Russian, and zero still never reaches it.
- Search ignores accents, which in Russian turns Ё into Е (wanted: Russian readers often write Е for Ё) but also Й into И, two distinct letters. A search for "Мой" must still find "Мой" and the alphabetical headings of a list must keep Й apart from И.
- Content a user wrote stays in the language its author wrote it in. Choosing Russian translates nothing a user typed.
- The framework strings (validation messages, password reset, pagination, HTTP statuses) read in Russian as well, not only the product's own strings.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The product MUST offer Russian (Russia) as a selectable locale, named "Русский" with the region "Россия", next to the other shipped locales.
- **FR-002**: Every product string MUST be translated into Russian before the locale ships. No Russian screen may fall back to English.
- **FR-003**: The framework strings the product relies on (validation, authentication, password reset, pagination, actions, HTTP statuses) MUST be available in Russian.
- **FR-004**: Every counted phrase MUST carry the three wordings Russian needs, and each MUST interpolate the count, following the counted phrase rules in the code style.
- **FR-005**: Placeholders in every string MUST be kept unchanged in the Russian value.
- **FR-006**: Russian MUST default to the date order day, month, year with dots, and to a space for thousands and a comma for decimals.
- **FR-007**: Russian MUST address the reader formally, as *вы*, and keep the plain, calm register of the English source.
- **FR-008**: Each product noun in the glossary MUST be translated once and used identically on every screen. The Russian column of the glossary MUST be filled.
- **FR-009**: A browser announcing Russian MUST be answered in Russian when the visitor has no preference of their own.
- **FR-010**: Lists sorted by name MUST follow Russian alphabetical order when Russian is the active locale.
- **FR-011**: The record of which locales a native speaker has read MUST list Russian, marked as not yet read by a native speaker.
- **FR-012**: The test recording how many wordings each locale's plural rules ask for MUST list Russian with three.
- **FR-013**: The localization check MUST pass for every locale once Russian is added.

### Key Entities

- **Locale**: a language and region the product ships in. For Russian: its code, its name and region in Russian, its flag, its default date order and its default number format.
- **Translation file**: the set of Russian values, one per product string, plus the framework strings.
- **Glossary entry**: one product noun and the single Russian word chosen for it.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: 100 percent of the product's 3,588 strings have a Russian value at release, and the localization check reports zero missing strings for Russian.
- **SC-002**: Every counted phrase reads correctly in Russian at 0, 1, 2, 5, 11, 21 and 22.
- **SC-003**: A Russian speaker can sign up, sign in, create a vault and add a contact without meeting a single English sentence.
- **SC-004**: The 17 glossary nouns each map to exactly one Russian word, and a search of the Russian file finds no second translation for any of them.
- **SC-005**: A sample of 100 Russian strings, read by somebody who reads Russian, has at least 95 accepted without change. Until that reading happens, the locale is recorded as not read by a native speaker.

## Assumptions

- The locale is Russian as written in Russia (`ru_RU`). No other Russian variant is in scope.
- Russian declares no typography rules for the localization check in this feature. The rules say to declare them only when somebody who reads the language does, and Russian guillemets (`«…»`) are used in the translations without being enforced. Declaring them is a separate change.
- The flag is the Russian tricolour (white, blue, red, horizontal), drawn the same way as the other flags.
- The translations are written in this change without a native speaker's review. The locale ships anyway, as every other locale did, and the native speaker record says so.
- Locale aware sorting already exists for other languages and only needs to recognise Russian, not to be redesigned.
- The order of given name and family name stays a per user setting and is not changed by choosing Russian.
- Marketing pages and the API documentation portal are in scope only where they already read from the shared translation files.
