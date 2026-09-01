# Feature Specification: Locale resolution modelled on laravel-localizer

**Feature Branch**: `006-import-laravel-localizer`

**Created**: 2026-08-30

**Status**: Draft

**Input**: User description: "Import https://github.com/asbiin/laravel-localizer/ in the project"

## Clarifications

### Session 2026-08-31

- Q: What should an API response be translated into? → A: The language the request announces in `Accept-Language`, then the signed in user's stored preference, then the fallback. No session, no cookie.
- Q: How should a language announced by the reader's browser be matched to the nine locales this project ships? → A: On the language subtag, preferring the region when the browser gives one. `fr-FR` and `fr` both reach `fr_FR`; `pt-BR` reaches `pt_BR` and `pt-PT` reaches `pt_PT`; bare `pt` reaches a declared default. The shipped locale keys do not change.
- Q: Should a signed out reader's language choice be kept on their device after their session ends? → A: Yes, persisted on the device for a long period, the package's default being one year.
- Q: Should the product depend on `asbiin/laravel-localizer` as a package, or have the behaviour live in Monica? → A: In Monica. The product takes on no new dependency for this, and the behaviour is reproduced in this repository under this project's own conventions.
- Q: On the API, which wins when the request announces one language and the token's user has a different one stored? → A: The announced language wins. This confirms the amended answer to the first question of this session and restores the body changes that follow from it.
- Q: Does the code still need to be built as a chain of separate reader and writer classes, now that nothing is being copied? → A: No. One resolver holding a method per source and one per ordering, plus the two middlewares. It lands in a folder the project already has, so no new top level folder and no amendment to the layout. This supersedes an earlier answer naming `app/Localizer/` with `Detectors/` and `Stores/`, which was given when upstream's files were going to be brought across.
- Q: Should the code be copied from `asbiin/laravel-localizer`, or written here from its design? → A: Written here. The product takes what that project does and how it is shaped, and reproduces the behaviour in Monica. No file is copied, so no third party licence enters the repository and there is nothing to credit in a file header. This supersedes an earlier answer about crediting original authors, which assumed copied files.
- Q: How much of the package's design should be reproduced? → A: Only what the two chains use. The ordered chain of sources, the readers and writers those chains name, and the language matching this project needs. Readers for a locale in the URL or a domain, and the settings feeding them, stay out.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - The interface keeps speaking the language I picked (Priority: P1)

Somebody who has already chosen a language, either from the language switcher or from their account
preferences, keeps seeing the interface in that language on every screen of the product: the signed
in application, the sign in and registration screens, the API reference at `/docs/api`, and the
screens reached from an email link. The choice survives moving between those areas, and it is not
lost because a particular screen was reached by a route that somebody forgot to opt in.

**Why this priority**: this is what the product does today, and the change must not take it away.
Language selection reaching every screen is the baseline the rest of the feature is built on. Shipped
on its own it delivers a real improvement, because language resolution stops being something each
route has to remember to ask for.

**Independent Test**: pick a language from the switcher, then walk the signed in screens, the guest
screens and the API reference, and confirm every one of them is in that language.

**Acceptance Scenarios**:

1. **Given** a visitor who is not signed in and has chosen French from the language switcher, **When** they open the sign in screen, **Then** the screen is in French.
2. **Given** a signed in user whose account preference is German and who has made no choice in this session, **When** they open any screen of the application, **Then** the screen is in German.
3. **Given** a signed in user whose account preference is German, **When** they pick Spanish from the language switcher, **Then** every following screen is in Spanish until they choose otherwise, and their stored account preference is unchanged.
4. **Given** any screen of the product, **When** it is reached without the route having been individually opted in to language resolution, **Then** it is still shown in the reader's language.

---

### User Story 2 - A new visitor lands in a language they can read (Priority: P2)

Somebody who has never been to the product before, and therefore has no account preference and has
made no choice, is shown the interface in a language their browser says they read, when the product
ships that language. Otherwise they are shown the fallback language.

**Why this priority**: it is the reason for doing any of this rather than leaving the resolver alone,
and it is the one behaviour change a reader can notice. It depends on nothing
in User Story 1 and can be turned on or off on its own.

