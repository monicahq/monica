---
name: help-bubbles
description: When and how to add a help bubble (x-help) to a screen, and the help centre page it points at. Use when creating or changing a form, a setting, or a concept a reader may not understand, or when the user mentions help, a help bubble, contextual help, a question mark or a guide link.
---

# Help bubbles

A help bubble is a small question mark beside something on a screen. Opened, it explains in a few
sentences how that thing works, then offers the full guide in the help centre (`docs/portal`).
Specification 101 has the reasoning.

## When a screen needs one

Add a bubble when the reader cannot see the consequence of a control from its label alone:

- a step that cannot be undone, or that locks something;
- something sent somewhere else (an email, a device, another person);
- a term Monica gives its own meaning (vault, partial contact, template version, sensitive field);
- a choice between two modes whose difference only shows later.

Do not add one for:

- a label that already says everything (First name, Upload);
- a validation message, which belongs next to the field when it fails;
- a sentence the screen already prints. Shorten the screen's sentence or keep it, never repeat it.

One bubble per thing, and at most one per section of a screen. A section that seems to need two says
its wording is wrong: fix the wording instead. Most sections need none, because their description
already says enough; a bubble earns its place only by answering what that description cannot.

## How to write it

```blade
<x-help
  :label="__('Help about the email address')"
  :title="__('What does this address do?')"
  guide="account.profile#your-email-address"
>{{ __('Sign in links, password resets and security warnings all go to this address.') }}</x-help>
```

- `label` is the whole accessible name of the mark, "Help about" plus the thing, as one translated
  string. Never a bare "Help".
- `title` is a question the reader would ask, ending with a question mark.
- The explanation is at most three sentences, in the plain register of the translations skill. Say
  what happens, not what the screen looks like.
- The `example` slot is optional. Use it when one sentence still leaves the reader guessing, and set
  the example in the reader's own world: a person, a relative, a phone. Put it after the explanation.
- `align="right"` when the mark sits near the right edge, so the bubble opens leftwards.
- Every string goes through `__()`, then `php artisan monica:localize` and translations in every locale.

## Which guide

`guide` is a help centre page id, the `id:` in a page's frontmatter, never a URL. It survives a page
being renamed or moved. List them with:

```bash
grep -rh "^id:" docs/portal/en
```

Point at the page that explains the thing, not at a section index.

When the page covers several things and one `##` section answers the bubble's question, add that
section after a `#`: `guide="account.profile#your-email-address"`. The anchor is the heading's text
in lower case, words joined by dashes, as the English page writes it. When the whole page is the
answer, give the id alone. Never point at a `###` heading: only `##` headings are sections.

**When no page explains it, write one in the same change** with the `writer` skill, in
`docs/portal/en`, with its frontmatter and today's `last_updated`. A bubble never waits for its page.
The same goes for a page that exists but has no section answering the question: add the `##`
section. `tests/Unit/ViewModels/Docs/HelpGuideCoverageTest.php` fails, naming the view, when a
bubble has no `guide`, names a page that does not exist, or names a section the page does not have.
Write the guide as a plain attribute (`guide="account.profile#your-email-address"`), not a bound
one, so that test can read it.

## Where the mark goes

- Beside a section title: the `help` slot of `x-page-section`, which places it after the heading and
  outside it.
- Beside a control: right after the control or its label, in the same row.
- Never inside an `<h1>` to `<h6>` or a `<label>`: the mark's name would become part of the heading's
  or the field's name.

## What the component already does

Do not rebuild any of this on a screen. `x-help` opens on hover with a mouse, on Enter, Space or a
tap, never on focus alone; closes on Escape, an outside click, or when focus leaves it; keeps one
bubble open at a time; opens guides in a new tab and says so; and stays readable at 320 px wide.
`tests/Feature/Views/Components/HelpTest.php` holds its contract.
