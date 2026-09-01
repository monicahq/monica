# Phase 0 Research: Favourite a Contact From Their Page

Four questions had to be settled before the design. One of them, decision 1, was the
`[NEEDS CLARIFICATION]` marker left on FR-014 by `/speckit-specify`. It is resolved here rather than
by the user, on the strength of a rule the constitution already states. If that reading is wrong the
decision is cheap to reverse, because nothing else in the plan depends on it.

## 1. Where the API coverage for this capability is

**Decision**: no API endpoint ships in this feature. The capability is delivered through the signed
in interface only, and the answer to "where is its API coverage" is recorded, not skipped: the
logic sits in `FavouriteContact` and `UnfavouriteContact`, and the new controller adds nothing but
transport, so a `PUT /contacts/{contact}/favourite` can be added later with no rework.

**Rationale**: Principle X says a capability is not complete until its API coverage exists and is
tested, and the spec turned that into FR-014. The constitution's own Development Workflow section
provides the exemption and states its price:

> A change that adds a capability states where its API coverage is. While there is no `Api`
> namespace, the answer is that the logic sits in an action and the controller adds nothing but
> transport, so the API can be added later without rework.

That sentence is stale in one respect and it is worth naming rather than leaning on quietly: the
`Api` namespace now exists. `app/Http/Controllers/Api/` holds `ApiKeyController`, `LogController`,
`EmailSentController`, `MeController` and `StatusController`, and `routes/api.php` is real. What does
not exist is any contacts resource in it. So the exemption's literal condition has expired while its
substance holds exactly: there is nothing for a favourite endpoint to hang off, and inventing the
contacts surface here would mean fixing how a contact is addressed and what a contact resource
returns in order to expose one boolean.

The constitution's sync report already carries this as follow up TODO 2, that there is no contacts
API and every capability shipped before it adds to the debt. This feature adds one item to that
debt, deliberately and in writing.

**Alternatives considered**:

- *Ship a minimal favourite endpoint.* Rejected. It forces the contacts API's addressing and resource
  shape to be decided by the smallest possible consumer. The first contacts endpoint should be the
  one that reads a contact, not the one that flips a flag on it.
- *Build the contacts API in this feature.* Rejected as scope the request did not contain, and large
  enough to be its own spec.
- *Amend the constitution first.* Rejected as disproportionate. The principle is right; only its
  worked example has aged.

**Follow up this creates**: when the contacts API is opened, `PUT /contacts/{contact}/favourite` is
part of it. The contract is written in `contracts/favourite-a-contact.md` so that the later work
inherits a decision rather than repeating this one.

## 2. How the two states are told apart

**Decision**: an outline star for a contact who is not a favourite, and a filled star for one who is.
A new `star-filled` glyph is added to `resources/views/components/icon.blade.php`, carrying
`fill="currentColor"` on its own path.

**Rationale**: Principle IX forbids information carried by colour alone, and FR-004 makes it
testable. The star today sits in a marigold tinted chip, and tint is exactly the thing that may not
be the only signal. Fill against outline is a difference of shape, which survives greyscale, and it
is the convention a user already knows from every other product's favourite control.

This is a deliberate deviation from the icon component's own stated rule, which is worth stating
because the component says it out loud:

> Nothing here is filled: the stroke is `currentColor`, which is what lets a row tint its mark by
> setting a colour on the wrapper rather than by asking for a different drawing.

The rule exists so a glyph can be tinted by its wrapper. A filled star still is: `currentColor`
inherits from the wrapper for the fill exactly as it does for the stroke, so the reason behind the
rule is not damaged. The component keeps `fill="none"` on the `<svg>`, and the one glyph that needs
a fill declares it on its own path, so no prop is added and no other glyph changes.

**Alternatives considered**:

- *One glyph, tinted differently.* Rejected outright. Colour alone.
- *One glyph plus a visible word.* Rejected. It puts a text label in a 32px chip over an avatar, at
  every screen size, to solve a problem fill already solves.
- *A `filled` prop on `<x-icon>`.* Rejected as a prop that exists for one caller. Adding the glyph is
  smaller and keeps the set the single place a drawing lives, which is what the component asks for.

## 3. Whether the control toggles or sets

**Decision**: the control submits the state it wants, not a toggle. The form carries
`favourite=1` or `favourite=0` depending on what is currently drawn, and the controller validates
`['favourite' => ['required', 'boolean']]` and dispatches to the matching action, in the shape
`TemplateActivationController` already uses for a boolean a user flips.

**Rationale**: the spec's edge cases include a page left open while the state changes elsewhere, and
require that pressing the star on a stale page must not silently write over a newer state without
the user knowing what they changed. A blind toggle does exactly that: it inverts whatever it finds,
so a stale page produces the opposite of what its own star showed. Sending the desired state makes
the request idempotent, so the worst a stale page can do is confirm the state that is already
stored.

It also matches the only precedent in the codebase for this exact problem, which is worth more than
a marginally shorter route.

**Alternatives considered**:

- *A toggle route with no payload.* Rejected for the reason above, and because it makes the endpoint
  non idempotent for no gain.
- *Two routes, one to set and one to clear.* Rejected. Two routes and two controller methods to carry
  one boolean, where the existing precedent carries it in one.

## 4. How the star redraws without a page load

**Decision**: the star sits in its own region with an id and `x-merge="replace"`, the form targets
that region with `x-target`, and the controller redirects back to `contacts.show`. Alpine Ajax
follows the redirect and swaps the region in. Without JavaScript the same request is an ordinary form
post and the browser follows the redirect, so the mark is still applied and the page still shows the
new state.

**Rationale**: this is the pattern the contact page and the people list already use, in
`_section.blade.php` with `x-target` on the section's region id, and in `index.blade.php` with
`x-target="contacts"`. The controllers behind them redirect to a route rather than returning a
fragment, which is what keeps the no JavaScript path working. `<x-form>` supplies the token and the
method field, so nothing about the request is hand rolled.

**Alternatives considered**:

- *Optimistic update in Alpine with a background request.* Rejected. It makes the drawn state and the
  stored state disagree whenever the request fails, which FR-012 forbids, and it breaks entirely
  without JavaScript.
- *A full page reload.* Rejected. FR-003 asks for the new state without reloading or navigating, and
  the page is long enough that a reload loses the reader's place.