**Independent Test**: open the product with no session and no account, with a browser announcing a
supported language, and confirm the landing screen is in that language; repeat with a browser
announcing only unsupported languages and confirm the fallback.

**Acceptance Scenarios**:

1. **Given** a first-time visitor whose browser announces French before English, **When** they open the sign in screen, **Then** the screen is in French, whether the browser named a region alongside the language or only the language.
2. **Given** a first-time visitor whose browser announces Brazilian Portuguese, **When** they open the sign in screen, **Then** the screen is in the Brazilian Portuguese the product ships, and not in the European one.
3. **Given** a first-time visitor whose browser announces Portuguese without saying which, **When** they open the sign in screen, **Then** the screen is in whichever of the two the product declares as the answer for that language.
4. **Given** a first-time visitor whose browser announces only a language the product does not ship, **When** they open the sign in screen, **Then** the screen is in the fallback language.
5. **Given** a visitor who has explicitly chosen a language, **When** their browser announces a different language, **Then** their explicit choice wins.

---

### User Story 3 - My language survives coming back tomorrow (Priority: P3)

Somebody who is not signed in and has chosen a language keeps that language when they come back
after their session has expired, without having to choose again.

**Why this priority**: a convenience for guests, and the only part of the feature that persists
anything on the reader's device. It is worth having and worth being able to drop without touching
the rest.

**Independent Test**: choose a language while signed out, discard the session, return, and confirm
the language is still the chosen one.

**Acceptance Scenarios**:

1. **Given** a signed out visitor who chose Italian and whose session has since expired, **When** they return to the product, **Then** the interface is in Italian.
2. **Given** that same visitor, **When** they clear the state their browser holds for the product, **Then** language resolution starts again from their browser's announced languages.
3. **Given** a visitor who chose Italian on this device while signed out, **When** they sign in to an account whose preference is German, **Then** the interface is in German, because an account preference outranks an earlier choice on the device.

---

### Edge Cases

- A stored or announced language that the product no longer ships. Resolution must skip it and carry
  on down the chain rather than showing an untranslated screen or failing.
- A device announcing a language the product ships in two regions, without naming a region. One of
  the two has to be declared the answer, because picking either silently is a coin toss a reader
  notices.
- A device announcing a region the product does not ship for a language it does, for example Swiss
  French. The language has to win over the region rather than the whole value being discarded.
- A device announcing several languages in order of preference. The first one the product ships wins,
  and the ones ahead of it that it does not ship are skipped rather than ending the chain.
- A request with no session at all, which is every API request. Reading a session that was never
  started must not raise an error, and the request must still be answered in a language.
- An API request with no signed in user, which is what creating an account and signing in are. The
  language it announces has to be honoured, because there is no preference to read.
- An API request whose announced language disagrees with the preference stored against the token's
  user. What the request asked for wins, because it asked; the two never have to be reconciled.
- An API request announcing a language the product does not ship. It has to fall through to the
  user's preference rather than being answered in the fallback language.
- A language chosen through the switcher on a screen that is itself reached before language
  resolution has run. The redirect that follows must already be in the new language.
- Two sources disagreeing, for example an account preference in German and a session choice in
  Spanish. The order of precedence must be fixed and observable, never dependent on which screen is
  being viewed.
- A value forged into whatever the reader's device sends, including what is kept on the device. An
  unsupported or malformed language must never become the active language.
- A language kept on a device from before a locale was withdrawn from the product. It must be skipped
  the same way any other unshipped value is, rather than showing an untranslated screen.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: The product MUST resolve the reader's language from an ordered chain of sources, and MUST use the first source that yields a language the product ships.
- **FR-002**: The order MUST be: a choice made in the current session, then the signed in user's stored account preference, then a choice made earlier on this same device, then the language the reader's device announces, then the fallback language configured for the application. A signed in user's account preference therefore outranks a choice they made on that device before signing in.
- **FR-003**: The product MUST ignore any resolved value that is not one of the languages it ships, and MUST continue down the chain instead of adopting it.
- **FR-004**: A language announced by the reader's device MUST be matched to a shipped locale on its language, not on the exact form the device wrote it in. A device naming a language the product ships MUST reach that language whether or not it also named a region, and whether it separated the two with a dash or an underscore.
- **FR-005**: Where the product ships more than one locale for the same language, a device that names a region MUST reach the locale shipped for that region, and a device that names only the language MUST reach the one locale declared as the answer for that language. Portuguese is the only such language today, and its answer MUST be declared beside the locales themselves rather than inferred.
- **FR-006**: A language a device announces MUST NOT be able to select anything other than a locale the product ships. A value matching nothing MUST be skipped and the next language the device announces considered, before the chain moves on.
- **FR-007**: The set of languages the product ships MUST have exactly one definition, the one already used by the language switcher, the account preferences screen and the translation tooling. Adding a language MUST NOT require declaring it in a second place.
- **FR-008**: Language resolution MUST apply to every screen of the product without a route having to ask for it, covering the signed in application, the guest and authentication screens, and the public API reference.
- **FR-009**: Language resolution MUST run after the session is available and before route parameters are resolved into records.
- **FR-010**: Choosing a language from the switcher MUST take effect immediately, on the screen the reader is returned to.
- **FR-011**: Choosing a language from the switcher MUST NOT alter the signed in user's stored account preference, and saving an account preference MUST take effect immediately as well.
- **FR-012**: A request carrying no session MUST be resolved without error, using the sources that do not need one.
- **FR-013**: The product MUST answer an API request in a language resolved from an ordered chain of its own: the language the request announces, then the signed in user's stored account preference, then the fallback language. A caller that asks for a language therefore gets it, whoever the token belongs to. Neither a session nor anything stored on the caller's device takes part, and nothing is stored as a result of an API request.
- **FR-014**: An API request that announces no language MUST fall through to the signed in user's stored account preference, and an API request that carries no signed in user MUST be answered in the language it announces, which covers the routes that create an account and sign in. A request with neither is answered in the fallback language.
- **FR-015**: The product MUST remember a reader's chosen language on their device for at least a year, so that it survives the end of a session, and MUST resolve from it at the position FR-002 gives it.
- **FR-016**: A choice made from the language switcher MUST be recorded both for the current session and on the device, in one action, so that the two never disagree.
- **FR-017**: What is kept on the device MUST be the chosen language and nothing else, and MUST be readable and clearable by the reader through their browser like any other site preference.
- **FR-018**: The behaviour MUST be covered by tests that exercise each source of the chain, the precedence between them, and the rejection of an unsupported value.
- **FR-019**: Nothing the reader can see MUST change apart from what these requirements describe. In particular the language switcher, the account preferences screen and the wording of every screen stay as they are.

### Key Entities

- **Supported language**: one of the languages the product ships its interface in. It is the unit everything here is decided in, it already carries a label, a region, a date format, a number format and punctuation rules, and it is defined in exactly one place. Two of them share a language, and one of those two is declared the answer for a device that names the language and no region.
- **Language choice**: what a reader picked from the language switcher. It is recorded twice, once for the current session where it outranks every other source, and once on the device where it outlives the session and sits below an account preference. It is not the same thing as an account preference, and picking one never changes one.
- **Account preference**: the language stored against a signed in user. It survives sessions and devices, and on the API it answers whenever the caller asked for no particular language.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Every screen of the product is shown in the reader's language, on 100% of routes, with no route needing to opt in.
- **SC-002**: A first-time visitor whose device announces a language the product ships lands on a screen in that language on their first request, with no action on their part.
- **SC-003**: All nine shipped locales are reachable by browser detection, not only the ones whose name happens to match the form a browser writes.
- **SC-004**: A reader who picks a language from the switcher sees the very next screen in it, 100% of the time.
- **SC-005**: An account preference and a session choice that disagree resolve the same way on every screen, with zero screens showing the other language.
- **SC-006**: A signed out reader who chose a language and returns after their two-hour session has expired sees that language, rather than starting over, in 100% of cases where their browser still holds the product's state.
- **SC-007**: A language the product does not ship, arriving from any source, is never displayed to a reader, in 0 cases out of the full set of sources.
- **SC-008**: Every translated message the API returns today is still returned translated after the change, on both the authenticated and the unauthenticated routes.
- **SC-009**: An API caller can choose the language of a response for any request, authenticated or not, without holding an account in that language.
- **SC-010**: Language resolution happens in one place. No screen, route, controller or view contains a rule about which language to use, so changing the order of the sources is a change in one file.
- **SC-011**: Adding a tenth language requires touching the one existing definition of supported languages and its translation file, and nothing else.
- **SC-012**: The product resolves a language from exactly the five sources this specification names and from no others, and each of the five is covered by a test. Nothing in the product can be configured to read a sixth.
- **SC-013**: No file added by this feature reproduces another project's text, and no third party licence notice enters the repository. A licence audit of this change has nothing to account for.

## Assumptions

- "Import in the project" means reproducing the behaviour inside this repository rather than reaching
  for a package. The product takes on no new runtime dependency for this feature, and nothing here is
  resolved by code the project does not own.
- What `asbiin/laravel-localizer` contributes is its design and the behaviour it demonstrates, not its
  text. The design is an ordered chain of small sources, each reading one place and returning a
  language or nothing, with a set of writers recording whichever answer wins. That is what this feature
  adopts, along with the precedence the package's own defaults suggest. Every file is written here,
  against this specification and this project's conventions.
- No file reproduces another project's text, so no third party licence enters the repository and there
  is nothing to credit in a file header. The distinction is real and has to hold in the code rather
  than only in intent: a shape and an approach may be borrowed freely, a file that is the original with
  its names changed is still the original. A reviewer should be able to open any file here and see
  something written for this product rather than something adapted to it.
- Most of these pieces are a few lines each, reading a session key or a column and returning it. Short
  functional code like that converges on the same answer whoever writes it, and that is not a problem.
  Attention belongs on the parts with enough structure to carry real expression.
- The upstream project is worth naming in the pull request as the source of the design, as a courtesy
  and so the next reader knows where the shape came from. Nothing tracks it, no upgrade path to it is
  implied, and it is a dependency of no kind.
- Everything here is this project's code: it follows this project's conventions, is covered by this
  project's tests, and is maintained here.
- The design is borrowed at the level of behaviour rather than of arrangement. Upstream expresses the
  chain as a registry of interchangeable readers and writers, which is what a package needs so its
  host can rearrange it. This product knows its two orderings and SC-012 asks that no third be
  configurable, so the same behaviour is expressed as two short sequences in one place. Fewer moving
  parts for the same observable result.
- The new code lands in a folder the project already has, so the fixed repository layout is unchanged
  and no amendment to it is part of this feature.
- The language resolution currently in the product is replaced rather than kept beside the new one.
  Two resolvers disagreeing is exactly the failure this feature exists to remove.
- Putting the language in the URL, as a path segment or a domain, is out of scope. The product has no
  localized URLs today and adding them would rewrite every route and every link, which is a separate
  feature with its own reasons. Nothing supporting it is built either, so the capability is absent
  rather than present and unused.
- Only what the two chains use is built. Anything the borrowed design offers that this product does not
  read is left out, because everything here is this project's code to test and maintain, and an
  untested reader for a scheme the product does not have is a liability rather than an option.
- Parsing what a device announces uses what the framework already provides, so this feature adds no
  new runtime dependency at all, not even an indirect one.
- The order of precedence keeps the two sources the product already implements, a session choice
  ahead of an account preference, and adds two below them: a choice kept on the device, then the
  languages the device announces. No existing reader's experience changes except that a first-time
  visitor may now land in their own language and a returning guest keeps theirs.
- The list of supported languages, and everything hanging off it, stays where it is. The new code reads
  that one list rather than being given a second one to keep in step.
- The language switcher, the account preferences screen and the route that saves a choice keep their
  current addresses and behaviour.
- Existing sessions carrying a language chosen before this change keep working after it, because the
  place a session choice is stored does not move.
- The web chain and the API chain deliberately order the same two sources differently. On the web an
  announced language is the browser's ambient setting, which a signed in user never asked for, so
  their account preference outranks it. On the API an announced language is an explicit act by the
  caller on that request, so it outranks the preference stored against the token's user. The two are
  the same rule read in context, not a contradiction.
- What is kept on the reader's device is a functional preference set at their own explicit request, so
  it needs no consent banner, and the product has none to extend.
- Nothing is kept on the device as the result of an API request, and nothing is kept there for a
  reader who never chose a language.
- The nine shipped languages, their labels, regions, formats and punctuation rules are unchanged by
  this feature.
